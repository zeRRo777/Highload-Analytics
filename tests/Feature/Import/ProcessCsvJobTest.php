<?php

use App\Enums\Status;
use App\Jobs\ProcessCsvImportJob;
use App\Models\Import;
use App\Models\Transaction;
use Illuminate\Support\Facades\Storage;

it('processes csv file in chunks and updates import status', function () {
    Storage::fake('s3');

    // Создаем фейковый CSV файл в S3
    $csvContent = "user_id,amount,transaction_date\n"
        . "1,1500,2023-10-01 12:00:00\n"
        . "2,2000,2023-10-02 12:00:00\n"
        . "1,500,2023-10-03 12:00:00\n";

    $filePath = 'imports/test.csv';
    Storage::disk('s3')->put($filePath, $csvContent);

    // Создаем запись Import (статус pending)
    $import = Import::create([
        'file_path' => $filePath,
        'status' => Status::Pending->value,
    ]);

    // Вызываем Job напрямую синхронно
    $job = new ProcessCsvImportJob($import);
    $job->handle();

    // Проверяем БД - транзакции должны появиться
    $this->assertDatabaseCount('transactions', 3);
    $this->assertDatabaseHas('transactions', [
        'user_id' => 1,
        'amount' => 1500,
    ]);

    // Проверяем статус Import
    $import->refresh();
    expect($import->status->value)->toBe(Status::Completed->value);
    expect($import->total_rows)->toBe(3);
    expect($import->processed_rows)->toBe(3);
});

it('handles invalid csv rows gracefully', function () {
    Storage::fake('s3');

    // Строка 2 имеет неверный формат (мало колонок)
    $csvContent = "user_id,amount,transaction_date\n"
        . "1,1500,2023-10-01 12:00:00\n"
        . "2,invalid_data\n"
        . "3,3000,2023-10-03 12:00:00\n";

    $filePath = 'imports/bad_test.csv';
    Storage::disk('s3')->put($filePath, $csvContent);

    $import = Import::create([
        'file_path' => $filePath,
        'status' => Status::Pending->value,
    ]);

    $job = new ProcessCsvImportJob($import);
    $job->handle();

    // В базу должны попасть только 2 правильные строки
    $this->assertDatabaseCount('transactions', 2);

    $import->refresh();
    expect($import->status->value)->toBe(Status::Completed->value);
});
