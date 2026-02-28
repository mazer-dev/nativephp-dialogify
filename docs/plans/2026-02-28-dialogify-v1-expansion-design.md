# Dialogify v1 Expansion — Design Document

## Overview

Expand nativephp-dialogify from "native dialogs" into a comprehensive **"User Communication Hub"** for NativePHP Mobile. All features render natively on Android (Kotlin) and iOS (Swift) with cross-platform parity.

## Release Strategy

Incremental minor releases within v1.x. Each release gets its own feature branch, merged to main, tagged, and promoted on social media.

| Release | Branch | Content | Depends on |
|---|---|---|---|
| **v1.0** | `main` (tag) | Toasts + Alerts + Sheets (current) | — |
| **v1.1** | `feature/v1.1-toast-countdown` | Toast: countdown, showTimer | v1.0 |
| **v1.2** | `feature/v1.2-alert-countdown` | Alert: countdown, enableAfterCountdown, progress info | v1.1 |
| **v1.3** | `feature/v1.3-sheet-countdown` | Sheet: countdown, enableAfterCountdown, progress info | v1.2 |
| **v1.4** | `feature/v1.4-progress` | Progress bar standalone (new component) | v1.3 |
| **v1.5** | `feature/v1.5-overlay` | Overlay blocker: spinner/countdown/progress (new) + Dismiss API | v1.4 |
| **v1.6** | `feature/v1.6-icons` | First icon expansion batch | v1.5 |

### Branch flow

```
main (v1.0) ──tag──▶ v1.0
  └── feature/v1.1-toast-countdown ──merge──▶ main ──tag──▶ v1.1
       └── feature/v1.2-alert-countdown ──merge──▶ main ──tag──▶ v1.2
            └── feature/v1.3-sheet-countdown ──merge──▶ main ──tag──▶ v1.3
                 └── feature/v1.4-progress ──merge──▶ main ──tag──▶ v1.4
                      └── feature/v1.5-overlay ──merge──▶ main ──tag──▶ v1.5
                           └── feature/v1.6-icons ──merge──▶ main ──tag──▶ v1.6
```

### Code reuse across releases

v1.1 creates countdown infrastructure in Kotlin (`CountdownHelper.kt`) and Swift stub. v1.2 and v1.3 reuse it. v1.5 reuses countdown + progress from v1.4.

---

## Components After Expansion

| Component | Status | Purpose |
|---|---|---|
| Toast | Existing + enhanced | Temporary notification |
| Alert | Existing + enhanced | Modal dialog |
| Sheet | Existing + enhanced | Bottom sheet |
| **Progress** | **New (v1.4)** | Standalone progress bar (top/bottom of screen) |
| **Overlay** | **New (v1.5)** | Full-screen blocking feedback (spinner/countdown/progress) |

### New behaviors (cross-cutting)

| Behavior | Applies to | What it does |
|---|---|---|
| Countdown | Toast, Alert, Sheet | Countdown timer, toggleable visibility |
| EnableAfterCountdown | Button (in Alert/Sheet) | Button disabled until timer reaches 0 |
| Progress display | Alert, Sheet, Overlay | Progress bar with X/Y or % format |
| Programmatic dismiss | Progress, Overlay | `Dialogify::dismiss(id)` from backend |

---

## Bridge Functions

```json
{
  "bridge_functions": [
    { "name": "Dialogify.Toast",    "since": "v1.0" },
    { "name": "Dialogify.Alert",    "since": "v1.0" },
    { "name": "Dialogify.Sheet",    "since": "v1.0" },
    { "name": "Dialogify.Progress", "since": "v1.4" },
    { "name": "Dialogify.Overlay",  "since": "v1.5" },
    { "name": "Dialogify.Dismiss",  "since": "v1.4" }
  ]
}
```

---

## v1.1 — Toast with Countdown

### New methods on PendingToast

```php
Dialogify::toast('Action will execute in 5s')
    ->countdown(5)            // auto-dismiss in 5s (overrides duration)
    ->showTimer()             // shows countdown visually ("5... 4... 3...")
    ->action(Button::make('undo', 'Undo'))
    ->show();

// Hidden timer (works like duration but with countdown infra)
Dialogify::toast('Processing...')
    ->countdown(10)
    ->showTimer(false)        // timer runs but is not visible
    ->show();
```

### Payload additions

```json
{
  "message": "Action will execute in 5s",
  "countdown": 5,
  "showTimer": true,
  "action": { "id": "undo", "label": "Undo" }
}
```

### Native behavior

