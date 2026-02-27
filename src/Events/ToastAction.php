<?php

namespace MazerDev\Dialogify\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ToastAction
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $buttonId,
        public string $label,
        public ?string $toastId = null,
    ) {}
}
