<?php

namespace MazerDev\Dialogify\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Native\Mobile\Events\Contracts\DispatchableFromNative;

class ButtonPressed implements DispatchableFromNative
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $buttonId,
        public string $label,
        public int $index,
        public ?string $dialogId = null,
    ) {}
}
