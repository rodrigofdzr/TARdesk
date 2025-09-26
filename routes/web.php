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
    'auth',
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/email-threading', function () {
        return view('email-threading-dashboard');
    })->name('email.threading.dashboard');
});

// Rutas para agentes - Plataforma interna de servicio al cliente
Route::middleware([
    'auth',
    'verified',
    'role:manager,customer_service,call_center'
])->group(function () {

    // Dashboard principal para agentes
    Route::get('/agent', function () {
        $user = auth()->user();
        return view('agent.dashboard', compact('user'));
    })->name('agent.dashboard');

    // Gestión de tickets - Todos los agentes pueden ver tickets
    Route::get('/tickets', function () {
        return view('agent.tickets.index');
    })->name('tickets.index');

    Route::get('/tickets/{ticket}', function ($ticket) {
        return view('agent.tickets.show', compact('ticket'));
    })->name('tickets.show');

    // Rutas para Call Center y Customer Service - pueden crear tickets
    Route::middleware('role:call_center,customer_service,manager')->group(function () {
        Route::get('/tickets/create', function () {
            return view('agent.tickets.create');
        })->name('tickets.create');

        Route::post('/tickets', function () {
            // Lógica para crear tickets
        })->name('tickets.store');
    });

    // Rutas para Customer Service y Manager - pueden asignar tickets
    Route::middleware('role:customer_service,manager')->group(function () {
        Route::get('/tickets/{ticket}/edit', function ($ticket) {
            return view('agent.tickets.edit', compact('ticket'));
        })->name('tickets.edit');

        Route::patch('/tickets/{ticket}/assign', function ($ticket) {
            // Lógica para asignar tickets
        })->name('tickets.assign');

        Route::get('/reports', function () {
            return view('agent.reports.index');
        })->name('reports.index');
    });

    // Rutas solo para Manager - acceso administrativo
    Route::middleware('role:manager')->group(function () {
        Route::get('/team', function () {
            return view('agent.team.index');
        })->name('team.index');

        Route::get('/settings', function () {
            return view('agent.settings.index');
        })->name('agent.settings');
    });
});

// Zoho Mail webhook endpoint (public POST) - exempt from CSRF so Zoho's initial verification POST (empty body) returns 200
Route::post('/webhooks/zoho-mail', [ZohoWebhookController::class, 'handle'])
    ->name('webhooks.zoho_mail')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
