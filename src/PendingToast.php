<?php

namespace MazerDev\Dialogify;

use MazerDev\Dialogify\Enums\Duration;
use MazerDev\Dialogify\Enums\Icon;
use MazerDev\Dialogify\Enums\Position;

class PendingToast
{
    protected Position $position = Position::Bottom;

    protected ?string $backgroundColor = null;

    protected ?string $textColor = null;

    protected ?string $icon = null;

    protected Duration|int $duration = Duration::Long;

    protected ?int $cornerRadius = null;

    protected ?Button $action = null;

    protected ?string $id = null;

    protected bool $shown = false;

    public function __construct(
        protected string $message,
    ) {}

    public function position(Position $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function backgroundColor(string $hex): self
    {
        $this->backgroundColor = $hex;

        return $this;
    }

    public function textColor(string $hex): self
    {
        $this->textColor = $hex;

        return $this;
    }

    public function icon(Icon|string $icon): self
    {
        $this->icon = $icon instanceof Icon ? $icon->value : $icon;

        return $this;
    }

    public function duration(Duration|int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function cornerRadius(int $dp): self
    {
        $this->cornerRadius = $dp;

        return $this;
    }

    public function action(Button $button): self
    {
        $this->action = $button;

        return $this;
    }

    public function id(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /** @return array{message: string, position: string, backgroundColor: ?string, textColor: ?string, icon: ?string, duration: string|int, cornerRadius: ?int, action: ?array, id: ?string} */
    public function toPayload(): array
    {
        return [
            'message' => $this->message,
            'position' => $this->position->value,
            'backgroundColor' => $this->backgroundColor,
            'textColor' => $this->textColor,
            'icon' => $this->icon,
            'duration' => $this->duration instanceof Duration ? $this->duration->value : $this->duration,
            'cornerRadius' => $this->cornerRadius,
            'action' => $this->action?->toArray(),
            'id' => $this->id,
        ];
    }

    public function show(): void
    {
        if ($this->shown) {
            return;
        }

        $this->shown = true;

        if (function_exists('nativephp_call')) {
            nativephp_call('Dialogify.Toast', json_encode($this->toPayload()));
        }
    }

    public function __destruct()
    {
        if (! $this->shown) {
            $this->show();
        }
    }
}
