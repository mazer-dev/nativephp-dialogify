<?php

namespace MazerDev\Dialogify;

use MazerDev\Dialogify\Enums\ButtonStyle;

class Button
{
    protected ButtonStyle $style = ButtonStyle::Neutral;

    protected ?string $color = null;

    protected ?string $backgroundColor = null;

    public function __construct(
        protected string $id,
        protected string $label,
    ) {}

    public static function make(string $id, string $label): self
    {
        return new self($id, $label);
    }

    public function style(ButtonStyle $style): self
    {
        $this->style = $style;

        return $this;
    }

    public function primary(): self
    {
        return $this->style(ButtonStyle::Primary);
    }

    public function destructive(): self
    {
        return $this->style(ButtonStyle::Destructive);
    }

    public function neutral(): self
    {
        return $this->style(ButtonStyle::Neutral);
    }

    public function cancel(): self
    {
        return $this->style(ButtonStyle::Cancel);
    }

    public function color(string $hex): self
    {
        $this->color = $hex;

        return $this;
    }

    public function backgroundColor(string $hex): self
    {
        $this->backgroundColor = $hex;

        return $this;
    }

    /** @return array{id: string, label: string, style: string, color: ?string, backgroundColor: ?string} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'style' => $this->style->value,
            'color' => $this->color,
            'backgroundColor' => $this->backgroundColor,
        ];
    }
}
