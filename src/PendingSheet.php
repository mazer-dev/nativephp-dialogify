<?php

namespace MazerDev\Dialogify;

class PendingSheet
{
    protected ?string $message = null;

    /** @var Button[] */
    protected array $buttons = [];

    /** @var Input[] */
    protected array $inputs = [];

    protected bool $cancelable = true;

    protected ?string $id = null;

    protected ?string $eventClass = null;

    protected bool $shown = false;

    public function __construct(
        protected ?string $title = null,
    ) {}

    public function message(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function addButton(Button $button): self
    {
        $this->buttons[] = $button;

        return $this;
    }

    public function addInput(Input $input): self
    {
        $this->inputs[] = $input;

        return $this;
    }

    public function cancelable(bool $cancelable = true): self
    {
        $this->cancelable = $cancelable;

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

    /**
     * @return array{title: ?string, message: ?string, buttons: array<int, array<string, mixed>>, inputs: array<int, array<string, mixed>>, cancelable: bool, id: ?string, event: ?string}
     */
    public function toPayload(): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'buttons' => array_map(fn (Button $b) => $b->toArray(), $this->buttons),
            'inputs' => array_map(fn (Input $i) => $i->toArray(), $this->inputs),
            'cancelable' => $this->cancelable,
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
            nativephp_call('Dialogify.Sheet', json_encode($this->toPayload()));
        }
    }

    public function __destruct()
    {
        if (! $this->shown) {
            $this->show();
        }
    }
}
