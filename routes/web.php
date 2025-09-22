<?php

use App\Services\EmailToTicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ZohoWebhookController;

Route::get('/', function () {
    return view('welcome');
});

// Dashboard de Email Threading para usuarios autenticados
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/email-threading', function () {
        return view('email-threading-dashboard');
    })->name('email.threading.dashboard');
});

// Zoho Mail webhook endpoint (public POST)
Route::post('/webhooks/zoho-mail', [ZohoWebhookController::class, 'handle'])->name('webhooks.zoho_mail');
