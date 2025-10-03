<?php

use App\Services\EmailToTicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ZohoWebhookController;
use App\Http\Controllers\ZohoOauthController;

Route::get('/', function () {
    return view('welcome');
});

// Dashboard general (si lo usas fuera de Filament)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    Route::get('/email-threading', function () {
        return view('email-threading-dashboard');
    })->name('email.threading.dashboard');
});

// Webhook Zoho Mail
Route::post('/webhooks/zoho-mail', [ZohoWebhookController::class, 'handle'])
    ->name('webhooks.zoho_mail')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Zoho OAuth2 routes
Route::get('/oauth/zoho/authorize', [ZohoOauthController::class, 'authorize'])->name('zoho.oauth.authorize');
Route::get('/oauth/zoho/callback', [ZohoOauthController::class, 'callback'])->name('zoho.oauth.callback');

// Todas las gestiones de agentes, tickets, plantillas y clientes se hacen desde /admin (Filament)
