<?php

namespace MazerDev\Dialogify;

use MazerDev\Dialogify\Enums\InputType;

class Input
{
    protected InputType $type = InputType::Text;

    protected ?string $placeholder = null;

    protected mixed $defaultValue = null;

    protected bool $required = false;

    protected ?int $maxLength = null;

    /** @var array<string, string>|null */
    protected ?array $options = null;

    protected ?string $minDate = null;

    protected ?string $maxDate = null;

    protected ?int $minuteInterval = null;

    public function __construct(
        protected string $id,
        protected string $label,
    ) {}

    public static function make(string $id, string $label): self
    {
        return new self($id, $label);
    }

    public function type(InputType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function placeholder(string $text): self
    {
        $this->placeholder = $text;

        return $this;
    }

    public function defaultValue(mixed $value): self
    {
        $this->defaultValue = $value;

        return $this;
    }

    public function required(bool $required = true): self
    {
        $this->required = $required;

        return $this;
    }

    public function maxLength(int $max): self
    {
        $this->maxLength = $max;

        return $this;
    }

    /** @param array<string, string> $options */
    public function options(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function minDate(string $date): self
    {
        $this->minDate = $date;

        return $this;
    }

    public function maxDate(string $date): self
    {
        $this->maxDate = $date;

        return $this;
    }

    public function minuteInterval(int $minutes): self
    {
        $this->minuteInterval = $minutes;

        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'type' => $this->type->value,
            'placeholder' => $this->placeholder,
            'defaultValue' => $this->defaultValue,
            'required' => $this->required,
            'maxLength' => $this->maxLength,
            'options' => $this->options,
            'minDate' => $this->minDate,
            'maxDate' => $this->maxDate,
            'minuteInterval' => $this->minuteInterval,
        ];
    }
}