- `countdown` present: Android `CountDownTimer(seconds * 1000, 1000)`
- `showTimer = true`: countdown text displayed in the toast (right side of message or below)
- Action button clicked: cancel countdown, dismiss toast, dispatch `ToastAction`
- Countdown reaches 0: dismiss toast (no event — timeout is the expected action)
- Timer visual: simple text "5s" updating every second

---

## v1.2 — Alert with Countdown + Progress Info

### New methods on PendingAlert

```php
// Case 1: Mandatory read — button disabled until countdown
Dialogify::alert('Terms of Service', $terms)
    ->countdown(20)
    ->showTimer()
    ->positive(Button::make('accept', 'Accept')->enableAfterCountdown())
    ->negative(Button::make('cancel', 'Cancel'))
    ->cancelable(false)
    ->show();

// Case 2: Static progress info
Dialogify::alert('Congratulations!', 'Step 3 of 5 completed')
    ->progress(3, 5)                  // bar at 60%
    ->progressFormat('fraction')      // shows "3/5" (or 'percent' for "60%")
    ->positive(Button::make('next', 'Continue')->primary())
    ->show();

// Case 3: Progress without visible values
Dialogify::alert('Progress', 'Almost there')
    ->progress(4, 5)
    ->progressFormat('none')          // visual bar only, no text
    ->positive(Button::make('ok', 'OK'))
    ->show();
```

### New method on Button

```php
Button::make('accept', 'Accept')
    ->primary()
    ->enableAfterCountdown()   // starts disabled, enables when countdown = 0
```

### Payload additions

```json
{
  "title": "Terms of Service",
  "message": "...",
  "countdown": 20,
  "showTimer": true,
  "progress": { "current": 3, "total": 5, "format": "fraction" },
  "positive": { "id": "accept", "label": "Accept", "enableAfterCountdown": true }
}
```

### Native behavior

- Countdown: positive button rendered "grayed out" until timer finishes
- Timer shown in button text: "Accept (18s)" → "Accept (17s)" → ... → "Accept"
- Progress bar: native `ProgressBar` (Android) / `UIProgressView` (iOS), positioned between message and buttons
- `progressFormat`: `fraction` = "3/5", `percent` = "60%", `none` = no text

---

## v1.3 — Sheet with Countdown + Progress Info

Same logic as Alert, adapted for Sheet:

```php
// Sheet with mandatory read
Dialogify::sheet('Privacy Policy')
    ->message($policy)
    ->countdown(15)
    ->showTimer()
    ->addButton(Button::make('accept', 'Accept')->primary()->enableAfterCountdown())
    ->addButton(Button::make('cancel', 'Cancel')->cancel())
    ->cancelable(false)
    ->show();

// Sheet with progress info
Dialogify::sheet('Registration Progress')
    ->message('Almost done')
    ->progress(2, 4)
    ->progressFormat('fraction')    // "2/4"
    ->addButton(Button::make('next', 'Next Step')->primary())
    ->show();
```

Implementation reuses `CountdownHelper` and `ProgressBarHelper` from v1.1 and v1.2.

---

## v1.4 — Standalone Progress Bar (new component)

### New class: PendingProgress

```php
// Create progress bar at top of screen
Dialogify::progress('upload')
    ->position(Position::Top)         // Top or Bottom
    ->color('#3b82f6')                // bar color
    ->label('Uploading...')           // optional text
    ->showValue('percent')            // 'percent' = "35%", 'fraction' = "15/42", 'none'
    ->show();

// Update progress (percentage)
Dialogify::progress('upload')->update(0.65);      // 65%

// Update progress (fraction)
Dialogify::progress('upload')->update(15, 42);    // 15/42

// Indeterminate (infinite animation, no value)
Dialogify::progress('loading')
    ->position(Position::Top)
    ->indeterminate()                 // animated bar with no defined progress
    ->show();

// Remove progress bar
Dialogify::progress('upload')->dismiss();
```

### Bridge function

`Dialogify.Progress` — idempotent (creates or updates).

### Payload

```json
{
  "id": "upload",
  "position": "top",
  "color": "#3b82f6",
  "label": "Uploading...",
  "showValue": "percent",
  "value": 0.65,
  "current": null,
  "total": null,
  "indeterminate": false,
  "dismiss": false
}
```

### Native behavior

- Android: `ProgressBar` with `style="?android:attr/progressBarStyleHorizontal"`, fixed overlay at top/bottom
- iOS: `UIProgressView` with fixed constraint
- Indeterminate: native bar animation
- `dismiss: true`: removes the overlay

### Dismiss API

New bridge function: `Dialogify.Dismiss` — generic dismiss by ID.

```json
{ "id": "upload" }
```

Works for Progress and Overlay (v1.5).

---

## v1.5 — Blocking Overlay (new component)

### New class: PendingOverlay

3 display modes (mutually exclusive):

