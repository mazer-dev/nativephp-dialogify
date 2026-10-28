# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2026-10-27

### Security

- `ButtonPressed`, `ToastAction` and `InputSubmitted` implement `Native\Mobile\Events\Contracts\DispatchableFromNative`, which the `/_native/api/events` endpoint requires since NativePHP Mobile 3.3.11 and 4.6.1 ([GHSA-83jv-8h7x-287p](https://github.com/NativePHP/mobile-air/security/advisories/GHSA-83jv-8h7x-287p)). Without it, Laravel listeners for these events stop receiving them on patched NativePHP versions.

### Changed

- Requires `nativephp/mobile` `^3.3.11|^4.6.1`. Older 3.x versions lack the marker interface and are affected by the advisories above.
- Custom event classes passed to `->event()` must implement `DispatchableFromNative` to reach Laravel listeners.

## [1.0.0] - 2026-03-01

### Added

- **Toast notifications** with position (top/bottom/center), background color, text color, icon, duration (short/long/custom ms), corner radius, and action button
- **Alert dialogs** with title, message, up to 3 buttons (positive/negative/neutral), icon, cancelable option, form inputs, and custom event class
- **Bottom sheets** with title, message, unlimited buttons, form inputs, cancelable option, and custom event class
- **Button** value object with style shortcuts (`primary()`, `destructive()`, `neutral()`, `cancel()`) and custom colors
- **Input** value object supporting 10 types: text, number, password, email, date, time, datetime, select, checkbox, radio — with placeholder, default value, required, maxLength, options, minDate, maxDate, and minuteInterval
- **Events**: `ButtonPressed`, `ToastAction`, `InputSubmitted` with full payload data
- **Fluent API** with auto-show on destruct — no need to call `show()` explicitly
- **Options array** alternative syntax for toast and alert creation
- **Android implementation** (Kotlin) using Material Design components
- **iOS implementation** (Swift) using native UIKit
- **Dialogify facade** with `toast()`, `alert()`, and `sheet()` methods
- Auto-registering service provider via Laravel package discovery

### Platform Details

- Android: min SDK 24, uses `com.google.android.material:material:1.12.0`
- iOS: min version 15.0, no external dependencies

[1.0.1]: https://github.com/mazer-dev/nativephp-dialogify/releases/tag/1.0.1
[1.0.0]: https://github.com/mazer-dev/nativephp-dialogify/releases/tag/v1.0.0
