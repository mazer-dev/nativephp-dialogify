<?php

namespace MazerDev\Dialogify;

use MazerDev\Dialogify\Enums\Icon;

class PendingAlert
{
    protected ?Button $positive = null;

    protected ?Button $negative = null;

    protected ?Button $neutral = null;

    protected ?string $icon = null;

    protected bool $cancelable = true;

    /** @var Input[] */
    protected array $inputs = [];

    protected ?string $id = null;

    protected ?string $eventClass = null;

    protected bool $shown = false;

    public function __construct(
        protected string $title,
        protected ?string $message = null,
    ) {}

    public function positive(Button $button): self
    {
        $this->positive = $button;

        return $this;
    }

    public function negative(Button $button): self
    {
        $this->negative = $button;

        return $this;
    }

    public function neutral(Button $button): self
    {
        $this->neutral = $button;

        return $this;
    }

    public function icon(Icon|string $icon): self
    {
        $this->icon = $icon instanceof Icon ? $icon->value : $icon;

        return $this;
    }

    public function cancelable(bool $cancelable = true): self
    {
        $this->cancelable = $cancelable;

        return $this;
    }

    public function addInput(Input $input): self
    {
        $this->inputs[] = $input;

        return $this;
    }

    public function id(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function event(string $eventClass): self
    {
        $this->eventClass = $eventClass;

        return $this;
    }

    /** @return array{title: string, message: ?string, positive: ?array<string, mixed>, negative: ?array<string, mixed>, neutral: ?array<string, mixed>, icon: ?string, cancelable: bool, inputs: array<int, array<string, mixed>>, id: ?string, event: ?string} */
    public function toPayload(): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'positive' => $this->positive?->toArray(),
            'negative' => $this->negative?->toArray(),
            'neutral' => $this->neutral?->toArray(),
            'icon' => $this->icon,
            'cancelable' => $this->cancelable,
            'inputs' => array_map(fn (Input $input) => $input->toArray(), $this->inputs),
            'id' => $this->id,
            'event' => $this->eventClass,
        ];
    }

    public function show(): void
    {
        if ($this->shown) {
            return;
        }

        $this->shown = true;

        if (function_exists('nativephp_call')) {
            nativephp_call('Dialogify.Alert', json_encode($this->toPayload()));
        }
    }

    public function __destruct()
    {
        if (! $this->shown) {
            $this->show();
        }
    }
}
