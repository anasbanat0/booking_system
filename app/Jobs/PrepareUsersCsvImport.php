<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PrepareUsersCsvImport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public array $backoff = [60, 300, 900];

    public function __construct(
        public string $path,
        public array $locations,
        public int $actorId,
        public bool $canManageAllBranches,
        public ?int $actorBranchId,
    ) {}

    public function handle(): void
    {
        $handle = null;

        try {
            $handle = fopen(Storage::disk('local')->path($this->path), 'rb');

            if ($handle === false) {
                throw new \RuntimeException('The queued CSV file could not be opened.');
            }

            $header = fgetcsv($handle);

            if (! $header) {
                throw ValidationException::withMessages(['file' => 'The uploaded file is empty.']);
            }

            $header = array_map(
                fn ($value) => strtolower(trim((string) $value, "\xEF\xBB\xBF \t\n\r\0\x0B")),
                $header,
            );

            if (! in_array('name', $header, true) || ! in_array('email', $header, true)) {
                throw ValidationException::withMessages([
                    'file' => 'The CSV must contain name and email columns.',
                ]);
            }

            if (count(array_unique($header)) !== count($header)) {
                throw ValidationException::withMessages([
                    'file' => 'The CSV contains duplicate column names.',
                ]);
            }

            $chunk = [];
            $rowCount = 0;
            $chunkCount = 0;

            while (($row = fgetcsv($handle)) !== false) {
                $row = array_slice(array_pad($row, count($header), null), 0, count($header));
                $data = array_combine($header, array_map(function ($value) {
                    $value = (string) ($value ?? '');

                    return mb_check_encoding($value, 'UTF-8')
                        ? $value
                        : mb_convert_encoding($value, 'UTF-8', ['Windows-1256', 'ISO-8859-1']);
                }, $row));

                if (blank($data['email'] ?? null) && blank($data['name'] ?? null)) {
                    continue;
                }

                $chunk[] = $data;
                $rowCount++;

                if (count($chunk) === 25) {
                    $this->dispatchChunk($chunk, $rowCount - count($chunk));
                    $chunk = [];
                    $chunkCount++;
                }
            }

            if ($chunk !== []) {
                $this->dispatchChunk($chunk, $rowCount - count($chunk));
                $chunkCount++;
            }

            Log::warning('CSV user import file prepared.', [
                'actor_id' => $this->actorId,
                'rows' => $rowCount,
                'chunks' => $chunkCount,
            ]);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }

            Storage::disk('local')->delete($this->path);
        }
    }

    private function dispatchChunk(array $chunk, int $offset): void
    {
        ImportUsersCsvChunk::dispatch(
            $chunk,
            $this->locations,
            $this->actorId,
            $this->canManageAllBranches,
            $this->actorBranchId,
            $offset,
        );
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('CSV user import file job failed.', [
            'actor_id' => $this->actorId,
            'path' => $this->path,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
