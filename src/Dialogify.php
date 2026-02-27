<?php

namespace MazerDev\Dialogify;

use MazerDev\Dialogify\Enums\ButtonStyle;
use MazerDev\Dialogify\Enums\Duration;
use MazerDev\Dialogify\Enums\Icon;
use MazerDev\Dialogify\Enums\Position;

class Dialogify
{
    public function toast(string $message, array $options = []): PendingToast
    {
        $toast = new PendingToast($message);

        foreach ($options as $key => $value) {
            match ($key) {
                'position' => $toast->position(
                    Position::tryFrom($value) ?? throw new \InvalidArgumentException("Invalid position: '{$value}'. Valid values: top, bottom, center")
                ),
                'backgroundColor' => $toast->backgroundColor($value),
                'textColor' => $toast->textColor($value),
                'icon' => $toast->icon(is_string($value) ? (Icon::tryFrom($value) ?? $value) : $value),
                'duration' => $toast->duration(is_int($value) ? $value : (Duration::tryFrom($value) ?? throw new \InvalidArgumentException("Invalid duration: '{$value}'. Valid values: short, long"))),
                'cornerRadius' => $toast->cornerRadius($value),
                'id' => $toast->id($value),
                default => null,
            };
        }

        return $toast;
    }

    public function alert(string $title, ?string $message = null, array $options = []): PendingAlert
    {
        $alert = new PendingAlert($title, $message);

        foreach ($options as $key => $value) {
            match ($key) {
                'positive' => $alert->positive(
                    is_array($value)
                        ? Button::make(
                            $value['id'] ?? throw new \InvalidArgumentException("Button array must have an 'id' key"),
                            $value['label'] ?? throw new \InvalidArgumentException("Button array must have a 'label' key"),
                        )->style(ButtonStyle::tryFrom($value['style'] ?? 'neutral') ?? ButtonStyle::Neutral)
                        : $value
                ),
                'negative' => $alert->negative(
                    is_array($value)
                        ? Button::make(
                            $value['id'] ?? throw new \InvalidArgumentException("Button array must have an 'id' key"),
                            $value['label'] ?? throw new \InvalidArgumentException("Button array must have a 'label' key"),
                        )->style(ButtonStyle::tryFrom($value['style'] ?? 'neutral') ?? ButtonStyle::Neutral)
                        : $value
                ),
                'neutral' => $alert->neutral(
                    is_array($value)
                        ? Button::make(
                            $value['id'] ?? throw new \InvalidArgumentException("Button array must have an 'id' key"),
                            $value['label'] ?? throw new \InvalidArgumentException("Button array must have a 'label' key"),
                        )->style(ButtonStyle::tryFrom($value['style'] ?? 'neutral') ?? ButtonStyle::Neutral)
                        : $value
                ),
                'icon' => $alert->icon(is_string($value) ? (Icon::tryFrom($value) ?? $value) : $value),
                'cancelable' => $alert->cancelable($value),
                'id' => $alert->id($value),
                'event' => $alert->event($value),
                default => null,
            };
        }

        return $alert;
    }

    public function sheet(?string $title = null, ?string $message = null): PendingSheet
    {
        $sheet = new PendingSheet($title);

        if ($message !== null) {
            $sheet->message($message);
        }

        return $sheet;
    }
}
