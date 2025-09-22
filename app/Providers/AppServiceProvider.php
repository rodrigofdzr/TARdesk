<?php

namespace App\Providers;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Observers\TicketObserver;
use App\Observers\TicketReplyObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar observers para email-to-ticket con threading
        Ticket::observe(TicketObserver::class);
        TicketReply::observe(TicketReplyObserver::class);
    }
}
