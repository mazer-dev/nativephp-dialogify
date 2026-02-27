<?php

use MazerDev\Dialogify\Button;
use MazerDev\Dialogify\Enums\InputType;
use MazerDev\Dialogify\Input;
use MazerDev\Dialogify\PendingSheet;

test('PendingSheet builds payload with defaults', function () {
    $sheet = new PendingSheet('Choose');

    $payload = $sheet->toPayload();

    expect($payload['title'])->toBe('Choose');
    expect($payload['message'])->toBeNull();
    expect($payload['buttons'])->toBe([]);
    expect($payload['inputs'])->toBe([]);
    expect($payload['cancelable'])->toBeTrue();
    expect($payload['id'])->toBeNull();
    expect($payload['event'])->toBeNull();
});

test('PendingSheet supports message()', function () {
    $sheet = new PendingSheet('Pick');
    $sheet->message('Select an option');

    expect($sheet->toPayload()['message'])->toBe('Select an option');
});

test('PendingSheet supports unlimited buttons', function () {
    $sheet = new PendingSheet('Options');
    $sheet->addButton(Button::make('a', 'Option A'))
        ->addButton(Button::make('b', 'Option B'))
        ->addButton(Button::make('c', 'Option C'))
        ->addButton(Button::make('d', 'Option D'))
        ->addButton(Button::make('e', 'Option E'));

    expect($sheet->toPayload()['buttons'])->toHaveCount(5);
});

test('PendingSheet supports inputs', function () {
    $sheet = new PendingSheet('Schedule');
    $sheet->addInput(Input::make('date', 'Date')->type(InputType::Date))
        ->addInput(Input::make('time', 'Time')->type(InputType::Time));

    expect($sheet->toPayload()['inputs'])->toHaveCount(2);
});

test('PendingSheet supports cancelable', function () {
    $sheet = new PendingSheet('Required');
    $sheet->cancelable(false);

    expect($sheet->toPayload()['cancelable'])->toBeFalse();
});

test('PendingSheet supports id()', function () {
    $sheet = new PendingSheet('Test');
    $sheet->id('my-sheet');

    expect($sheet->toPayload()['id'])->toBe('my-sheet');
});

test('PendingSheet title is optional', function () {
    $sheet = new PendingSheet;

    expect($sheet->toPayload()['title'])->toBeNull();
});

test('PendingSheet is fluent', function () {
    $sheet = (new PendingSheet('T'))
        ->message('M')
        ->addButton(Button::make('a', 'A'))
        ->addInput(Input::make('x', 'X'))
        ->cancelable(false)
        ->id('test');

    expect($sheet)->toBeInstanceOf(PendingSheet::class);
});

test('PendingSheet show() is safe outside native runtime', function () {
    $sheet = new PendingSheet('Test');
    $sheet->show();

    expect(true)->toBeTrue();
});
