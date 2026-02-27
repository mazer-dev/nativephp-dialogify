<?php

use MazerDev\Dialogify\Events\ButtonPressed;
use MazerDev\Dialogify\Events\InputSubmitted;
use MazerDev\Dialogify\Events\ToastAction;

test('ButtonPressed event stores properties', function () {
    $event = new ButtonPressed(
        buttonId: 'delete',
        label: 'Delete',
        index: 1,
        dialogId: 'confirm-delete',
    );

    expect($event->buttonId)->toBe('delete');
    expect($event->label)->toBe('Delete');
    expect($event->index)->toBe(1);
    expect($event->dialogId)->toBe('confirm-delete');
});

test('ButtonPressed dialogId defaults to null', function () {
    $event = new ButtonPressed(buttonId: 'ok', label: 'OK', index: 0);

    expect($event->dialogId)->toBeNull();
});

test('ToastAction event stores properties', function () {
    $event = new ToastAction(
        buttonId: 'undo',
        label: 'Undo',
        toastId: 'delete-toast',
    );

    expect($event->buttonId)->toBe('undo');
    expect($event->label)->toBe('Undo');
    expect($event->toastId)->toBe('delete-toast');
});

test('InputSubmitted event stores properties', function () {
    $event = new InputSubmitted(
        buttonId: 'verify',
        label: 'Verify',
        dialogId: 'mfa-verify',
        inputs: ['code' => '123456'],
    );

    expect($event->buttonId)->toBe('verify');
    expect($event->label)->toBe('Verify');
    expect($event->dialogId)->toBe('mfa-verify');
    expect($event->inputs)->toBe(['code' => '123456']);
});

test('InputSubmitted inputs defaults to empty array', function () {
    $event = new InputSubmitted(buttonId: 'ok', label: 'OK');

    expect($event->inputs)->toBe([]);
});
