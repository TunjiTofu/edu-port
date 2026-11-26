<?php

namespace App\Http\Controllers;

use App\Filament\Student\Pages\PrintPortfolio;
use App\Services\PortfolioPdfBuilder;
use App\Services\PortfolioService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Streams a candidate's portfolio PDF from disk.
 *
 * Registered inside the student panel (authenticatedRoutes), so it runs
 * behind the same login, student-role and profile middleware as the panel.
 * Deliberately a plain HTTP response rather than a Livewire action:
 * Livewire downloads hold the whole file in memory several times over
 * (output capture + base64 + JSON), which exhausted a 128 MB limit.
 */
class PortfolioDownloadController extends Controller
{
    public function __invoke(int $program, PortfolioService $service, PortfolioPdfBuilder $builder)
    {
        $user = Filament::auth()->user();
        $data = $service->build($user, $program);

        if (! $data || $data['summary']['completed'] === 0) {
            Notification::make()
                ->title($data ? 'Nothing to print yet' : 'Program not found')
                ->body($data
                    ? 'None of your tasks in this program have been completed yet. Your portfolio includes completed tasks only.'
                    : 'You are not enrolled in the selected program.')
                ->warning()
                ->send();

            return redirect(PrintPortfolio::getUrl());
        }

        @set_time_limit(300); // merging many uploaded PDFs can take a while

        // Headroom only; the build is designed to fit in 128 MB.
        if ($this->bytes(ini_get('memory_limit')) < 256 * 1048576) {
            @ini_set('memory_limit', '256M');
        }

        $outPath = PortfolioPdfBuilder::outputDir() . '/' . uniqid('portfolio-', true) . '.pdf';

        try {
            $builder->buildToFile($data, $outPath);
        } catch (\Throwable $e) {
            @unlink($outPath);

            Log::error('Portfolio: PDF build failed', [
                'event'      => 'portfolio_build_failed',
                'user_id'    => $user->id,
                'program_id' => $program,
                'error'      => $e->getMessage(),
            ]);

            Notification::make()
                ->title('Could not create your portfolio')
                ->body('Something went wrong while preparing the PDF. Please try again, or contact the administrator if it keeps happening.')
                ->danger()
                ->send();

            return redirect(PrintPortfolio::getUrl());
        }

        Log::info('Portfolio: PDF downloaded', [
            'event'      => 'portfolio_download',
            'user_id'    => $user->id,
            'program_id' => $program,
            'size_bytes' => filesize($outPath),
            'peak_mb'    => round(memory_get_peak_usage(true) / 1048576),
        ]);

        return response()
            ->download($outPath, $service->filename($user, $data), ['Content-Type' => 'application/pdf'])
            ->deleteFileAfterSend();
    }

    private function bytes(string|false $value): int
    {
        if ($value === false || $value === '' || $value === '-1') {
            return PHP_INT_MAX;
        }

        $num = (int) $value;

        return match (strtolower(substr(trim($value), -1))) {
            'g'     => $num * 1073741824,
            'm'     => $num * 1048576,
            'k'     => $num * 1024,
            default => $num,
        };
    }
}
