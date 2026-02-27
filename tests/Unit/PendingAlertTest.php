<?php

use MazerDev\Dialogify\Button;
use MazerDev\Dialogify\Enums\Icon;
use MazerDev\Dialogify\Enums\InputType;
use MazerDev\Dialogify\Input;
use MazerDev\Dialogify\PendingAlert;

test('PendingAlert builds payload with defaults', function () {
    $alert = new PendingAlert('Title', 'Message');

    $payload = $alert->toPayload();

    expect($payload['title'])->toBe('Title');
    expect($payload['message'])->toBe('Message');
    expect($payload['positive'])->toBeNull();
    expect($payload['negative'])->toBeNull();
    expect($payload['neutral'])->toBeNull();
    expect($payload['icon'])->toBeNull();
    expect($payload['cancelable'])->toBeTrue();
    expect($payload['inputs'])->toBe([]);
    expect($payload['id'])->toBeNull();
    expect($payload['event'])->toBeNull();
});

test('PendingAlert supports buttons', function () {
    $alert = new PendingAlert('Confirm', 'Sure?');
    $alert->positive(Button::make('delete', 'Delete')->destructive())
        ->negative(Button::make('cancel', 'Cancel'));

    $payload = $alert->toPayload();

    expect($payload['positive']['id'])->toBe('delete');
    expect($payload['positive']['style'])->toBe('destructive');
    expect($payload['negative']['id'])->toBe('cancel');
    expect($payload['neutral'])->toBeNull();
});

test('PendingAlert supports neutral button', function () {
    $alert = new PendingAlert('Choose', 'Pick one');
    $alert->positive(Button::make('a', 'A'))
        ->negative(Button::make('b', 'B'))
        ->neutral(Button::make('c', 'C'));

    expect($alert->toPayload()['neutral']['id'])->toBe('c');
});

test('PendingAlert supports icon', function () {
    $alert = new PendingAlert('Warning', 'Careful');
    $alert->icon(Icon::Warning);

    expect($alert->toPayload()['icon'])->toBe('warning');
});

test('PendingAlert supports cancelable', function () {
    $alert = new PendingAlert('Important', 'Cannot dismiss');
    $alert->cancelable(false);

    expect($alert->toPayload()['cancelable'])->toBeFalse();
});

test('PendingAlert supports inputs', function () {
    $alert = new PendingAlert('Verify', 'Enter code');
    $alert->addInput(
        Input::make('code', 'Code')
            ->type(InputType::Number)
            ->maxLength(6)
            ->required()
    );

    $payload = $alert->toPayload();

    expect($payload['inputs'])->toHaveCount(1);
    expect($payload['inputs'][0]['id'])->toBe('code');
    expect($payload['inputs'][0]['type'])->toBe('number');
});

test('PendingAlert supports multiple inputs', function () {
    $alert = new PendingAlert('Login', 'Enter credentials');
    $alert->addInput(Input::make('user', 'Username'))
        ->addInput(Input::make('pass', 'Password')->type(InputType::Password));

    expect($alert->toPayload()['inputs'])->toHaveCount(2);
});

test('PendingAlert supports id()', function () {
    $alert = new PendingAlert('Test', 'Test');
    $alert->id('my-alert');

    expect($alert->toPayload()['id'])->toBe('my-alert');
});

test('PendingAlert supports custom event class', function () {
    $alert = new PendingAlert('Test', 'Test');
    $alert->event('App\\Events\\CustomEvent');

    expect($alert->toPayload()['event'])->toBe('App\\Events\\CustomEvent');
});

test('PendingAlert message is optional', function () {
    $alert = new PendingAlert('Title Only');

    expect($alert->toPayload()['message'])->toBeNull();
});

test('PendingAlert is fluent', function () {
    $alert = (new PendingAlert('T', 'M'))
        ->positive(Button::make('ok', 'OK'))
        ->negative(Button::make('no', 'No'))
        ->icon(Icon::Info)
        ->cancelable(false)
        ->id('test');

    expect($alert)->toBeInstanceOf(PendingAlert::class);
});

test('PendingAlert show() is safe outside native runtime', function () {
    $alert = new PendingAlert('Test', 'Test');
    $alert->show();

    expect(true)->toBeTrue();
});
