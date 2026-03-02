<?php

use MazerDev\Dialogify\Enums\ButtonStyle;
use MazerDev\Dialogify\Enums\Duration;
use MazerDev\Dialogify\Enums\Icon;
use MazerDev\Dialogify\Enums\InputType;
use MazerDev\Dialogify\Enums\Position;

test('Position enum has expected cases', function () {
    expect(Position::Top->value)->toBe('top');
    expect(Position::Bottom->value)->toBe('bottom');
    expect(Position::Center->value)->toBe('center');
});

test('Duration enum has expected cases', function () {
    expect(Duration::Short->value)->toBe('short');
    expect(Duration::Long->value)->toBe('long');
});

test('ButtonStyle enum has expected cases', function () {
    expect(ButtonStyle::Primary->value)->toBe('primary');
    expect(ButtonStyle::Destructive->value)->toBe('destructive');
    expect(ButtonStyle::Neutral->value)->toBe('neutral');
    expect(ButtonStyle::Cancel->value)->toBe('cancel');
});

test('InputType enum has expected cases', function () {
    expect(InputType::Text->value)->toBe('text');
    expect(InputType::Number->value)->toBe('number');
    expect(InputType::Password->value)->toBe('password');
    expect(InputType::Email->value)->toBe('email');
    expect(InputType::Date->value)->toBe('date');
    expect(InputType::Time->value)->toBe('time');
    expect(InputType::DateTime->value)->toBe('datetime');
    expect(InputType::Select->value)->toBe('select');
    expect(InputType::Checkbox->value)->toBe('checkbox');
    expect(InputType::Radio->value)->toBe('radio');
});

test('Icon enum has expected cases', function () {
    expect(Icon::CheckCircle->value)->toBe('check_circle');
    expect(Icon::Warning->value)->toBe('warning');
    expect(Icon::Error->value)->toBe('error');
    expect(Icon::Info->value)->toBe('info');
    expect(Icon::Check->value)->toBe('check');
    expect(Icon::Delete->value)->toBe('delete');
    expect(Icon::Close->value)->toBe('close');
    expect(Icon::QuestionMark->value)->toBe('help');
});
