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

## Compatibility

Dialogify works with all NativePHP-supported frontend stacks:

| Stack | Status | Notes |
|---|---|---|
| Livewire v3 | Tested | Full support via `#[OnNative]` event attributes |
| Livewire v4 | Tested | Full support via `#[OnNative]` event attributes |
| Inertia + Vue | Supported | Use bridge calls from JavaScript (see [JavaScript Usage](#javascript-usage)) |
| Inertia + React | Supported | Use bridge calls from JavaScript (see [JavaScript Usage](#javascript-usage)) |

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

The service provider auto-registers via Laravel package discovery — no manual registration needed.

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

Dialogify dispatches Laravel events when users interact with dialogs. All events use `Dispatchable` and `SerializesModels` traits.

### ButtonPressed

Fired when a button is pressed on an Alert or Sheet.

```php
use MazerDev\Dialogify\Events\ButtonPressed;

// Constructor signature:
// public function __construct(
//     public string  $buttonId,   // The button's ID
//     public string  $label,      // The button's label text
//     public int     $index,      // The button's position index
//     public ?string $dialogId,   // The dialog's ID (if set)
// )
```

### ToastAction

Fired when a toast's action button is tapped.

```php
use MazerDev\Dialogify\Events\ToastAction;

// Constructor signature:
// public function __construct(
//     public string  $buttonId,  // The action button's ID
//     public string  $label,     // The action button's label
//     public ?string $toastId,   // The toast's ID (if set)
// )
```

### InputSubmitted

Fired when a dialog with inputs is submitted.

```php
use MazerDev\Dialogify\Events\InputSubmitted;

// Constructor signature:
// public function __construct(
//     public string  $buttonId,   // The button that triggered submission
//     public string  $label,      // The button's label
//     public ?string $dialogId,   // The dialog's ID (if set)
//     public array   $inputs,     // Key-value pairs: input ID => submitted value
// )
```

### Listening to Events

**With Livewire and `#[OnNative]` (recommended):**

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

**With a custom event class:**

You can use the `->event()` method on alerts and sheets to dispatch your own event class instead of the defaults:

```php
// In your dialog setup
Dialogify::alert('Edit Profile')
    ->addInput(Input::make('name', 'Name'))
    ->positive(Button::make('save', 'Save')->primary())
    ->event(ProfileUpdated::class)
    ->show();

// Your custom event class receives the same payload
```

## JavaScript Usage

Dialogify is a PHP-first plugin. The primary API is the PHP Facade, and all events flow back through Laravel's event system.

For **SPA frameworks** (Inertia + Vue/React), you can call bridge functions directly via the NativePHP JavaScript bridge:

### Bridge Function Calls

```js
// Toast
window.__nativephp.bridgeCall('Dialogify.Toast', JSON.stringify({
    message: 'Hello from JS',
    position: 'bottom',
    duration: 'long',
    backgroundColor: '#1a1a2e',
    textColor: '#ffffff',
    icon: 'check_circle',
    cornerRadius: 12,
    id: 'js-toast',
    action: { id: 'undo', label: 'Undo' },
}));

// Alert
window.__nativephp.bridgeCall('Dialogify.Alert', JSON.stringify({
    title: 'Confirm',
    message: 'Are you sure?',
    positive: { id: 'yes', label: 'Yes', style: 'primary' },
    negative: { id: 'no', label: 'No', style: 'cancel' },
    cancelable: true,
    icon: 'warning',
    id: 'confirm-alert',
}));

// Sheet
window.__nativephp.bridgeCall('Dialogify.Sheet', JSON.stringify({
    title: 'Actions',
    message: 'Choose an option',
    buttons: [
        { id: 'edit', label: 'Edit', style: 'primary' },
        { id: 'delete', label: 'Delete', style: 'destructive' },
    ],
    cancelable: true,
    id: 'action-sheet',
}));
```

### Listening for Events in JavaScript

Native events are dispatched to the DOM as `CustomEvent` objects:

```js
document.addEventListener('native-event', (e) => {
    const { event, payload } = e.detail;

    if (event === 'MazerDev\\Dialogify\\Events\\ButtonPressed') {
        console.log('Button pressed:', payload.buttonId);
    }

    if (event === 'MazerDev\\Dialogify\\Events\\InputSubmitted') {
        console.log('Inputs:', payload.inputs);
    }
});
```

If Livewire is loaded, events are also dispatched via `window.Livewire.dispatch()`.

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
| `duration()` | `Duration\|int` | Set display duration (enum or milliseconds) |
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

**Duration:** `Short` (2s), `Long` (3.5s) — or pass an `int` for custom milliseconds

**ButtonStyle:** `Primary`, `Destructive`, `Neutral`, `Cancel`

**InputType:** `Text`, `Number`, `Password`, `Email`, `Date`, `Time`, `DateTime`, `Select`, `Checkbox`, `Radio`

**Icon:** `CheckCircle`, `Warning`, `Error`, `Info`, `Check`, `Delete`, `Close`, `QuestionMark`

Icons map to Material Icons (Android) and SF Symbols (iOS) automatically.

### Events

| Event | Properties | Dispatched When |
|---|---|---|
| `ButtonPressed` | `buttonId`, `label`, `index`, `dialogId` | Alert/Sheet button pressed |
| `ToastAction` | `buttonId`, `label`, `toastId` | Toast action button tapped |
| `InputSubmitted` | `buttonId`, `label`, `dialogId`, `inputs` | Form dialog submitted |

### Bridge Functions

These are the raw bridge function names registered in `nativephp.json`, used for JavaScript calls:

| Bridge Function | Description |
|---|---|
| `Dialogify.Toast` | Show a customizable toast notification |
| `Dialogify.Alert` | Show a customizable alert dialog |
| `Dialogify.Sheet` | Show a customizable bottom sheet |

## Platform Support

| Platform | Implementation | Native Framework | Status |
|---|---|---|---|
| Android | Kotlin | Material Design Components | Fully implemented |
| iOS | Swift | UIKit | Fully implemented |

### Android Details
- Min SDK: 24 (Android 7.0)
- Dependency: `com.google.android.material:material:1.12.0` (added automatically via `nativephp.json`)
- Icons: Material Icons

### iOS Details
- Min version: iOS 15.0
- No external dependencies (uses UIKit and SF Symbols)
- Icons: SF Symbols

## Test App

A demo app showcasing all Dialogify features is available for testing:

- **iOS (TestFlight):** [Coming soon](#)
- **Android (Google Play Testing):** [Coming soon](#)

## Testing

Run the test suite:

```bash
composer test
# or
vendor/bin/pest
```

Tests cover facade methods, builder serialization, event dispatching, and input validation.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a detailed version history.

## Upgrading

Dialogify follows [Semantic Versioning](https://semver.org/). Within a major version (e.g., all 1.x releases), there are no breaking changes — only new features and bug fixes. Your license covers all updates within the purchased major version.

When a new major version is released, a dedicated upgrade guide will be published.

## Roadmap

Dialogify is actively evolving. Upcoming v1.x releases will introduce new capabilities — including countdown timers, progress indicators, and more. All future v1.x updates are included with your license at no extra cost.

## Support

- **Email:** support@mazer.dev
- **GitHub Discussions:** [Ask questions or share feedback](https://github.com/mazer-dev/nativephp-dialogify/discussions)
- **GitHub Issues:** [Report bugs](https://github.com/mazer-dev/nativephp-dialogify/issues)

## License

Proprietary License. See [LICENSE](LICENSE) for details.

This is a commercial product. Each license grants usage rights for a specific
major version. Redistribution is not permitted.
