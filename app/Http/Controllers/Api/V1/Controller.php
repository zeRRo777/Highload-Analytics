<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller as BaseController;
use OpenApi\Attributes as OA;

/**
 * Базовый контроллер для API v1.
 */
#[OA\Info(
    version: "1.0.0",
    title: "Highload-Analytics API v1",
    description: "API для сервиса управления транзакциями (Версия 1)."
)]
#[OA\Contact(email: "test@example.com")]
#[OA\Server(
    url: "http://localhost:8080",
    description: "Local Development Server"
)]
abstract class Controller extends BaseController
{
    //
}
