<?php

namespace App\Services;

use App\Support\StreamingFpdi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Assembles the candidate portfolio into one PDF:
 *
 *   1. Cover page (MG logo + conference header)       ┐ rendered by dompdf
 *   2. Candidate details, program, summary, contents  ┘ (pdf/portfolio/front)
 *   3. For every submitted task:
 *        - a results page (score, status, resubmitted, reviewer comment)  ← dompdf (pdf/portfolio/task)
 *        - the candidate's own uploaded PDF, every page, untouched        ← merged with FPDI
 *
 * Pure PHP (works on cPanel). The free FPDI parser cannot read some PDF 1.5+
 * files (compressed cross-reference streams); those are converted to PDF 1.4
 * with Ghostscript when the server has it, otherwise the results page says
 * the document could not be included and the portfolio is still produced.
 */
class PortfolioPdfBuilder
{
    private string $workDir;

    /**
     * Build the portfolio and write it to $outPath.
     *
     * Everything goes through files on disk (never the whole PDF as a PHP
     * string), so memory stays flat as portfolios grow; see StreamingFpdi.
     */
    public function buildToFile(array $data, string $outPath): void
    {
        $this->purgeStaleTempFiles();

        $this->workDir = storage_path('app/portfolio-tmp/' . uniqid('p', true));
        File::ensureDirectoryExists($this->workDir);

        try {
            $parts = [$this->renderToFile('pdf.portfolio.front', $data, 'front.pdf')];

            $tasks = collect($data['sections'])->flatMap(fn ($s) => $s['tasks'])->values();
            $total = $tasks->count();

            foreach ($tasks as $i => $task) {
                $source = $this->prepareSubmission($task, $i);

                $parts[] = $this->renderToFile('pdf.portfolio.task', [
                    'task'          => $task,
                    'number'        => $i + 1,
                    'total'         => $total,
                    'student_name'  => $data['student']['name'],
                    'program_label' => $data['program']['label'],
                    'header_year'   => $data['header_year'],
                    'embedded'      => $source['path'] !== null,
                    'page_count'    => $source['pages'],
                    'embed_error'   => $source['error'],
                ], "task-{$i}.pdf");

                if ($source['path']) {
                    $parts[] = $source['path'];
                }
            }

            $this->merge($parts, $data['student']['name'], $outPath);
        } finally {
            File::deleteDirectory($this->workDir);
        }
    }

    /** Where finished portfolios wait to be sent (deleted after sending). */
    public static function outputDir(): string
    {
        $dir = storage_path('app/portfolio-tmp');
        File::ensureDirectoryExists($dir);

        return $dir;
    }

    /**
     * Downloads delete their file after sending, but an aborted download or a
     * crashed request can leave files behind: clear anything over an hour old.
     */
    private function purgeStaleTempFiles(): void
    {
        $cutoff = time() - 3600;

        foreach (glob(self::outputDir() . '/*') ?: [] as $path) {
            if (@filemtime($path) < $cutoff) {
                is_dir($path) ? File::deleteDirectory($path) : @unlink($path);
            }
        }
    }

    private function renderToFile(string $view, array $data, string $name): string
    {
        $path = "{$this->workDir}/{$name}";

        // Subsetting embeds only the glyphs used: keeps DejaVu (needed for names
        // like "Ọlá") without adding ~1 MB of font per rendered part.
        $pdf = Pdf::loadView($view, $data)
            ->setPaper('a4')
            ->setOption('enable_font_subsetting', true);

        file_put_contents($path, $pdf->output());
        unset($pdf); // free dompdf's layout tree before the next part

        return $path;
    }

