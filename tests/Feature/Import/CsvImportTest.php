<?php

use App\Enums\Status;
use App\Jobs\ProcessCsvImportJob;
use App\Models\Import;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

it('accepts csv file, saves to s3, and dispatches job', function () {
    // Подменяем S3 и Очереди для тестов
    Storage::fake('s3');
    Queue::fake();

    // Генерируем фейковый CSV файл
    $file = UploadedFile::fake()->create('transactions.csv', 1000, 'text/csv');

    $response = $this->postJson('/api/v1/imports', [
        'file' => $file,
    ]);

    $response->assertStatus(202);
    $response->assertJsonStructure(['data' => ['id', 'status', 'file_path']]);

    $this->assertDatabaseHas('imports', [
        'status' => Status::Pending->value
    ]);

    $import = Import::first();

    // Проверяем, что файл реально лежит в фейковом S3
    Storage::disk('s3')->assertExists($import->file_path);

    // Проверяем, что Job улетел в очередь
    Queue::assertPushed(ProcessCsvImportJob::class, function ($job) use ($import) {
        return $job->import->id === $import->id;
    });
});

it('returns import status for polling', function () {
    $import = Import::create([
        'file_path' => 'imports/dummy.csv',
        'status' => Status::Processing->value,
        'total_rows' => 100000,
        'processed_rows' => 5000,
    ]);

    $response = $this->getJson("/api/v1/imports/{$import->id}");

    $response->assertStatus(200);
    $response->assertJsonPath('data.status', Status::Processing->value);
    $response->assertJsonPath('data.processed_rows', 5000);
});

it('validates file extension', function () {
    $file = UploadedFile::fake()->create('image.jpg', 1000, 'image/jpeg');

    $response = $this->postJson('/api/v1/imports', [
        'file' => $file,
    ]);

    $response->assertStatus(422);

    $response->assertJsonStructure([
        'type',
        'title',
        'status',
        'detail',
        'instance',
        'errors' => [
            'file'
        ]
    ]);

    $response->assertJsonPath('status', 422);
});
