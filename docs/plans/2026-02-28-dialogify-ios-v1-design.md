# Dialogify iOS v1.0 — Native Implementation Design

**Date**: 2026-02-28
**Scope**: v1.0 only — Toast, Alert, Sheet with full input support
**Target**: iOS 15.0+, zero external dependencies

## Architecture

### BridgeFunction Protocol

The NativePHP iOS `BridgeFunction` protocol does NOT pass `webView`:

```swift
protocol BridgeFunction {
    func execute(parameters: [String: Any]) throws -> [String: Any]
}
```

The current stubs with `required init(webView: Any)` are incorrect and must be removed.

### Event Dispatch

All events go through `LaravelBridge.shared.send?(event, payload)` — a singleton that NativePHP core connects to `WKWebView.evaluateJavaScript` internally. This replaces the Android pattern of direct JavaScript injection via `WebViewProvider`.

### Root ViewController Access

```swift
guard let scene = UIApplication.shared.connectedScenes
    .compactMap({ $0 as? UIWindowScene })
    .first(where: { $0.activationState == .foregroundActive }),
  let root = scene.windows.first(where: { $0.isKeyWindow })?.rootViewController else {
    return
}
```

### File Structure

```
resources/ios/Sources/
├── AlertFunctions.swift       — Alert dialog (simple + complex inputs)
├── ToastFunctions.swift        — Toast orchestration
├── SheetFunctions.swift        — Bottom sheet
└── ui/
    └── ToastOverlayView.swift  — Custom toast UI
```

## Component Designs

### 1. AlertFunctions

**Two modes based on input complexity:**

- **Simple (no inputs or text-only inputs)**: `UIAlertController` with `addTextField` and `addAction`
- **Complex (date, select, checkbox inputs)**: `UIViewController` presented as `.formSheet` with programmatic form layout

**Input type mapping:**

| Type | iOS Component |
|------|---------------|
| text | `UITextField` (via `alert.addTextField` or in form) |
| email | `UITextField` with `.emailAddress` keyboard |
| password | `UITextField` with `isSecureTextEntry = true` |
| number | `UITextField` with `.numberPad` keyboard |
| date | `UIDatePicker` with `.date` mode |
| time | `UIDatePicker` with `.time` mode |
| datetime | `UIDatePicker` with `.dateAndTime` mode |
| select/radio | `UIPickerView` |
| checkbox | `UISwitch` rows |

**Date constraints**: `minimumDate`/`maximumDate` on UIDatePicker. `minuteInterval` on UIDatePicker natively supported.

**Button style mapping:**

| Plugin Style | UIAlertAction.Style |
|-------------|---------------------|
| primary/default | `.default` |
| destructive | `.destructive` |
| cancel | `.cancel` |

**Cancel handling**: When cancelable and dismissed without button press, dispatch `ButtonPressed` with `buttonId: "cancelled"`, `index: -1`.

**Event payloads** — identical to Android:

```swift
// Button only
LaravelBridge.shared.send?(event, [
    "buttonId": "ok", "label": "OK", "index": 0, "dialogId": "my-alert"
])

// With inputs
LaravelBridge.shared.send?(event, [
    "buttonId": "submit", "label": "Submit", "dialogId": "my-form",
    "inputs": ["name": "John", "email": "j@x.com"]
])
```

### 2. ToastFunctions + ToastOverlayView

Custom `UIView` overlay on key window.

**Layout**: Horizontal stack — icon (optional) + message + action button (optional)
- Background: `UIView` with `layer.cornerRadius` and `layer.shadowOffset/shadowRadius`
- Colors: customizable `backgroundColor` and `textColor` (hex string parsing)
- Positions: top (respects `safeAreaInsets.top`), bottom (respects `safeAreaInsets.bottom`), center

**Animation**: `UIView.animate` fade in 0.2s → auto-dismiss after `durationMs` → fade out 0.2s

**Duration**: `"short"` = 2s, `"long"` = 4s, or custom milliseconds from parameter.

**Action button**: Cancels auto-dismiss timer, dispatches `ToastAction` event, then dismisses.

**Icons — SF Symbols mapping:**

| Plugin Icon | SF Symbol |
|-------------|-----------|
| info | `info.circle.fill` |
| warning | `exclamationmark.triangle.fill` |
| error | `xmark.circle.fill` |
| success/check | `checkmark.circle.fill` |
| question | `questionmark.circle.fill` |
| delete | `trash.fill` |
| notification | `bell.fill` |
| email | `envelope.fill` |

### 3. SheetFunctions

`UIViewController` with `UISheetPresentationController` (iOS 15+).

**Configuration:**
- Detents: `.medium()` and `.large()` (auto-sized based on content)
- `prefersGrabberVisible = true`
- `isModalInPresentation = !cancelable` (prevents swipe-dismiss when not cancelable)

**Layout**: Vertical stack in `UIScrollView` — title → message → inputs → buttons (programmatic).

**Inputs**: Same implementation as Alert complex form (shared code).

**Cancel handling**: `UIAdaptivePresentationControllerDelegate.presentationControllerDidDismiss` dispatches cancel event.

**Buttons**: Variable count (not fixed to positive/negative/neutral like Alert).

## Event Classes (unchanged from Android)

- `MazerDev\Dialogify\Events\ButtonPressed` — Alert/Sheet button click
- `MazerDev\Dialogify\Events\InputSubmitted` — Alert/Sheet with inputs submitted
- `MazerDev\Dialogify\Events\ToastAction` — Toast action button click

## Key Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Alert simple | UIAlertController | Native, familiar, minimal code |
| Alert complex inputs | UIViewController .formSheet | Full layout control for pickers/switches |
| Toast | Custom UIView overlay | Matches Android feature set (position, colors, icon, action) |
| Sheet | UISheetPresentationController | Native iOS 15+ bottom sheet with detents |
| Icons | SF Symbols | Zero dependencies, native iOS |
| Event dispatch | LaravelBridge.shared | Official NativePHP pattern |
| External deps | None | Pure UIKit |
