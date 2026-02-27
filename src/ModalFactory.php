<?php

namespace MazerDev\Dialogify;

class ModalFactory
{
    public function confirm(string $message, ?string $title = null): PendingAlert
    {
        return new PendingAlert($title ?? '', $message);
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
