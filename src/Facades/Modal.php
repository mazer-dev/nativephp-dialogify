<?php

namespace MazerDev\Dialogify\Facades;

use Illuminate\Support\Facades\Facade;
use MazerDev\Dialogify\PendingAlert;
use MazerDev\Dialogify\PendingSheet;

/**
 * @method static PendingAlert confirm(string $message, ?string $title = null)
 * @method static PendingSheet sheet(?string $title = null, ?string $message = null)
 */
class Modal extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'dialogify.modal';
    }
}
