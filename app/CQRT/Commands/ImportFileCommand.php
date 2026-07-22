<?php

namespace App\CQRT\Commands;

use App\Data\ImportData;
use App\Enums\Status;
use App\Jobs\ProcessCsvImportJob;
use App\Models\Import;
use Illuminate\Support\Facades\Storage;

class ImportFileCommand
{
    public function __invoke(ImportData $data): Import
    {
        $path = Storage::disk('s3')->putFile('imports', $data->file);

        $import = Import::create([
            'file_path' => $path,
            'status' => Status::Pending->value
        ]);

        ProcessCsvImportJob::dispatch($import);

        return $import;
    }
}
