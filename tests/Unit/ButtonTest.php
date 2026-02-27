<?php

use MazerDev\Dialogify\Button;
use MazerDev\Dialogify\Enums\ButtonStyle;

test('Button can be created with make()', function () {
    $button = Button::make('delete', 'Delete');

    expect($button->toArray())->toBe([
        'id' => 'delete',
        'label' => 'Delete',
        'style' => 'neutral',
        'color' => null,
        'backgroundColor' => null,
    ]);
});

test('Button supports style shortcuts', function () {
    expect(Button::make('ok', 'OK')->primary()->toArray()['style'])->toBe('primary');
    expect(Button::make('del', 'Delete')->destructive()->toArray()['style'])->toBe('destructive');
    expect(Button::make('c', 'Cancel')->cancel()->toArray()['style'])->toBe('cancel');
    expect(Button::make('n', 'Maybe')->neutral()->toArray()['style'])->toBe('neutral');
});

test('Button supports custom colors', function () {
    $button = Button::make('save', 'Save')
        ->color('#ffffff')
        ->backgroundColor('#16a34a');

    $array = $button->toArray();

    expect($array['color'])->toBe('#ffffff');
    expect($array['backgroundColor'])->toBe('#16a34a');
});

test('Button supports style() method with enum', function () {
    $button = Button::make('del', 'Delete')->style(ButtonStyle::Destructive);

    expect($button->toArray()['style'])->toBe('destructive');
});

test('Button is fluent', function () {
    $button = Button::make('x', 'X')
        ->destructive()
        ->color('#ff0000')
        ->backgroundColor('#000000');

    expect($button)->toBeInstanceOf(Button::class);
});