```php
// MODE 1: Spinner (indeterminate) — unknown end time
Dialogify::overlay('Syncing data...')
    ->spinner()                       // animated loading icon
    ->id('sync')
    ->show();

// Programmatic dismiss when operation completes
Dialogify::dismiss('sync');

// MODE 2: Countdown — known time limit
Dialogify::overlay('Processing transaction...')
    ->countdown(30)                   // 30 seconds
    ->showTimer()                     // shows "28s"
    ->id('transaction')
    ->show();

// Auto-dismiss when countdown = 0, or manual:
Dialogify::dismiss('transaction');

// MODE 3: Progress bar — trackable steps
Dialogify::overlay('Sending records')
    ->progress(0, 42)                // starts at 0/42
    ->showValue('fraction')          // shows "0/42"
    ->id('upload')
    ->show();

// Update message and progress
Dialogify::overlay('upload')
    ->updateMessage('Sending records')
    ->updateProgress(15, 42);        // updates to 15/42

Dialogify::dismiss('upload');
```

### Overlay characteristics

- **Always blocking** — `cancelable: false` implicit, no close button
- **Full-screen semi-transparent** — darkened background, centered content
- **Dismiss only programmatic** — `Dialogify::dismiss(id)` or countdown = 0

### Bridge function

`Dialogify.Overlay` — idempotent (creates or updates).

### Payload (create)

```json
{
  "id": "sync",
  "message": "Syncing data...",
  "mode": "spinner",
  "countdown": null,
  "showTimer": false,
  "progress": null,
  "showValue": "none"
}
```

### Payload (update)

```json
{
  "id": "upload",
  "message": "Sending records",
  "progress": { "current": 15, "total": 42 },
  "showValue": "fraction"
}
```

### Native behavior

- Android: `FrameLayout` overlay with `#80000000` background (semi-transparent), centered content
  - Spinner: circular `ProgressBar` (indeterminate)
  - Countdown: `TextView` + `CountDownTimer`
  - Progress: horizontal `ProgressBar` + `TextView` with value
- iOS: `UIView` overlay + `UIActivityIndicatorView` (spinner) or `UIProgressView`
- No user interaction — 100% backend-controlled
- No events dispatched

---

## v1.6 — First Icon Expansion

### Strategy

Small batches of icons per minor release. Each batch is social media content.

### v1.6 — Batch 1 (15 new icons focused on common use cases)

```php
enum Icon: string {
    // Existing (v1.0)
    case CheckCircle = 'check_circle';
    case Warning = 'warning';
    case Error = 'error';
    case Info = 'info';
    case Success = 'check';
    case Delete = 'delete';
    case Close = 'close';
    case QuestionMark = 'help';

    // Batch 1 (v1.6)
    case Sync = 'sync';
    case Upload = 'upload';
    case Download = 'download';
    case Settings = 'settings';
    case Person = 'person';
    case Lock = 'lock';
    case Unlock = 'lock_open';
    case Wifi = 'wifi';
    case WifiOff = 'wifi_off';
    case Star = 'star';
    case Favorite = 'favorite';
    case Share = 'share';
    case Edit = 'edit';
    case Save = 'save';
    case Refresh = 'refresh';
}
```

### Cross-platform mapping

| PHP Enum | Android (Material) | iOS (SF Symbols) |
|---|---|---|
| `Sync` | `ic_sync` | `arrow.triangle.2.circlepath` |
| `Upload` | `ic_upload` | `arrow.up.circle` |
| `Download` | `ic_download` | `arrow.down.circle` |
| `Lock` | `ic_lock` | `lock` |
| `Person` | `ic_person` | `person` |
| `Wifi` | `ic_wifi` | `wifi` |

Mapping lives in native code (Kotlin/Swift), transparent to PHP developer.

### Future releases (v1.7+)

- More icon batches
- Custom drawables (project asset name)
- Animated icons (spinner, pulse) — when infrastructure is mature

---

## Cross-Platform Parity

| Feature | Android | iOS | Parity level |
|---|---|---|---|
| Countdown timer | `CountDownTimer` | `Timer` (Foundation) | Excellent |
| Progress bar | `ProgressBar` widget | `UIProgressView` | Excellent |
| Loading overlay | Custom View + `ProgressBar` | Custom View + `UIActivityIndicatorView` | Excellent |
| Material Icons | Native (hundreds) | SF Symbols (equivalents) | Good (needs mapping) |

All features are achievable natively on both platforms.

---

## What's NOT in v1.x

- Full iOS implementation (roadmap, tracked separately)
- Rich text / HTML in messages
- Nested dialogs
- Custom fonts
- Charts (separate plugin: nativephp-chartsy)
