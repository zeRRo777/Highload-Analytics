<?php

namespace App\Jobs;

use App\Enums\Status;
use App\Models\Import;
use App\Models\Transaction;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;
use Throwable;

class ProcessCsvImportJob implements ShouldQueue
{
    use Queueable;

    private const CHUNK_SIZE = 1000;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Import $import
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->import->update([
            'status' => Status::Processing->value
        ]);

        $stream = Storage::disk('s3')->readStream($this->import->file_path);

        if (!$stream) {
            $this->import->update([
                'status' => Status::Failed->value
            ]);

            return;
        }

        $totalRows = 0;
        $processedRows = 0;

        try {
            LazyCollection::make(function () use ($stream, &$totalRows) {
                $header = fgetcsv($stream);

                if (!$header) {
                    throw new Exception("Файл пуст или имеет неверный формат");
                }

                while (($row = fgetcsv($stream)) !== false) {
                    $totalRows++;
                    yield $row;
                }
            })
                ->reject(function (array $row) {
                    return count($row) < 3;
                })
                ->map(function (array $row) {
                    return [
                        'user_id' => (int) $row[0],
                        'amount' => (int) $row[1],
                        'transaction_date' => $row[2],
                    ];
                })
                ->chunk(self::CHUNK_SIZE)
                ->each(function (LazyCollection $chunk) use (&$processedRows) {
                    $records = $chunk->toArray();

                    Transaction::insert($records);
                    $processedRows += count($records);

                    $this->import->update([
                        'processed_rows' => $processedRows,
                    ]);
                });

            $this->import->update([
                'status' => Status::Completed->value,
                'total_rows' => $totalRows,
                'processed_rows' => $processedRows,
            ]);
        } catch (Throwable $e) {
            $this->import->update([
                'status' => Status::Failed->value
            ]);

            throw $e;
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }
}
