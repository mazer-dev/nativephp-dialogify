<?php

namespace MazerDev\Dialogify;

class ToastFactory
{
    public function make(string $message): PendingToast
    {
        return new PendingToast($message);
    }
}
