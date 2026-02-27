<?php

namespace MazerDev\Dialogify\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ButtonPressed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $buttonId,
        public string $label,
        public int $index,
        public ?string $dialogId = null,
    ) {}
}
