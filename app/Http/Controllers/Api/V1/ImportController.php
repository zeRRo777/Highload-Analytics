<?php

namespace App\Http\Controllers\Api\V1;

use App\CQRT\Commands\ImportFileCommand;
use App\Data\ImportData;
use App\Http\Controllers\Api\V1\Controller;
use App\Http\Resources\ImportResource;
use App\Models\Import;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;


class ImportController extends Controller
{
    #[OA\Post(
        path: '/api/v1/imports',
        summary: 'Загрузка файла для импорта',
        description: 'Принимает CSV или TXT файл (до 100MB), сохраняет его в S3 и ставит задачу на обработку в очередь.',
        tags: ['Imports'],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Файл для импорта (multipart/form-data)',
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        new OA\Property(
                            property: 'file',
                            description: 'Файл транзакций (только .csv или .txt, макс. 100MB)',
                            type: 'string',
                            format: 'binary'
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 202,
                description: 'Файл успешно принят в обработку',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1, description: 'ID импорта'),
                                new OA\Property(property: 'status', type: 'string', example: 'pending', description: 'Статус (pending, processing, completed, failed)'),
                                new OA\Property(property: 'file_path', type: 'string', example: 'imports/random-name.csv', description: 'Путь к файлу в S3'),
                                new OA\Property(property: 'total_rows', type: 'integer', example: 0, description: 'Всего строк в файле'),
                                new OA\Property(property: 'processed_rows', type: 'integer', example: 0, description: 'Количество обработанных строк')
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Ошибка валидации (например, неверный формат или размер файла)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'type', type: 'string', example: 'http://localhost:8080/errors/validation-error'),
                        new OA\Property(property: 'title', type: 'string', example: 'Validation Error'),
                        new OA\Property(property: 'status', type: 'integer', example: 422),
                        new OA\Property(property: 'detail', type: 'string', example: 'Произошла одна или несколько ошибок проверки.'),
                        new OA\Property(property: 'instance', type: 'string', example: '/api/v1/imports'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            description: 'Список ошибок валидации',
                            additionalProperties: new OA\AdditionalProperties(
                                type: 'array',
                                items: new OA\Items(type: 'string')
                            ),
                            example: [
                                'file' => ['The file field must be a file of type: csv, txt.', 'The file field must not be greater than 102400 kilobytes.']
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function import(ImportData $data, ImportFileCommand $importFileCommand): JsonResponse
    {
        $import = $importFileCommand($data);

        return (new ImportResource($import))
            ->response()
            ->setStatusCode(202);
    }

    #[OA\Get(
        path: '/api/v1/imports/{import}',
        summary: 'Получение статуса импорта (Поллинг)',
        description: 'Возвращает текущую информацию по импорту, включая прогресс обработки (processed_rows / total_rows).',
        tags: ['Imports'],
        parameters: [
            new OA\Parameter(
                name: 'import',
                in: 'path',
                required: true,
                description: 'ID импорта',
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешное получение статуса',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'status', type: 'string', example: 'processing'),
                                new OA\Property(property: 'file_path', type: 'string', example: 'imports/random-name.csv'),
                                new OA\Property(property: 'total_rows', type: 'integer', example: 100000),
                                new OA\Property(property: 'processed_rows', type: 'integer', example: 45000)
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Импорт не найден',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'type', type: 'string', example: 'http://localhost:8080/errors/not-found'),
                        new OA\Property(property: 'title', type: 'string', example: 'Not Found'),
                        new OA\Property(property: 'status', type: 'integer', example: 404),
                        new OA\Property(property: 'detail', type: 'string', example: 'No query results for model [App\\Models\\Import] 1.'),
                        new OA\Property(property: 'instance', type: 'string', example: '/api/v1/imports/1')
                    ]
                )
            )
        ]
    )]
    public function show(Import $import): ImportResource
    {
        return new ImportResource($import);
    }
}
