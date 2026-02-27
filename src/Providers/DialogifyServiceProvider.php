<?php

namespace MazerDev\Dialogify\Providers;

use Illuminate\Support\ServiceProvider;
use MazerDev\Dialogify\Dialogify;
use MazerDev\Dialogify\ModalFactory;
use MazerDev\Dialogify\ToastFactory;

class DialogifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Dialogify::class, fn () => new Dialogify);
        $this->app->singleton('dialogify.toast', fn () => new ToastFactory);
        $this->app->singleton('dialogify.modal', fn () => new ModalFactory);
    }
}
