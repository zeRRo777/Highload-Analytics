<?php

namespace App\Http\Controllers\Api\V1;

use App\CQRT\Queries\GetMonthlyRank;
use App\Http\Controllers\Api\V1\Controller;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;

class AnalyticsController extends Controller
{
    #[OA\Get(
        path: '/api/v1/analytics/monthly-rank',
        summary: 'Получение ежемесячного рейтинга пользователей',
        description: 'Возвращает топ-100 пользователей по сумме транзакций за каждый месяц, включая их ранг и среднее значение суммы транзакций (по всем пользователям) за этот месяц. Результат кэшируется на 1 час.',
        tags: ['Analytics'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешное получение аналитики',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            description: 'Массив с данными аналитики',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'user_id', type: 'integer', example: 2, description: 'ID пользователя'),
                                    new OA\Property(property: 'month', type: 'string', example: '2023-10', description: 'Месяц агрегации (YYYY-MM)'),
                                    new OA\Property(property: 'total_amount', type: 'integer', example: 300000, description: 'Общая сумма транзакций пользователя за месяц'),
                                    new OA\Property(property: 'user_rank', type: 'integer', example: 1, description: 'Ранг пользователя в указанном месяце (1 - лидер)'),
                                    new OA\Property(property: 'monthly_avg', type: 'integer', example: 200000, description: 'Средняя сумма транзакций за этот месяц среди всех пользователей')
                                ]
                            )
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Внутренняя ошибка сервера (например, недоступна БД или кэш)',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'type', type: 'string', example: 'http://localhost:8080/errors/server-error'),
                        new OA\Property(property: 'title', type: 'string', example: 'Server Error'),
                        new OA\Property(property: 'status', type: 'integer', example: 500),
                        new OA\Property(property: 'detail', type: 'string', example: 'Internal Server Error.'),
                        new OA\Property(property: 'instance', type: 'string', example: '/api/v1/analytics/monthly-rank')
                    ]
                )
            )
        ]
    )]
    public function monthlyRank(GetMonthlyRank $GetMonthlyRank): JsonResponse
    {
        return response()->json([
            'data' => $GetMonthlyRank()
        ]);
    }
}
