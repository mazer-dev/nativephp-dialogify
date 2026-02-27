<?php

use MazerDev\Dialogify\Enums\InputType;
use MazerDev\Dialogify\Input;

test('Input can be created with make()', function () {
    $input = Input::make('name', 'Name');

    expect($input->toArray())->toBe([
        'id' => 'name',
        'label' => 'Name',
        'type' => 'text',
        'placeholder' => null,
        'defaultValue' => null,
        'required' => false,
        'maxLength' => null,
        'options' => null,
        'minDate' => null,
        'maxDate' => null,
        'minuteInterval' => null,
    ]);
});

test('Input supports type()', function () {
    $input = Input::make('email', 'Email')->type(InputType::Email);

    expect($input->toArray()['type'])->toBe('email');
});

test('Input supports text field options', function () {
    $input = Input::make('code', 'Code')
        ->type(InputType::Number)
        ->placeholder('123456')
        ->maxLength(6)
        ->required();

    $array = $input->toArray();

    expect($array['type'])->toBe('number');
    expect($array['placeholder'])->toBe('123456');
    expect($array['maxLength'])->toBe(6);
    expect($array['required'])->toBeTrue();
});

test('Input supports select/radio/checkbox options', function () {
    $input = Input::make('format', 'Format')
        ->type(InputType::Select)
        ->options(['pdf' => 'PDF', 'csv' => 'CSV'])
        ->defaultValue('pdf');

    $array = $input->toArray();

    expect($array['options'])->toBe(['pdf' => 'PDF', 'csv' => 'CSV']);
    expect($array['defaultValue'])->toBe('pdf');
});

test('Input supports date/time constraints', function () {
    $input = Input::make('date', 'Date')
        ->type(InputType::Date)
        ->minDate('2026-01-01')
        ->maxDate('2026-12-31');

    $array = $input->toArray();

    expect($array['minDate'])->toBe('2026-01-01');
    expect($array['maxDate'])->toBe('2026-12-31');
});

test('Input supports minuteInterval for time', function () {
    $input = Input::make('time', 'Time')
        ->type(InputType::Time)
        ->minuteInterval(15);

    expect($input->toArray()['minuteInterval'])->toBe(15);
});

test('Input supports checkbox defaultValue as array', function () {
    $input = Input::make('channels', 'Channels')
        ->type(InputType::Checkbox)
        ->options(['email' => 'Email', 'sms' => 'SMS', 'push' => 'Push'])
        ->defaultValue(['email', 'push']);

    expect($input->toArray()['defaultValue'])->toBe(['email', 'push']);
});

test('Input is fluent', function () {
    $input = Input::make('x', 'X')
        ->type(InputType::Text)
        ->placeholder('...')
        ->required()
        ->maxLength(100);

    expect($input)->toBeInstanceOf(Input::class);
});
