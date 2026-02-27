<?php

namespace MazerDev\Dialogify\Facades;

use Illuminate\Support\Facades\Facade;
use MazerDev\Dialogify\PendingToast;

/**
 * @method static PendingToast make(string $message)
 */
class Toast extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'dialogify.toast';
    }
}
