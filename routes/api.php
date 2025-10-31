<?php

use App\Http\Controllers\TesteBoletoController;
use App\Http\Controllers\TesteEmailController;
use App\Http\Controllers\TesteNotaFiscalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/testar-boletos-sem-job', [TesteBoletoController::class, 'semJob']);
Route::post('/testar-boletos-com-job', [TesteBoletoController::class, 'comJob']);

Route::get('/teste-email/sem-job', [TesteEmailController::class, 'semJob']);
Route::get('/teste-email/com-job', [TesteEmailController::class, 'comJob']);

Route::get('/teste-nota/sem-job', [TesteNotaFiscalController::class, 'semJob']);
Route::get('/teste-nota/com-job', [TesteNotaFiscalController::class, 'comJob']);