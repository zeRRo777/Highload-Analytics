<?php

use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;

it('calculates monthly rank using window functions and caches the result', function () {
    // Очищаем кэш перед тестом
    Cache::flush();

    Transaction::create(['user_id' => 1, 'amount' => 100000, 'transaction_date' => '2023-10-05 10:00:00']);
    Transaction::create(['user_id' => 2, 'amount' => 300000, 'transaction_date' => '2023-10-10 10:00:00']);
    Transaction::create(['user_id' => 1, 'amount' => 500000, 'transaction_date' => '2023-11-01 10:00:00']);

    $response = $this->getJson('/api/v1/analytics/monthly-rank');

    $response->assertStatus(200);

    // Проверяем кэш
    expect(Cache::has('analytics:monthly-rank'))->toBeTrue();

    // Проверяем структуру ответа
    $data = $response->json('data');
    expect($data)->toBeArray();

    $octoberTopUser = collect($data)->firstWhere('month', '2023-10');
    expect($octoberTopUser['user_id'])->toBe(2);
    expect($octoberTopUser['user_rank'])->toBe(1);
    expect($octoberTopUser['monthly_avg'])->toBe(200000);
});
