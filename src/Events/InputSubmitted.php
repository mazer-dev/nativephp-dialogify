<?php

namespace MazerDev\Dialogify\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Native\Mobile\Events\Contracts\DispatchableFromNative;

class InputSubmitted implements DispatchableFromNative
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<string, mixed>  $inputs
     */
    public function __construct(
        public string $buttonId,
        public string $label,
        public ?string $dialogId = null,
        public array $inputs = [],
    ) {}
}
