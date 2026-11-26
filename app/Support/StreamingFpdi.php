<?php

namespace App\Support;

use setasign\Fpdi\Fpdi;

/**
 * FPDI that writes the finished PDF straight to a file in 1 MB chunks.
 *
 * Stock FPDF builds the entire output document in one in-memory string
 * ($this->buffer) before saving it. For a portfolio that merges many
 * uploaded PDFs that string can be tens of MB, which exhausts a 128 MB
 * shared-hosting memory_limit. Here the buffer is flushed to disk as it
 * grows, and _getoffset() accounts for bytes already flushed so the
 * cross-reference table offsets stay correct.
 *
 * FPDF only writes to $this->buffer during Close() (via _put), so
 * buffering starts when writeToFile() opens the handle.
 */
class StreamingFpdi extends Fpdi
{
    private const FLUSH_AT = 1048576; // 1 MB

    /** @var resource|null */
    private $handle = null;

    private int $flushed = 0;

    public function writeToFile(string $path): void
    {
        $this->handle = fopen($path, 'wb');
        if ($this->handle === false) {
            throw new \RuntimeException("Cannot open {$path} for writing.");
        }

        try {
            $this->Close();       // FPDF writes the whole document via _put() here
            $this->flushBuffer();
        } finally {
            fclose($this->handle);
            $this->handle = null;
        }
    }

    protected function _put($s, $newLine = true)
    {
        parent::_put($s, $newLine);

        if ($this->handle && strlen($this->buffer) >= self::FLUSH_AT) {
            $this->flushBuffer();
        }
    }

    protected function _getoffset()
    {
        return $this->flushed + strlen($this->buffer);
    }

    private function flushBuffer(): void
    {
        if ($this->buffer === '') {
            return;
        }

        if (fwrite($this->handle, $this->buffer) !== strlen($this->buffer)) {
            throw new \RuntimeException('Failed writing PDF output (disk full?).');
        }

        $this->flushed += strlen($this->buffer);
        $this->buffer = '';
    }
}
