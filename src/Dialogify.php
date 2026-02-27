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
                'position' => $toast->position(Position::from($value)),
                'backgroundColor' => $toast->backgroundColor($value),
                'textColor' => $toast->textColor($value),
                'icon' => $toast->icon(is_string($value) ? (Icon::tryFrom($value) ?? $value) : $value),
                'duration' => $toast->duration(is_int($value) ? $value : Duration::from($value)),
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
                        ? Button::make($value['id'], $value['label'])->style(ButtonStyle::from($value['style'] ?? 'neutral'))
                        : $value
                ),
                'negative' => $alert->negative(
                    is_array($value)
                        ? Button::make($value['id'], $value['label'])->style(ButtonStyle::from($value['style'] ?? 'neutral'))
                        : $value
                ),
                'neutral' => $alert->neutral(
                    is_array($value)
                        ? Button::make($value['id'], $value['label'])->style(ButtonStyle::from($value['style'] ?? 'neutral'))
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
