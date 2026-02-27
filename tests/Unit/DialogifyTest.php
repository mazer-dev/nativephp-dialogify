<?php

use MazerDev\Dialogify\Dialogify;
use MazerDev\Dialogify\PendingAlert;
use MazerDev\Dialogify\PendingSheet;
use MazerDev\Dialogify\PendingToast;

test('Dialogify::toast() returns PendingToast', function () {
    $dialogify = new Dialogify;

    $toast = $dialogify->toast('Hello');

    expect($toast)->toBeInstanceOf(PendingToast::class);
    expect($toast->toPayload()['message'])->toBe('Hello');
});

test('Dialogify::toast() with array options applies them', function () {
    $dialogify = new Dialogify;

    $toast = $dialogify->toast('Hello', [
        'position' => 'top',
        'backgroundColor' => '#16a34a',
        'duration' => 'short',
    ]);

    $payload = $toast->toPayload();

    expect($payload['position'])->toBe('top');
    expect($payload['backgroundColor'])->toBe('#16a34a');
    expect($payload['duration'])->toBe('short');
});

test('Dialogify::alert() returns PendingAlert', function () {
    $dialogify = new Dialogify;

    $alert = $dialogify->alert('Title', 'Message');

    expect($alert)->toBeInstanceOf(PendingAlert::class);
    expect($alert->toPayload()['title'])->toBe('Title');
});

test('Dialogify::alert() with array options applies buttons', function () {
    $dialogify = new Dialogify;

    $alert = $dialogify->alert('Confirm', 'Sure?', [
        'positive' => ['id' => 'ok', 'label' => 'OK'],
        'negative' => ['id' => 'cancel', 'label' => 'Cancel'],
        'id' => 'my-alert',
    ]);

    $payload = $alert->toPayload();

    expect($payload['positive']['id'])->toBe('ok');
    expect($payload['negative']['id'])->toBe('cancel');
    expect($payload['id'])->toBe('my-alert');
});

test('Dialogify::sheet() returns PendingSheet', function () {
    $dialogify = new Dialogify;

    $sheet = $dialogify->sheet('Options');

    expect($sheet)->toBeInstanceOf(PendingSheet::class);
    expect($sheet->toPayload()['title'])->toBe('Options');
});

test('Dialogify::sheet() with message argument', function () {
    $dialogify = new Dialogify;

    $sheet = $dialogify->sheet('Title', 'Pick one');

    $payload = $sheet->toPayload();

    expect($payload['title'])->toBe('Title');
    expect($payload['message'])->toBe('Pick one');
});
