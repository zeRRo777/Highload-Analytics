<?php

use App\Http\Controllers\Api\V1\ImportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/imports', [ImportController::class, 'import'])->name('api.v1.imports');

    Route::get('/imports/{import}', [ImportController::class, 'show'])->name('api.v1.imports.show');
});