    /**
     * Copy the candidate's PDF out of storage and make sure FPDI can read it.
     *
     * @return array{path: ?string, pages: int, error: ?string}
     */
    private function prepareSubmission(array $task, int $index): array
    {
        $disk = Storage::disk(config('filesystems.default'));
        $fail = fn (string $error) => ['path' => null, 'pages' => 0, 'error' => $error];

        try {
            if (! $disk->exists($task['file_path'])) {
                return $fail('The file was not found in storage.');
            }

            // Stream the copy: never hold the uploaded file in memory.
            $local = "{$this->workDir}/submission-{$index}.pdf";
            $in    = $disk->readStream($task['file_path']);
            $out   = fopen($local, 'wb');
            stream_copy_to_stream($in, $out);
            fclose($out);
            is_resource($in) && fclose($in);
        } catch (\Throwable $e) {
            Log::warning('Portfolio: could not fetch submission file', ['path' => $task['file_path'], 'error' => $e->getMessage()]);

            return $fail('The file could not be retrieved from storage.');
        }

        if (strncmp((string) file_get_contents($local, false, null, 0, 5), '%PDF-', 5) !== 0) {
            return $fail('The stored file is not a valid PDF.');
        }

        $pages = $this->pageCount($local);
        if ($pages > 0) {
            return ['path' => $local, 'pages' => $pages, 'error' => null];
        }

        // FPDI's free parser can't read it (usually PDF 1.5+ compression): try Ghostscript.
        $converted = $this->convertWithGhostscript($local, "{$this->workDir}/submission-{$index}-gs.pdf");
        if ($converted && ($pages = $this->pageCount($converted)) > 0) {
            return ['path' => $converted, 'pages' => $pages, 'error' => null];
        }

        Log::warning('Portfolio: submission PDF could not be merged', [
            'event'     => 'portfolio_embed_failed',
            'path'      => $task['file_path'],
            'gs_tried'  => $converted !== null,
        ]);

        return $fail('Its PDF format could not be read for merging.');
    }

    private function pageCount(string $path): int
    {
        try {
            return (new Fpdi())->setSourceFile($path);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function convertWithGhostscript(string $in, string $out): ?string
    {
        if (! function_exists('proc_open')) {
            return null; // commonly disabled on shared hosting
        }

        $gs = (new ExecutableFinder())->find('gs');
        if (! $gs) {
            return null;
        }

        try {
            $result = Process::timeout(60)->run([
                $gs, '-q', '-dNOPAUSE', '-dBATCH', '-dSAFER',
                '-sDEVICE=pdfwrite', '-dCompatibilityLevel=1.4',
                '-sOutputFile=' . $out, $in,
            ]);

            return $result->successful() && is_file($out) ? $out : null;
        } catch (\Throwable $e) {
            Log::warning('Portfolio: ghostscript conversion failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /** @param string[] $files */
    private function merge(array $files, string $studentName, string $outPath): void
    {
        $pdf = new StreamingFpdi();
        $pdf->SetAutoPageBreak(false);
        $pdf->AliasNbPages('{nb}');
        $pdf->SetTitle($this->latin1("Portfolio - {$studentName}"));
        $pdf->SetCreator('Candidate Portal');

        $label = $this->latin1($studentName);

        foreach ($files as $file) {
            $count = $pdf->setSourceFile($file);

            for ($p = 1; $p <= $count; $p++) {
                $tpl  = $pdf->importPage($p);
                $size = $pdf->getTemplateSize($tpl);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tpl);

                if ($pdf->PageNo() > 1) { // no number on the cover
                    $pdf->SetFont('Helvetica', '', 7);
                    $pdf->SetTextColor(120, 120, 120);
                    $pdf->SetXY(0, $size['height'] - 8);
                    $pdf->Cell($size['width'], 4, $label . '  |  Page ' . $pdf->PageNo() . ' of {nb}', 0, 0, 'C');
                }
            }
        }

        $pdf->writeToFile($outPath);
    }

    /** FPDF core fonts are Windows-1252; transliterate anything else. */
    private function latin1(string $text): string
    {
        $converted = @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text);

        return $converted !== false ? $converted : preg_replace('/[^\x20-\x7E]/', '?', $text);
    }
}
