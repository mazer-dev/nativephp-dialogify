<?php

use MazerDev\Dialogify\Button;
use MazerDev\Dialogify\Enums\Duration;
use MazerDev\Dialogify\Enums\Icon;
use MazerDev\Dialogify\Enums\Position;
use MazerDev\Dialogify\PendingToast;

test('PendingToast builds payload with defaults', function () {
    $toast = new PendingToast('Item saved!');

    expect($toast->toPayload())->toBe([
        'message' => 'Item saved!',
        'position' => 'bottom',
        'backgroundColor' => null,
        'textColor' => null,
        'icon' => null,
        'duration' => 'long',
        'cornerRadius' => null,
        'action' => null,
        'id' => null,
    ]);
});

test('PendingToast supports position()', function () {
    $toast = new PendingToast('Hello');
    $toast->position(Position::Top);

    expect($toast->toPayload()['position'])->toBe('top');
});

test('PendingToast supports colors', function () {
    $toast = new PendingToast('Hello');
    $toast->backgroundColor('#16a34a')->textColor('#ffffff');

    $payload = $toast->toPayload();

    expect($payload['backgroundColor'])->toBe('#16a34a');
    expect($payload['textColor'])->toBe('#ffffff');
});

test('PendingToast supports icon with enum', function () {
    $toast = new PendingToast('Done');
    $toast->icon(Icon::CheckCircle);

    expect($toast->toPayload()['icon'])->toBe('check_circle');
});

test('PendingToast supports icon with string', function () {
    $toast = new PendingToast('Done');
    $toast->icon('custom_icon');

    expect($toast->toPayload()['icon'])->toBe('custom_icon');
});

test('PendingToast supports duration with enum', function () {
    $toast = new PendingToast('Quick');
    $toast->duration(Duration::Short);

    expect($toast->toPayload()['duration'])->toBe('short');
});

test('PendingToast supports duration with int (ms)', function () {
    $toast = new PendingToast('Custom');
    $toast->duration(3000);

    expect($toast->toPayload()['duration'])->toBe(3000);
});

test('PendingToast supports cornerRadius', function () {
    $toast = new PendingToast('Round');
    $toast->cornerRadius(16);

    expect($toast->toPayload()['cornerRadius'])->toBe(16);
});

test('PendingToast supports action button', function () {
    $toast = new PendingToast('Deleted');
    $toast->action(Button::make('undo', 'Undo'));

    expect($toast->toPayload()['action'])->toBe([
        'id' => 'undo',
        'label' => 'Undo',
        'style' => 'neutral',
        'color' => null,
        'backgroundColor' => null,
    ]);
});

test('PendingToast supports id()', function () {
    $toast = new PendingToast('Test');
    $toast->id('my-toast');

    expect($toast->toPayload()['id'])->toBe('my-toast');
});

test('PendingToast is fluent', function () {
    $toast = (new PendingToast('Test'))
        ->position(Position::Top)
        ->backgroundColor('#000')
        ->textColor('#fff')
        ->icon(Icon::Info)
        ->duration(Duration::Short)
        ->cornerRadius(8)
        ->id('test');

    expect($toast)->toBeInstanceOf(PendingToast::class);
});

test('PendingToast show() is safe outside native runtime', function () {
    $toast = new PendingToast('Test');
    $toast->show();

    expect(true)->toBeTrue();
});
