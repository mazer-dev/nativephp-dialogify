# Dialogify for NativePHP Mobile

[![Latest Version](https://img.shields.io/packagist/v/mazer-dev/nativephp-dialogify.svg)](https://packagist.org/packages/mazer-dev/nativephp-dialogify)
[![License: Proprietary](https://img.shields.io/badge/License-Proprietary-red.svg)](LICENSE)

Native dialogs, fully featured.

A complete native communication hub for your NativePHP Mobile app — toast notifications, alert dialogs, and bottom sheets with rich form inputs, custom styling, and event-driven responses. Crafted in Kotlin and Swift for a truly fluid user experience.

## Purchase

**[Buy a license](#)** to get started.

Each license covers a single major version (e.g., all v1.x releases including minor and patch updates). Existing customers get exclusive upgrade discounts when new major versions are released.

## Features

- **Toast Notifications** — Position, colors, icons, duration, corner radius, action button
- **Alert Dialogs** — Title, message, up to 3 buttons (positive/negative/neutral), form inputs, icons
- **Bottom Sheets** — Unlimited buttons, form inputs, cancelable
- **Form Inputs** — Text, number, password, email, date, time, datetime, select, checkbox, radio
- **Fluent PHP API** — Chain methods, auto-show on destruct
- **Event-driven** — `ButtonPressed`, `ToastAction`, `InputSubmitted` events with full payload
- **Custom styling** — Colors, icons, button styles (primary, destructive, neutral, cancel)

## Requirements

- PHP >= 8.2
- NativePHP Mobile ^3.0
- Android min SDK 24
- iOS >= 15.0

## Installation

Add the Anystack repository to your `composer.json`:

```json
"repositories": [
    {
        "type": "composer",
        "url": "https://satis.anystack.sh"
    }
]
```

Configure your license key:

```bash
composer config http-basic.satis.anystack.sh your-license-key ""
```

Install:

```bash
composer require mazer-dev/nativephp-dialogify
```

The service provider auto-registers via Laravel package discovery.

## Usage

### Toast

```php
use MazerDev\Dialogify\Facades\Dialogify;
use MazerDev\Dialogify\Enums\Position;
use MazerDev\Dialogify\Enums\Duration;
use MazerDev\Dialogify\Enums\Icon;
use MazerDev\Dialogify\Button;

// Simple toast — auto-shows when variable goes out of scope
Dialogify::toast('Record saved!');

// Customized toast
Dialogify::toast('Operation complete')
    ->position(Position::Top)
    ->backgroundColor('#1a1a2e')
    ->textColor('#ffffff')
    ->icon(Icon::CheckCircle)
    ->duration(Duration::Long)
    ->cornerRadius(12)
    ->id('save-toast')
    ->show();

// Toast with action button
Dialogify::toast('Item deleted')
    ->action(Button::make('undo', 'Undo'))
    ->show();
```

### Alert

```php
use MazerDev\Dialogify\Facades\Dialogify;
use MazerDev\Dialogify\Button;
use MazerDev\Dialogify\Input;
use MazerDev\Dialogify\Enums\Icon;
use MazerDev\Dialogify\Enums\InputType;

// Simple alert
Dialogify::alert('Warning', 'Are you sure?');

// Alert with buttons
Dialogify::alert('Delete Record', 'This action cannot be undone.')
    ->positive(Button::make('confirm', 'Delete')->destructive())
    ->negative(Button::make('cancel', 'Cancel')->cancel())
    ->icon(Icon::Warning)
    ->cancelable(false)
    ->id('delete-alert')
    ->show();

// Alert with form inputs
Dialogify::alert('Edit Profile')
    ->addInput(Input::make('name', 'Name')->placeholder('Enter name')->required())
    ->addInput(Input::make('email', 'Email')->type(InputType::Email))
    ->positive(Button::make('save', 'Save')->primary())
    ->event(MyCustomEvent::class)
    ->show();
```

### Sheet

```php
use MazerDev\Dialogify\Facades\Dialogify;
use MazerDev\Dialogify\Button;
use MazerDev\Dialogify\Input;
use MazerDev\Dialogify\Enums\InputType;

// Bottom sheet with buttons
Dialogify::sheet('Choose Action')
    ->message('Select what you want to do')
    ->addButton(Button::make('edit', 'Edit')->primary())
    ->addButton(Button::make('share', 'Share'))
    ->addButton(Button::make('delete', 'Delete')->destructive())
    ->cancelable(true)
    ->id('action-sheet')
    ->show();

// Sheet with form inputs
Dialogify::sheet('Feedback')
    ->addInput(
        Input::make('rating', 'Rating')
            ->type(InputType::Select)
            ->options(['5' => 'Excellent', '4' => 'Good', '3' => 'Average'])
    )
    ->addInput(Input::make('comment', 'Comment')->placeholder('Your feedback'))
    ->addButton(Button::make('submit', 'Submit')->primary())
    ->show();
```

### Options Array (Alternative Syntax)

Toast and Alert also accept an options array for dynamic configuration:

```php
Dialogify::toast('Hello', [
    'position' => 'top',
    'backgroundColor' => '#333',
    'duration' => 'short',
    'icon' => 'check_circle',
]);

Dialogify::alert('Confirm', 'Proceed?', [
    'positive' => ['id' => 'ok', 'label' => 'OK', 'style' => 'primary'],
    'cancelable' => false,
]);
```

## Event Handling

Dialogify dispatches Laravel events when users interact with dialogs.

### ButtonPressed

Fired when a button is pressed on an Alert or Sheet.

```php
use MazerDev\Dialogify\Events\ButtonPressed;

// Properties:
// - string $buttonId   — The button's ID
// - string $label      — The button's label text
// - int    $index      — The button's position index
// - ?string $dialogId  — The dialog's ID (if set)
```

### ToastAction

Fired when a toast's action button is tapped.

```php
use MazerDev\Dialogify\Events\ToastAction;

// Properties:
// - string  $buttonId — The action button's ID
// - string  $label    — The action button's label
// - ?string $toastId  — The toast's ID (if set)
```

### InputSubmitted

Fired when a dialog with inputs is submitted.

```php
use MazerDev\Dialogify\Events\InputSubmitted;

// Properties:
// - string  $buttonId — The button that triggered submission
// - string  $label    — The button's label
// - ?string $dialogId — The dialog's ID (if set)
// - array   $inputs   — Key-value pairs of input ID => submitted value
```

### Listening to Events

**With Livewire and `#[OnNative]`:**

```php
use NativePHP\Attributes\OnNative;
use MazerDev\Dialogify\Events\ButtonPressed;
use MazerDev\Dialogify\Events\InputSubmitted;

#[OnNative(ButtonPressed::class)]
public function handleButton(ButtonPressed $event): void
{
    if ($event->buttonId === 'confirm') {
        // Handle confirmation
    }
}

#[OnNative(InputSubmitted::class)]
public function handleInput(InputSubmitted $event): void
{
    $name = $event->inputs['name'] ?? '';
    // Process submitted input
}
```

**With a standard Laravel listener:**

```php
use Illuminate\Support\Facades\Event;
use MazerDev\Dialogify\Events\ToastAction;

Event::listen(ToastAction::class, function (ToastAction $event) {
    if ($event->buttonId === 'undo') {
        // Handle undo action
    }
});
```

## JavaScript Usage

Dialogify is a PHP-first plugin. For SPA frameworks (Inertia + Vue/React), you can call bridge functions directly:

```js
window.__nativephp.bridgeCall('Dialogify.Toast', JSON.stringify({
    message: 'Hello from JS',
    position: 'bottom',
    duration: 'long',
}));

window.__nativephp.bridgeCall('Dialogify.Alert', JSON.stringify({
    title: 'Confirm',
    message: 'Are you sure?',
    positive: { id: 'yes', label: 'Yes', style: 'primary' },
    negative: { id: 'no', label: 'No', style: 'cancel' },
    cancelable: true,
}));
```

## API Reference

### Facade Methods

| Method | Returns | Description |
|---|---|---|
| `toast(string $message, array $options = [])` | `PendingToast` | Create a toast notification |
| `alert(string $title, ?string $message = null, array $options = [])` | `PendingAlert` | Create an alert dialog |
| `sheet(?string $title = null, ?string $message = null)` | `PendingSheet` | Create a bottom sheet |

### PendingToast

| Method | Parameter | Description |
|---|---|---|
| `position()` | `Position` | Set position (Top, Bottom, Center) |
| `backgroundColor()` | `string $hex` | Set background color |
| `textColor()` | `string $hex` | Set text color |
| `icon()` | `Icon\|string` | Set icon |
| `duration()` | `Duration\|int` | Set display duration |
| `cornerRadius()` | `int $dp` | Set corner radius in dp |
| `action()` | `Button` | Set action button |
| `id()` | `string` | Set toast identifier |
| `show()` | — | Display the toast immediately |

### PendingAlert

| Method | Parameter | Description |
|---|---|---|
| `positive()` | `Button` | Set positive (confirm) button |
| `negative()` | `Button` | Set negative (cancel) button |
| `neutral()` | `Button` | Set neutral button |
| `icon()` | `Icon\|string` | Set icon |
| `cancelable()` | `bool` | Allow dismissing by tapping outside (default: true) |
| `addInput()` | `Input` | Add a form input field |
| `id()` | `string` | Set dialog identifier |
| `event()` | `string $eventClass` | Set custom event class for responses |
| `show()` | — | Display the alert immediately |

### PendingSheet

| Method | Parameter | Description |
|---|---|---|
| `message()` | `string` | Set sheet message/description |
| `addButton()` | `Button` | Add a button (unlimited) |
| `addInput()` | `Input` | Add a form input field |
| `cancelable()` | `bool` | Allow dismissing by swiping down (default: true) |
| `id()` | `string` | Set sheet identifier |
| `event()` | `string $eventClass` | Set custom event class for responses |
| `show()` | — | Display the sheet immediately |

### Button

```php
Button::make(string $id, string $label)
    ->style(ButtonStyle $style)  // Or use shortcuts:
    ->primary()
    ->destructive()
    ->neutral()
    ->cancel()
    ->color(string $hex)
    ->backgroundColor(string $hex)
```

### Input

```php
Input::make(string $id, string $label)
    ->type(InputType $type)
    ->placeholder(string $text)
    ->defaultValue(mixed $value)
    ->required(bool $required = true)
    ->maxLength(int $max)
    ->options(array $options)          // For Select, Checkbox, Radio
    ->minDate(string $date)            // For Date, DateTime
    ->maxDate(string $date)            // For Date, DateTime
    ->minuteInterval(int $minutes)     // For Time, DateTime
```

### Enums

**Position:** `Top`, `Bottom`, `Center`

**Duration:** `Short`, `Long`

**ButtonStyle:** `Primary`, `Destructive`, `Neutral`, `Cancel`

**InputType:** `Text`, `Number`, `Password`, `Email`, `Date`, `Time`, `DateTime`, `Select`, `Checkbox`, `Radio`

**Icon:** `CheckCircle`, `Warning`, `Error`, `Info`, `Check`, `Delete`, `Close`, `QuestionMark`

## Platform Support

| Platform | Status |
|---|---|
| Android (Kotlin) | Fully implemented |
| iOS (Swift) | Fully implemented |

No special permissions required.

**Android dependency:** `com.google.android.material:material:1.12.0` (added automatically)

## Test App

A demo app showcasing all Dialogify features is available for testing:

- **iOS (TestFlight):** [Coming soon](#)
- **Android (Google Play Testing):** [Coming soon](#)

## Testing

```bash
composer test
# or
vendor/bin/pest
```

## Roadmap

Dialogify is actively evolving. Upcoming v1.x releases will introduce new capabilities — including countdown timers, progress indicators, and more. All future v1.x updates are included with your license at no extra cost.

## Support

- **GitHub Discussions:** [Ask questions or share feedback](https://github.com/mazer-dev/nativephp-dialogify/discussions)
- **GitHub Issues:** [Report bugs](https://github.com/mazer-dev/nativephp-dialogify/issues)
- **Email:** support@mazer.dev

## License

Proprietary License. See [LICENSE](LICENSE) for details.

This is a commercial product. Each license grants usage rights for a specific
major version. Redistribution is not permitted.
