<?php

namespace MazerDev\Dialogify\Facades;

use Illuminate\Support\Facades\Facade;
use MazerDev\Dialogify\PendingAlert;
use MazerDev\Dialogify\PendingSheet;
use MazerDev\Dialogify\PendingToast;

/**
 * @method static PendingToast toast(string $message, array $options = [])
 * @method static PendingAlert alert(string $title, ?string $message = null, array $options = [])
 * @method static PendingSheet sheet(?string $title = null, ?string $message = null)
 */
class Dialogify extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \MazerDev\Dialogify\Dialogify::class;
    }
}
