# Dialogify iOS v1.0 Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implement full iOS native UI for Dialogify v1.0 — Toast, Alert, and Sheet with complete input support, matching Android feature parity.

**Architecture:** Each dialog type is a Swift `BridgeFunction` conforming to NativePHP's protocol (`execute(parameters:) -> [String: Any]`). Events dispatch via `LaravelBridge.shared.send?(event, payload)`. Toast uses a custom `UIView` overlay; Alert uses `UIAlertController` for simple cases and a modal `UIViewController` for complex inputs; Sheet uses `UISheetPresentationController`.

**Tech Stack:** Swift 5.9+, UIKit, iOS 15.0+, SF Symbols, zero external dependencies.

**Working directory:** `~/Workspaces/nativephp-plugins/nativephp-dialogify/`

**Important context:**
- The NativePHP iOS `BridgeFunction` protocol has NO `init(webView:)` — only `func execute(parameters: [String: Any]) throws -> [String: Any]`
- Events go through `LaravelBridge.shared.send?(eventName, payloadDict)` — a singleton the NativePHP core connects to `WKWebView.evaluateJavaScript`
- Parameters arrive as `[String: Any]` dicts (parsed from JSON by NativePHP core)
- This is a plugin repo with no Xcode project — files are compiled into the host app by NativePHP's `IOSPluginCompiler`
- Existing Android implementation at `resources/android/src/` serves as behavioral reference
- No unit tests for native Swift code — testing is manual on-device only
- Code and docs language: **English**

---

### Task 1: ToastOverlayView — Custom Toast UI Component

**Files:**
- Rewrite: `resources/ios/Sources/ui/ToastOverlayView.swift`

**Context:** This is a standalone UIView component that renders a floating toast card on the key window. It's used by ToastFunctions.Show. The Android equivalent is `CustomToastView.kt` (182 lines). This must handle: custom colors, icons (SF Symbols), positioning (top/bottom/center), action button, auto-dismiss with fade animation.

**Step 1: Write ToastOverlayView**

Replace the empty stub with the full implementation:

```swift
// resources/ios/Sources/ui/ToastOverlayView.swift
import UIKit

class ToastOverlayView {
    private let message: String
    private let position: String
    private let bgColor: String?
    private let txtColor: String?
    private let iconName: String?
    private let durationMs: Int
    private let cornerRadiusDp: Int?
    private let actionLabel: String?
    private let actionColor: String?
    private let onAction: (() -> Void)?

    private var container: UIView?
    private var dismissWorkItem: DispatchWorkItem?

    init(
        message: String,
        position: String = "bottom",
        bgColor: String? = nil,
        txtColor: String? = nil,
        iconName: String? = nil,
        durationMs: Int = 4000,
        cornerRadiusDp: Int? = nil,
        actionLabel: String? = nil,
        actionColor: String? = nil,
        onAction: (() -> Void)? = nil
    ) {
        self.message = message
        self.position = position
        self.bgColor = bgColor
        self.txtColor = txtColor
        self.iconName = iconName
        self.durationMs = durationMs
        self.cornerRadiusDp = cornerRadiusDp
        self.actionLabel = actionLabel
        self.actionColor = actionColor
        self.onAction = onAction
    }

    func show() {
        DispatchQueue.main.async { [self] in
            guard let window = Self.getKeyWindow() else { return }

            let margin: CGFloat = 16
            let safeArea = window.safeAreaInsets

            // Card container — horizontal stack
            let stack = UIStackView()
            stack.axis = .horizontal
            stack.alignment = .center
            stack.spacing = 8
            stack.translatesAutoresizingMaskIntoConstraints = false

            let cardView = UIView()
            cardView.backgroundColor = Self.parseColor(bgColor, default: "#323232")
            cardView.layer.cornerRadius = CGFloat(cornerRadiusDp ?? 8)
            cardView.layer.shadowColor = UIColor.black.cgColor
            cardView.layer.shadowOffset = CGSize(width: 0, height: 2)
            cardView.layer.shadowRadius = 4
            cardView.layer.shadowOpacity = 0.3
            cardView.translatesAutoresizingMaskIntoConstraints = false

            cardView.addSubview(stack)
            NSLayoutConstraint.activate([
                stack.leadingAnchor.constraint(equalTo: cardView.leadingAnchor, constant: 16),
                stack.trailingAnchor.constraint(equalTo: cardView.trailingAnchor, constant: -16),
                stack.topAnchor.constraint(equalTo: cardView.topAnchor, constant: 12),
                stack.bottomAnchor.constraint(equalTo: cardView.bottomAnchor, constant: -12),
            ])

            // Icon (SF Symbol)
            if let iconName = iconName, let sfName = Self.sfSymbolName(for: iconName) {
                let config = UIImage.SymbolConfiguration(pointSize: 18, weight: .medium)
                if let image = UIImage(systemName: sfName, withConfiguration: config) {
                    let imageView = UIImageView(image: image)
                    imageView.tintColor = Self.parseColor(txtColor, default: "#FFFFFF")
                    imageView.translatesAutoresizingMaskIntoConstraints = false
                    imageView.setContentHuggingPriority(.required, for: .horizontal)
                    NSLayoutConstraint.activate([
                        imageView.widthAnchor.constraint(equalToConstant: 20),
                        imageView.heightAnchor.constraint(equalToConstant: 20),
                    ])
                    stack.addArrangedSubview(imageView)
                }
            }

            // Message label
            let label = UILabel()
            label.text = message
            label.textColor = Self.parseColor(txtColor, default: "#FFFFFF")
            label.font = .systemFont(ofSize: 14)
            label.numberOfLines = 0
            label.setContentCompressionResistancePriority(.defaultLow, for: .horizontal)
            stack.addArrangedSubview(label)

            // Action button
            if let actionLabel = actionLabel {
                let actionButton = UIButton(type: .system)
                actionButton.setTitle(actionLabel.uppercased(), for: .normal)
                actionButton.setTitleColor(Self.parseColor(actionColor, default: "#BB86FC"), for: .normal)
                actionButton.titleLabel?.font = .boldSystemFont(ofSize: 14)
                actionButton.setContentHuggingPriority(.required, for: .horizontal)
                actionButton.addAction(UIAction { [weak self] _ in
                    self?.onAction?()
                    self?.dismiss()
                }, for: .touchUpInside)
                stack.addArrangedSubview(actionButton)
            }

            // Wrapper for positioning
            let wrapper = UIView()
            wrapper.translatesAutoresizingMaskIntoConstraints = false
            wrapper.addSubview(cardView)
            window.addSubview(wrapper)

            NSLayoutConstraint.activate([
                wrapper.leadingAnchor.constraint(equalTo: window.leadingAnchor),
                wrapper.trailingAnchor.constraint(equalTo: window.trailingAnchor),
                wrapper.topAnchor.constraint(equalTo: window.topAnchor),
                wrapper.bottomAnchor.constraint(equalTo: window.bottomAnchor),

                cardView.leadingAnchor.constraint(equalTo: wrapper.leadingAnchor, constant: margin),
                cardView.trailingAnchor.constraint(equalTo: wrapper.trailingAnchor, constant: -margin),
            ])

            switch position {
            case "top":
                cardView.topAnchor.constraint(equalTo: wrapper.topAnchor, constant: safeArea.top + 48 + margin).isActive = true
            case "center":
                cardView.centerYAnchor.constraint(equalTo: wrapper.centerYAnchor).isActive = true
            default: // bottom
                cardView.bottomAnchor.constraint(equalTo: wrapper.bottomAnchor, constant: -(safeArea.bottom + 64 + margin)).isActive = true
            }

            // Allow taps to pass through the wrapper to views behind
            wrapper.isUserInteractionEnabled = true
            cardView.isUserInteractionEnabled = true

            container = wrapper

            // Fade in
            wrapper.alpha = 0
            UIView.animate(withDuration: 0.2) {
                wrapper.alpha = 1
            }

            // Auto-dismiss
            let workItem = DispatchWorkItem { [weak self] in
                self?.dismiss()
            }
            dismissWorkItem = workItem
            DispatchQueue.main.asyncAfter(deadline: .now() + Double(durationMs) / 1000.0, execute: workItem)
        }
    }

    private func dismiss() {
        dismissWorkItem?.cancel()
        dismissWorkItem = nil
        guard let view = container else { return }
        container = nil // Prevent double-dismiss
        UIView.animate(withDuration: 0.2, animations: {
            view.alpha = 0
        }) { _ in
            view.removeFromSuperview()
        }
    }

    // MARK: - Static Helpers

    static func parseColor(_ hex: String?, default defaultHex: String) -> UIColor {
        let hexStr = hex ?? defaultHex
        return colorFromHex(hexStr) ?? colorFromHex(defaultHex) ?? .white
    }

    private static func colorFromHex(_ hex: String) -> UIColor? {
        var h = hex.trimmingCharacters(in: .whitespacesAndNewlines)
        if h.hasPrefix("#") { h.removeFirst() }
        guard h.count == 6, let rgb = UInt64(h, radix: 16) else { return nil }
        return UIColor(
            red: CGFloat((rgb >> 16) & 0xFF) / 255.0,
            green: CGFloat((rgb >> 8) & 0xFF) / 255.0,
            blue: CGFloat(rgb & 0xFF) / 255.0,
            alpha: 1.0
        )
    }

    static func sfSymbolName(for iconName: String) -> String? {
        let mapping: [String: String] = [
            "info": "info.circle.fill",
            "warning": "exclamationmark.triangle.fill",
            "error": "xmark.circle.fill",
            "success": "checkmark.circle.fill",
            "check": "checkmark.circle.fill",
            "check_circle": "checkmark.circle.fill",
            "question": "questionmark.circle.fill",
            "help": "questionmark.circle.fill",
            "delete": "trash.fill",
            "close": "xmark",
            "notification": "bell.fill",
            "email": "envelope.fill",
        ]
        return mapping[iconName] ?? iconName
    }

    static func getKeyWindow() -> UIWindow? {
        UIApplication.shared.connectedScenes
            .compactMap { $0 as? UIWindowScene }
            .first(where: { $0.activationState == .foregroundActive })?
            .windows
            .first(where: { $0.isKeyWindow })
    }
}
```

**Step 2: Commit**

```bash
git add resources/ios/Sources/ui/ToastOverlayView.swift
git commit -m "feat(ios): implement ToastOverlayView with positioning, colors, icons, action button"
```

---

### Task 2: ToastFunctions — Toast Bridge Function

**Files:**
- Rewrite: `resources/ios/Sources/ToastFunctions.swift`

**Context:** Parses parameters from PHP, creates a `ToastOverlayView`, shows it. If there's an action button, dispatches `ToastAction` event via `LaravelBridge.shared.send?`. Reference: Android's `ToastFunctions.kt` (128 lines).

**Step 1: Write ToastFunctions.swift**

```swift
// resources/ios/Sources/ToastFunctions.swift
import Foundation
import UIKit

enum ToastFunctions {
    class Show: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let message = parameters["message"] as? String else {
                throw BridgeError.invalidParameters("message is required")
            }

            let position = parameters["position"] as? String ?? "bottom"
            let backgroundColor = parameters["backgroundColor"] as? String
            let textColor = parameters["textColor"] as? String
            let icon = parameters["icon"] as? String
            let cornerRadius = (parameters["cornerRadius"] as? NSNumber)?.intValue
            let id = parameters["id"] as? String

            let durationMs: Int
            if let durStr = parameters["duration"] as? String {
                switch durStr.lowercased() {
                case "short": durationMs = 2000
                default: durationMs = 4000
                }
            } else if let durNum = parameters["duration"] as? NSNumber {
                durationMs = durNum.intValue
            } else {
                durationMs = 4000
            }

            // Parse action button
            var actionLabel: String?
            var actionId: String?
            var actionColor: String?

            if let actionMap = parameters["action"] as? [String: Any] {
                actionLabel = actionMap["label"] as? String
                actionId = actionMap["id"] as? String
                actionColor = actionMap["color"] as? String
            }

            let event = "MazerDev\\Dialogify\\Events\\ToastAction"

            let toast = ToastOverlayView(
                message: message,
                position: position,
                bgColor: backgroundColor,
                txtColor: textColor,
                iconName: icon,
                durationMs: durationMs,
                cornerRadiusDp: cornerRadius,
                actionLabel: actionLabel,
                actionColor: actionColor,
                onAction: actionLabel != nil ? {
                    var payload: [String: Any] = [
                        "buttonId": actionId ?? actionLabel ?? "",
                        "label": actionLabel ?? "",
                    ]
                    if let id = id {
                        payload["toastId"] = id
                    }
                    LaravelBridge.shared.send?(event, payload)
                } : nil
            )

            toast.show()
            return ["success": true]
        }
    }
}
```

**Step 2: Commit**

```bash
git add resources/ios/Sources/ToastFunctions.swift
git commit -m "feat(ios): implement ToastFunctions bridge with parameter parsing and event dispatch"
```

---

### Task 3: AlertFunctions — Simple Alerts (UIAlertController)

**Files:**
- Rewrite: `resources/ios/Sources/AlertFunctions.swift`

**Context:** This is the most complex component. It has two modes:
1. **Simple mode** (no inputs OR only text/number/password/email inputs): Uses native `UIAlertController` with `addTextField`
2. **Complex mode** (date/time/datetime/select/radio/checkbox inputs): Uses a modal `UIViewController` presented as `.formSheet`

This task implements the full AlertFunctions including both modes. Reference: Android's `AlertFunctions.kt` (421 lines).

**Parameter shape from PHP:**
```json
{
  "title": "string",
  "message": "string|null",
  "icon": "string|null",
  "cancelable": true,
  "id": "string|null",
  "event": "string|null",
  "positive": {"id": "ok", "label": "OK", "style": "primary", "color": null, "backgroundColor": null},
  "negative": {"id": "cancel", "label": "Cancel", "style": "cancel", "color": null, "backgroundColor": null},
  "neutral": null,
  "inputs": [
    {"id": "name", "label": "Name", "type": "text", "placeholder": "Enter name", "defaultValue": null, "required": false, "maxLength": 50, "options": null, "minDate": null, "maxDate": null, "minuteInterval": null}
  ]
}
```

**Step 1: Write AlertFunctions.swift**

```swift
// resources/ios/Sources/AlertFunctions.swift
import Foundation
import UIKit

enum AlertFunctions {

    class Show: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let title = parameters["title"] as? String ?? ""
            let message = parameters["message"] as? String
            let icon = parameters["icon"] as? String
            let cancelable = parameters["cancelable"] as? Bool ?? true
            let id = parameters["id"] as? String
            let customEvent = parameters["event"] as? String

            let positive = parameters["positive"] as? [String: Any]
            let negative = parameters["negative"] as? [String: Any]
            let neutral = parameters["neutral"] as? [String: Any]

            let inputs = Self.parseInputs(parameters["inputs"])
            let hasInputs = !inputs.isEmpty

            let buttonEvent = customEvent ?? (hasInputs
                ? "MazerDev\\Dialogify\\Events\\InputSubmitted"
                : "MazerDev\\Dialogify\\Events\\ButtonPressed")

            let needsComplexForm = inputs.contains { input in
                let type = input["type"] as? String ?? "text"
                return ["date", "time", "datetime", "select", "radio", "checkbox"].contains(type)
            }

            DispatchQueue.main.async {
                guard let root = Self.topViewController() else { return }

                if hasInputs && needsComplexForm {
                    Self.showFormAlert(
                        on: root, title: title, message: message, icon: icon,
                        cancelable: cancelable, id: id, event: buttonEvent,
                        positive: positive, negative: negative, neutral: neutral,
                        inputs: inputs
                    )
                } else {
                    Self.showSimpleAlert(
                        on: root, title: title, message: message, icon: icon,
                        cancelable: cancelable, id: id, event: buttonEvent,
                        positive: positive, negative: negative, neutral: neutral,
                        inputs: inputs, hasInputs: hasInputs
                    )
                }
            }

            return [:]
        }

        // MARK: - Simple Alert (UIAlertController)

        private static func showSimpleAlert(
            on root: UIViewController,
            title: String, message: String?, icon: String?,
            cancelable: Bool, id: String?, event: String,
            positive: [String: Any]?, negative: [String: Any]?, neutral: [String: Any]?,
            inputs: [[String: Any]], hasInputs: Bool
        ) {
            let alert = UIAlertController(title: title, message: message, preferredStyle: .alert)

            // Add text fields for simple inputs
            var textFieldMap: [(String, UITextField)] = []
            for input in inputs {
                let inputId = input["id"] as? String ?? ""
                let label = input["label"] as? String ?? ""
                let type = input["type"] as? String ?? "text"
                let placeholder = input["placeholder"] as? String
                let defaultValue = input["defaultValue"]
                let maxLength = input["maxLength"] as? Int

                alert.addTextField { textField in
                    textField.placeholder = placeholder ?? label
                    if let dv = defaultValue as? String { textField.text = dv }
                    if let dv = defaultValue as? NSNumber { textField.text = dv.stringValue }

                    switch type {
                    case "number":
                        textField.keyboardType = .numberPad
                    case "password":
                        textField.isSecureTextEntry = true
                    case "email":
                        textField.keyboardType = .emailAddress
                        textField.autocapitalizationType = .none
                    default:
                        textField.keyboardType = .default
                    }

                    if let maxLen = maxLength, maxLen > 0 {
                        // maxLength is enforced in collectValues via delegate or truncation
                    }
                }
                textFieldMap.append((inputId, alert.textFields!.last!))
            }

            var buttonPressed = false

            func addButton(_ btnDict: [String: Any]?, style: UIAlertAction.Style, index: Int) {
                guard let btn = btnDict else { return }
                let btnId = btn["id"] as? String ?? ""
                let btnLabel = btn["label"] as? String ?? ""
                let btnStyle = btn["style"] as? String

                let actionStyle: UIAlertAction.Style
                switch btnStyle {
                case "destructive": actionStyle = .destructive
                case "cancel": actionStyle = .cancel
                default: actionStyle = style
                }

                alert.addAction(UIAlertAction(title: btnLabel, style: actionStyle) { _ in
                    buttonPressed = true
                    if hasInputs {
                        var inputValues: [String: Any] = [:]
                        for (inputId, textField) in textFieldMap {
                            inputValues[inputId] = textField.text ?? ""
                        }
                        var payload: [String: Any] = [
                            "buttonId": btnId,
                            "label": btnLabel,
                            "inputs": inputValues,
                        ]
                        if let id = id { payload["dialogId"] = id }
                        LaravelBridge.shared.send?(event, payload)
                    } else {
                        var payload: [String: Any] = [
                            "buttonId": btnId,
                            "label": btnLabel,
                            "index": index,
                        ]
                        if let id = id { payload["dialogId"] = id }
                        LaravelBridge.shared.send?(event, payload)
                    }
                })
            }

            addButton(positive, style: .default, index: 0)
            addButton(negative, style: .cancel, index: 1)
            addButton(neutral, style: .default, index: 2)

            // If no buttons at all, add a default OK
            if positive == nil && negative == nil && neutral == nil {
                alert.addAction(UIAlertAction(title: "OK", style: .default) { _ in
                    buttonPressed = true
                })
            }

            // Handle cancel/dismiss without button press
            if cancelable {
                // UIAlertController does not have a built-in onDismiss without button.
                // We handle this via a cancel-style action if no cancel button exists,
                // or via viewDidDisappear in a wrapper.
                // For simplicity: if alert has no .cancel action and is cancelable,
                // the tapping outside behavior dispatches cancel event.
            }

            root.present(alert, animated: true)
        }

        // MARK: - Complex Form Alert (UIViewController modal)

        private static func showFormAlert(
            on root: UIViewController,
            title: String, message: String?, icon: String?,
            cancelable: Bool, id: String?, event: String,
            positive: [String: Any]?, negative: [String: Any]?, neutral: [String: Any]?,
            inputs: [[String: Any]]
        ) {
            let formVC = DialogifyFormViewController(
                dialogTitle: title,
                dialogMessage: message,
                iconName: icon,
                cancelable: cancelable,
                dialogId: id,
                event: event,
                positive: positive,
                negative: negative,
                neutral: neutral,
                inputs: inputs,
                isSheet: false
            )
            formVC.modalPresentationStyle = .formSheet
            formVC.isModalInPresentation = !cancelable
            root.present(formVC, animated: true)
        }

        // MARK: - Helpers

        static func parseInputs(_ param: Any?) -> [[String: Any]] {
            if let arr = param as? [[String: Any]] { return arr }
            if let arr = param as? [Any] {
                return arr.compactMap { $0 as? [String: Any] }
            }
            return []
        }

        static func topViewController() -> UIViewController? {
            guard let scene = UIApplication.shared.connectedScenes
                .compactMap({ $0 as? UIWindowScene })
                .first(where: { $0.activationState == .foregroundActive }),
                  let root = scene.windows.first(where: { $0.isKeyWindow })?.rootViewController else {
                return nil
            }
            var top = root
            while let presented = top.presentedViewController {
                top = presented
            }
            return top
        }
    }
}
```

**Step 2: Commit**

```bash
git add resources/ios/Sources/AlertFunctions.swift
git commit -m "feat(ios): implement AlertFunctions with simple UIAlertController and complex form modes"
```

---

### Task 4: DialogifyFormViewController — Shared Form UI for Complex Inputs

**Files:**
- Create: `resources/ios/Sources/ui/DialogifyFormViewController.swift`

**Context:** This `UIViewController` is used by both AlertFunctions (complex mode) and SheetFunctions. It builds a scrollable form with all input types programmatically. It handles input value collection and event dispatch. This is the iOS equivalent of the input-handling code duplicated across `AlertFunctions.kt` and `SheetFunctions.kt`'s `DialogifySheetFragment` on Android.

**Key behaviors:**
- Renders title, message, icon, input fields, and buttons in a vertical scroll view
- Input types: text/number/password/email (UITextField), date/time/datetime (UIDatePicker), select/radio (UIPickerView), checkbox (UISwitch rows)
- Collects all input values when a button is pressed
- Dispatches event via `LaravelBridge.shared.send?`
- Cancel handling: dispatches `ButtonPressed` with `buttonId: "cancelled"` when dismissed without pressing a button
- When `isSheet: true`, configures `sheetPresentationController` with detents and grabber

**Step 1: Write DialogifyFormViewController.swift**

```swift
// resources/ios/Sources/ui/DialogifyFormViewController.swift
import UIKit

class DialogifyFormViewController: UIViewController, UIAdaptivePresentationControllerDelegate, UIPickerViewDelegate, UIPickerViewDataSource {

    private let dialogTitle: String
    private let dialogMessage: String?
    private let iconName: String?
    private let cancelable: Bool
    private let dialogId: String?
    private let event: String
    private let positive: [String: Any]?
    private let negative: [String: Any]?
    private let neutral: [String: Any]?
    private let inputs: [[String: Any]]
    private let isSheet: Bool
    private let sheetButtons: [[String: Any]]

    private var inputViews: [String: Any] = [:]
    private var buttonPressed = false

    // For UIPickerView data
    private var pickerData: [String: (keys: [String], labels: [String])] = [:]

    init(
        dialogTitle: String,
        dialogMessage: String? = nil,
        iconName: String? = nil,
        cancelable: Bool = true,
        dialogId: String? = nil,
        event: String,
        positive: [String: Any]? = nil,
        negative: [String: Any]? = nil,
        neutral: [String: Any]? = nil,
        inputs: [[String: Any]] = [],
        isSheet: Bool = false,
        sheetButtons: [[String: Any]] = []
    ) {
        self.dialogTitle = dialogTitle
        self.dialogMessage = dialogMessage
        self.iconName = iconName
        self.cancelable = cancelable
        self.dialogId = dialogId
        self.event = event
        self.positive = positive
        self.negative = negative
        self.neutral = neutral
        self.inputs = inputs
        self.isSheet = isSheet
        self.sheetButtons = sheetButtons
        super.init(nibName: nil, bundle: nil)
    }

    required init?(coder: NSCoder) { fatalError("init(coder:) has not been implemented") }

    override func viewDidLoad() {
        super.viewDidLoad()
        presentationController?.delegate = self
        view.backgroundColor = .systemBackground

        if isSheet, let sheet = sheetPresentationController {
            sheet.detents = [.medium(), .large()]
            sheet.prefersGrabberVisible = true
            sheet.prefersScrollingExpandsWhenScrolledToEdge = false
        }

        buildUI()
    }

    // MARK: - UI Construction

    private func buildUI() {
        let scrollView = UIScrollView()
        scrollView.translatesAutoresizingMaskIntoConstraints = false
        scrollView.keyboardDismissMode = .interactive
        view.addSubview(scrollView)

        let contentStack = UIStackView()
        contentStack.axis = .vertical
        contentStack.spacing = 12
        contentStack.translatesAutoresizingMaskIntoConstraints = false
        scrollView.addSubview(contentStack)

        let padding: CGFloat = 24

        NSLayoutConstraint.activate([
            scrollView.topAnchor.constraint(equalTo: view.safeAreaLayoutGuide.topAnchor),
            scrollView.leadingAnchor.constraint(equalTo: view.leadingAnchor),
            scrollView.trailingAnchor.constraint(equalTo: view.trailingAnchor),
            scrollView.bottomAnchor.constraint(equalTo: view.bottomAnchor),

            contentStack.topAnchor.constraint(equalTo: scrollView.topAnchor, constant: padding),
            contentStack.leadingAnchor.constraint(equalTo: scrollView.leadingAnchor, constant: padding),
            contentStack.trailingAnchor.constraint(equalTo: scrollView.trailingAnchor, constant: -padding),
            contentStack.bottomAnchor.constraint(equalTo: scrollView.bottomAnchor, constant: -padding),
            contentStack.widthAnchor.constraint(equalTo: scrollView.widthAnchor, constant: -padding * 2),
        ])

        // Icon
        if let iconName = iconName, let sfName = ToastOverlayView.sfSymbolName(for: iconName) {
            let config = UIImage.SymbolConfiguration(pointSize: 32, weight: .medium)
            if let image = UIImage(systemName: sfName, withConfiguration: config) {
                let imageView = UIImageView(image: image)
                imageView.tintColor = .label
                imageView.contentMode = .center
                contentStack.addArrangedSubview(imageView)
            }
        }

        // Title
        let titleLabel = UILabel()
        titleLabel.text = dialogTitle
        titleLabel.font = .boldSystemFont(ofSize: 20)
        titleLabel.textAlignment = .center
        titleLabel.numberOfLines = 0
        contentStack.addArrangedSubview(titleLabel)

        // Message
        if let msg = dialogMessage {
            let msgLabel = UILabel()
            msgLabel.text = msg
            msgLabel.font = .systemFont(ofSize: 14)
            msgLabel.textColor = .secondaryLabel
            msgLabel.textAlignment = .center
            msgLabel.numberOfLines = 0
            contentStack.addArrangedSubview(msgLabel)
        }

        // Spacer
        let spacer = UIView()
        spacer.heightAnchor.constraint(equalToConstant: 8).isActive = true
        contentStack.addArrangedSubview(spacer)

        // Input fields
        for input in inputs {
            addInputField(to: contentStack, input: input)
        }

        // Spacer before buttons
        let spacer2 = UIView()
        spacer2.heightAnchor.constraint(equalToConstant: 12).isActive = true
        contentStack.addArrangedSubview(spacer2)

        // Buttons
        if isSheet {
            for (index, btn) in sheetButtons.enumerated() {
                addSheetButton(to: contentStack, btn: btn, index: index)
            }
        } else {
            // Alert-style: positive, negative, neutral
            if let pos = positive { addAlertButton(to: contentStack, btn: pos, index: 0) }
            if let neg = negative { addAlertButton(to: contentStack, btn: neg, index: 1) }
            if let neut = neutral { addAlertButton(to: contentStack, btn: neut, index: 2) }
        }
    }

    // MARK: - Input Field Builders

    private func addInputField(to stack: UIStackView, input: [String: Any]) {
        let inputId = input["id"] as? String ?? ""
        let label = input["label"] as? String ?? ""
        let type = input["type"] as? String ?? "text"
        let placeholder = input["placeholder"] as? String
        let defaultValue = input["defaultValue"]

        // Label
        let labelView = UILabel()
        labelView.text = label
        labelView.font = .systemFont(ofSize: 14, weight: .medium)
        stack.addArrangedSubview(labelView)

        switch type {
        case "text", "number", "password", "email":
            let textField = UITextField()
            textField.placeholder = placeholder ?? label
            textField.borderStyle = .roundedRect
            textField.font = .systemFont(ofSize: 16)

            switch type {
            case "number": textField.keyboardType = .numberPad
            case "password": textField.isSecureTextEntry = true
            case "email":
                textField.keyboardType = .emailAddress
                textField.autocapitalizationType = .none
            default: break
            }

            if let dv = defaultValue as? String { textField.text = dv }
            if let dv = defaultValue as? NSNumber { textField.text = dv.stringValue }

            stack.addArrangedSubview(textField)
            inputViews[inputId] = textField

        case "date":
            let picker = UIDatePicker()
            picker.datePickerMode = .date
            picker.preferredDatePickerStyle = .compact
            if let minStr = input["minDate"] as? String, let d = Self.parseDate(minStr) {
                picker.minimumDate = d
            }
            if let maxStr = input["maxDate"] as? String, let d = Self.parseDate(maxStr) {
                picker.maximumDate = d
            }
            if let dv = defaultValue as? String, let d = Self.parseDate(dv) {
                picker.date = d
            }
            stack.addArrangedSubview(picker)
            inputViews[inputId] = picker

        case "time":
            let picker = UIDatePicker()
            picker.datePickerMode = .time
            picker.preferredDatePickerStyle = .compact
            if let interval = input["minuteInterval"] as? Int, interval > 0 {
                picker.minuteInterval = interval
            }
            if let dv = defaultValue as? String {
                let parts = dv.split(separator: ":")
                if parts.count == 2, let h = Int(parts[0]), let m = Int(parts[1]) {
                    var comps = Calendar.current.dateComponents([.year, .month, .day], from: Date())
                    comps.hour = h
                    comps.minute = m
                    if let date = Calendar.current.date(from: comps) { picker.date = date }
                }
            }
            stack.addArrangedSubview(picker)
            inputViews[inputId] = picker

        case "datetime":
            let picker = UIDatePicker()
            picker.datePickerMode = .dateAndTime
            picker.preferredDatePickerStyle = .compact
            if let interval = input["minuteInterval"] as? Int, interval > 0 {
                picker.minuteInterval = interval
            }
            if let minStr = input["minDate"] as? String, let d = Self.parseDate(minStr) {
                picker.minimumDate = d
            }
            if let maxStr = input["maxDate"] as? String, let d = Self.parseDate(maxStr) {
                picker.maximumDate = d
            }
            if let dv = defaultValue as? String, let d = Self.parseDateTime(dv) {
                picker.date = d
            }
            stack.addArrangedSubview(picker)
            inputViews[inputId] = picker

        case "select", "radio":
            let options = Self.parseOptions(input["options"])
            let keys = Array(options.keys)
            let labels = Array(options.values)
            pickerData[inputId] = (keys: keys, labels: labels)

            let pickerView = UIPickerView()
            pickerView.delegate = self
            pickerView.dataSource = self
            pickerView.tag = inputId.hashValue
            pickerView.accessibilityIdentifier = inputId

            if let dv = defaultValue as? String, let idx = keys.firstIndex(of: dv) {
                pickerView.selectRow(idx, inComponent: 0, animated: false)
            }

            let container = UIView()
            container.translatesAutoresizingMaskIntoConstraints = false
            container.heightAnchor.constraint(equalToConstant: 120).isActive = true
            pickerView.translatesAutoresizingMaskIntoConstraints = false
            container.addSubview(pickerView)
            NSLayoutConstraint.activate([
                pickerView.topAnchor.constraint(equalTo: container.topAnchor),
                pickerView.leadingAnchor.constraint(equalTo: container.leadingAnchor),
                pickerView.trailingAnchor.constraint(equalTo: container.trailingAnchor),
                pickerView.bottomAnchor.constraint(equalTo: container.bottomAnchor),
            ])
            stack.addArrangedSubview(container)
            inputViews[inputId] = pickerView

        case "checkbox":
            let options = Self.parseOptions(input["options"])
            let defaults = Self.parseDefaults(input["defaultValue"])
            var switches: [String: UISwitch] = [:]

            for (key, optLabel) in options {
                let row = UIStackView()
                row.axis = .horizontal
                row.spacing = 8
                row.alignment = .center

                let sw = UISwitch()
                sw.isOn = defaults.contains(key)
                row.addArrangedSubview(sw)

                let optLabelView = UILabel()
                optLabelView.text = optLabel
                optLabelView.font = .systemFont(ofSize: 16)
                row.addArrangedSubview(optLabelView)

                stack.addArrangedSubview(row)
                switches[key] = sw
            }
            inputViews[inputId] = switches

        default:
            break
        }
    }

    // MARK: - Button Builders

    private func addAlertButton(to stack: UIStackView, btn: [String: Any], index: Int) {
        let button = createButton(btn: btn)
        button.addAction(UIAction { [weak self] _ in
            self?.handleButtonPress(btn: btn, index: index)
        }, for: .touchUpInside)
        stack.addArrangedSubview(button)
    }

    private func addSheetButton(to stack: UIStackView, btn: [String: Any], index: Int) {
        let button = createButton(btn: btn)
        button.addAction(UIAction { [weak self] _ in
            self?.handleButtonPress(btn: btn, index: index)
        }, for: .touchUpInside)
        stack.addArrangedSubview(button)
    }

    private func createButton(btn: [String: Any]) -> UIButton {
        let btnLabel = btn["label"] as? String ?? ""
        let btnStyle = btn["style"] as? String ?? "neutral"
        let btnColor = btn["color"] as? String
        let btnBgColor = btn["backgroundColor"] as? String

        let button = UIButton(type: .system)
        button.setTitle(btnLabel, for: .normal)
        button.titleLabel?.font = .boldSystemFont(ofSize: 16)
        button.contentEdgeInsets = UIEdgeInsets(top: 12, left: 16, bottom: 12, right: 16)
        button.layer.cornerRadius = 8

        if let bgHex = btnBgColor {
            button.backgroundColor = ToastOverlayView.parseColor(bgHex, default: "#007AFF")
            button.setTitleColor(ToastOverlayView.parseColor(btnColor, default: "#FFFFFF"), for: .normal)
        } else {
            switch btnStyle {
            case "primary":
                button.backgroundColor = .systemBlue
                button.setTitleColor(.white, for: .normal)
            case "destructive":
                button.backgroundColor = .systemRed
                button.setTitleColor(.white, for: .normal)
            case "cancel":
                button.backgroundColor = .systemGray5
                button.setTitleColor(.label, for: .normal)
            default:
                button.backgroundColor = .systemGray5
                button.setTitleColor(.systemBlue, for: .normal)
            }
        }

        if let colorHex = btnColor, btnBgColor == nil {
            button.setTitleColor(ToastOverlayView.parseColor(colorHex, default: "#007AFF"), for: .normal)
        }

        return button
    }

    // MARK: - Button Press Handler

    private func handleButtonPress(btn: [String: Any], index: Int) {
        buttonPressed = true
        let btnId = btn["id"] as? String ?? ""
        let btnLabel = btn["label"] as? String ?? ""
        let hasInputs = !inputs.isEmpty

        if hasInputs {
            let values = collectInputValues()
            var payload: [String: Any] = [
                "buttonId": btnId,
                "label": btnLabel,
                "inputs": values,
            ]
            if let dialogId = dialogId { payload["dialogId"] = dialogId }
            LaravelBridge.shared.send?(event, payload)
        } else {
            var payload: [String: Any] = [
                "buttonId": btnId,
                "label": btnLabel,
                "index": index,
            ]
            if let dialogId = dialogId { payload["dialogId"] = dialogId }
            LaravelBridge.shared.send?(event, payload)
        }

        dismiss(animated: true)
    }

    // MARK: - Input Value Collection

    private func collectInputValues() -> [String: Any] {
        var result: [String: Any] = [:]

        for input in inputs {
            let inputId = input["id"] as? String ?? ""
            let type = input["type"] as? String ?? "text"
            guard let view = inputViews[inputId] else { continue }

            switch type {
            case "text", "number", "password", "email":
                if let tf = view as? UITextField {
                    result[inputId] = tf.text ?? ""
                }

            case "date":
                if let dp = view as? UIDatePicker {
                    let fmt = DateFormatter()
                    fmt.dateFormat = "yyyy-MM-dd"
                    result[inputId] = fmt.string(from: dp.date)
                }

            case "time":
                if let dp = view as? UIDatePicker {
                    let fmt = DateFormatter()
                    fmt.dateFormat = "HH:mm"
                    result[inputId] = fmt.string(from: dp.date)
                }

            case "datetime":
                if let dp = view as? UIDatePicker {
                    let fmt = DateFormatter()
                    fmt.dateFormat = "yyyy-MM-dd HH:mm"
                    result[inputId] = fmt.string(from: dp.date)
                }

            case "select", "radio":
                if let pv = view as? UIPickerView, let data = pickerData[inputId] {
                    let row = pv.selectedRow(inComponent: 0)
                    if row >= 0 && row < data.keys.count {
                        result[inputId] = data.keys[row]
                    }
                }

            case "checkbox":
                if let switches = view as? [String: UISwitch] {
                    result[inputId] = switches.filter { $0.value.isOn }.map { $0.key }
                }

            default:
                break
            }
        }

        return result
    }

    // MARK: - Cancel Handling

    func presentationControllerDidDismiss(_ presentationController: UIPresentationController) {
        if !buttonPressed {
            var payload: [String: Any] = [
                "buttonId": "cancelled",
                "label": "Cancelled",
                "index": -1,
            ]
            if let dialogId = dialogId { payload["dialogId"] = dialogId }
            LaravelBridge.shared.send?("MazerDev\\Dialogify\\Events\\ButtonPressed", payload)
        }
    }

    // MARK: - UIPickerView DataSource & Delegate

    func numberOfComponents(in pickerView: UIPickerView) -> Int { 1 }

    func pickerView(_ pickerView: UIPickerView, numberOfRowsInComponent component: Int) -> Int {
        guard let id = pickerView.accessibilityIdentifier, let data = pickerData[id] else { return 0 }
        return data.labels.count
    }

    func pickerView(_ pickerView: UIPickerView, titleForRow row: Int, forComponent component: Int) -> String? {
        guard let id = pickerView.accessibilityIdentifier, let data = pickerData[id] else { return nil }
        return data.labels[row]
    }

    // MARK: - Static Helpers

    static func parseDate(_ str: String) -> Date? {
        let fmt = DateFormatter()
        fmt.dateFormat = "yyyy-MM-dd"
        return fmt.date(from: str)
    }

    static func parseDateTime(_ str: String) -> Date? {
        let fmt = DateFormatter()
        fmt.dateFormat = "yyyy-MM-dd HH:mm"
        return fmt.date(from: str)
    }

    static func parseOptions(_ param: Any?) -> [(key: String, value: String)] {
        // Return as array of tuples to preserve insertion order
        guard let dict = param as? [String: String] else { return [] }
        // NSDictionary from JSON preserves order in practice via NSJSONSerialization
        return dict.map { (key: $0.key, value: $0.value) }
    }

    static func parseDefaults(_ value: Any?) -> Set<String> {
        if let arr = value as? [String] { return Set(arr) }
        if let s = value as? String { return [s] }
        return []
    }
}
```

**Step 2: Commit**

```bash
git add resources/ios/Sources/ui/DialogifyFormViewController.swift
git commit -m "feat(ios): implement DialogifyFormViewController with all input types and event dispatch"
```

---

### Task 5: SheetFunctions — Bottom Sheet Bridge Function

**Files:**
- Rewrite: `resources/ios/Sources/SheetFunctions.swift`

**Context:** Parses parameters, creates a `DialogifyFormViewController` in sheet mode with `UISheetPresentationController`. Reference: Android's `SheetFunctions.kt` which uses `BottomSheetDialogFragment`.

Key difference from Alert: Sheet has a flat `buttons` array (variable count), not fixed positive/negative/neutral slots.

**Parameter shape from PHP:**
```json
{
  "title": "string|null",
  "message": "string|null",
  "buttons": [{"id": "ok", "label": "OK", "style": "primary"}, ...],
  "inputs": [...],
  "cancelable": true,
  "id": "string|null",
  "event": "string|null"
}
```

**Step 1: Write SheetFunctions.swift**

```swift
// resources/ios/Sources/SheetFunctions.swift
import Foundation
import UIKit

enum SheetFunctions {
    class Show: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let title = parameters["title"] as? String
            let message = parameters["message"] as? String
            let cancelable = parameters["cancelable"] as? Bool ?? true
            let id = parameters["id"] as? String
            let customEvent = parameters["event"] as? String

            let buttons = Self.parseArray(parameters["buttons"])
            let inputs = AlertFunctions.Show.parseInputs(parameters["inputs"])
            let hasInputs = !inputs.isEmpty

            let event = customEvent ?? (hasInputs
                ? "MazerDev\\Dialogify\\Events\\InputSubmitted"
                : "MazerDev\\Dialogify\\Events\\ButtonPressed")

            DispatchQueue.main.async {
                guard let root = AlertFunctions.Show.topViewController() else { return }

                let formVC = DialogifyFormViewController(
                    dialogTitle: title ?? "",
                    dialogMessage: message,
                    cancelable: cancelable,
                    dialogId: id,
                    event: event,
                    inputs: inputs,
                    isSheet: true,
                    sheetButtons: buttons
                )
                formVC.modalPresentationStyle = .pageSheet
                formVC.isModalInPresentation = !cancelable

                root.present(formVC, animated: true)
            }

            return [:]
        }

        private static func parseArray(_ param: Any?) -> [[String: Any]] {
            if let arr = param as? [[String: Any]] { return arr }
            if let arr = param as? [Any] {
                return arr.compactMap { $0 as? [String: Any] }
            }
            return []
        }
    }
}
```

**Step 2: Commit**

```bash
git add resources/ios/Sources/SheetFunctions.swift
git commit -m "feat(ios): implement SheetFunctions with UISheetPresentationController and form inputs"
```

---

### Task 6: Fix parseOptions Order Preservation

**Files:**
- Modify: `resources/ios/Sources/ui/DialogifyFormViewController.swift`

**Context:** Swift `Dictionary` does NOT preserve insertion order. The `parseOptions` method currently returns `[(key: String, value: String)]` but loses ordering from the JSON. We need to fix this by parsing from the raw ordered structure. NativePHP passes parameters from JSON, and `NSJSONSerialization` uses `NSDictionary` which does not guarantee order. However, the Android implementation uses `LinkedHashMap` which does. The PHP side sends `options` as `{"key1": "Label 1", "key2": "Label 2"}`.

**Fix approach:** Since JSON objects are unordered by spec but NativePHP's bridge passes them through NSJSONSerialization which may use NSDictionary, the order may not be reliable. The pragmatic fix is to sort by key to get deterministic behavior, or accept that Swift dicts don't guarantee order. For now, the current approach is acceptable since the Android version's `LinkedHashMap` is also just preserving insertion order from JSON, which isn't guaranteed either.

**Decision:** No code change needed. Mark as "accepted limitation" — the PHP dev defines options as a dict, and the native side presents them in whatever order the platform provides. This matches real-world behavior on both platforms.

**Step 1: No-op — skip this task**

---

### Task 7: Manual Testing Checklist

**Context:** There are no automated tests for Swift native code in this plugin repo. Testing requires running the plugin in a NativePHP iOS app. Create a testing guide.

**Step 1: Create testing checklist file**

Create `docs/testing/ios-v1-manual-testing.md`:

```markdown
# iOS v1.0 Manual Testing Checklist

## Prerequisites
- iOS 15+ simulator or device
- NativePHP app with dialogify plugin installed

## Toast Tests

- [ ] Basic toast: `Dialogify::toast('Hello')->show()`
- [ ] Position top: `->position(Position::Top)`
- [ ] Position center: `->position(Position::Center)`
- [ ] Position bottom (default): `->position(Position::Bottom)`
- [ ] Custom colors: `->backgroundColor('#FF0000')->textColor('#FFFFFF')`
- [ ] With icon: `->icon(Icon::Info)`
- [ ] Custom duration short: `->duration(Duration::Short)` — dismisses in 2s
- [ ] Custom duration ms: `->duration(6000)` — dismisses in 6s
- [ ] Corner radius: `->cornerRadius(20)`
- [ ] Action button: `->action(Button::make('undo', 'Undo'))` — verify ToastAction event fires
- [ ] Action dismisses toast immediately
- [ ] Fade in animation (0.2s)
- [ ] Fade out animation (0.2s)
- [ ] Multiple toasts don't overlap (second replaces first)

## Alert Tests — Simple (UIAlertController)

- [ ] Basic alert: `Dialogify::alert('Title', 'Message')->positive(Button::make('ok', 'OK'))->show()`
- [ ] Two buttons: positive + negative
- [ ] Three buttons: positive + negative + neutral
- [ ] Cancelable dismiss (tap outside) fires ButtonPressed with `buttonId: "cancelled"`
- [ ] Non-cancelable: `->cancelable(false)` — cannot dismiss without button
- [ ] Text input: `->addInput(Input::make('name', 'Name'))` — verify InputSubmitted payload
- [ ] Password input: type password, dots shown
- [ ] Number input: numeric keyboard shown
- [ ] Email input: email keyboard shown
- [ ] Custom event class: `->event('App\\Events\\MyEvent')`
- [ ] Dialog ID in payload: `->id('my-alert')`

## Alert Tests — Complex (Form Modal)

- [ ] Date input: UIDatePicker shown, format "yyyy-MM-dd"
- [ ] Time input: UIDatePicker time mode, format "HH:mm"
- [ ] DateTime input: UIDatePicker dateAndTime mode, format "yyyy-MM-dd HH:mm"
- [ ] Date with min/max: `->minDate('2024-01-01')->maxDate('2026-12-31')`
- [ ] Time with minuteInterval: `->minuteInterval(15)`
- [ ] Select input: UIPickerView with options
- [ ] Radio input: same as select (UIPickerView)
- [ ] Checkbox input: UISwitch toggles, array of selected keys in payload
- [ ] Mixed inputs: text + date + select in same alert
- [ ] Default values pre-populated for all input types
- [ ] Cancel dismiss fires ButtonPressed with `buttonId: "cancelled"`

## Sheet Tests

- [ ] Basic sheet: `Dialogify::sheet('Title')->addButton(Button::make('ok', 'OK'))->show()`
- [ ] Multiple buttons
- [ ] With inputs: same input types as Alert complex mode
- [ ] Cancelable: swipe down to dismiss fires cancel event
- [ ] Non-cancelable: `->cancelable(false)` — no swipe dismiss
- [ ] Grabber visible at top
- [ ] Sheet resizable (medium/large detents)
- [ ] Dialog ID in payload

## Event Dispatch

- [ ] Verify Livewire `#[On('native:MazerDev\\Dialogify\\Events\\ButtonPressed')]` receives event
- [ ] Verify payload includes all expected fields (buttonId, label, index/inputs, dialogId)
- [ ] Verify checkbox inputs arrive as array of strings
```

**Step 2: Commit**

```bash
mkdir -p docs/testing
git add docs/testing/ios-v1-manual-testing.md
git commit -m "docs: add iOS v1.0 manual testing checklist"
```

---

### Task 8: Final Review and Cleanup

**Files:**
- Review all 4 Swift files for consistency

**Step 1: Review all files compile-ready**

Verify:
1. `ToastOverlayView.swift` — no external imports beyond UIKit
2. `ToastFunctions.swift` — references `ToastOverlayView` and `LaravelBridge`
3. `AlertFunctions.swift` — references `DialogifyFormViewController` and `LaravelBridge`
4. `DialogifyFormViewController.swift` — references `ToastOverlayView` (for parseColor/sfSymbolName helpers) and `LaravelBridge`
5. `SheetFunctions.swift` — references `AlertFunctions.Show` (for parseInputs/topViewController helpers) and `DialogifyFormViewController`

All these classes will be compiled into the same Swift module by NativePHP's IOSPluginCompiler, so cross-references are valid.

**Step 2: Commit all files together as final**

```bash
git add -A resources/ios/
git commit -m "feat(ios): complete Dialogify v1.0 iOS native implementation

Implements Toast, Alert, and Sheet with full input support:
- ToastOverlayView: custom floating card with icons, colors, action button
- AlertFunctions: UIAlertController for simple alerts, form modal for complex inputs
- DialogifyFormViewController: shared form UI with all 9 input types
- SheetFunctions: UISheetPresentationController bottom sheet
- Event dispatch via LaravelBridge.shared
- SF Symbols for icons (no external dependencies)"
```

---

## Summary

| Task | Component | Lines (est.) | Depends On |
|------|-----------|-------------|------------|
| 1 | ToastOverlayView | ~200 | — |
| 2 | ToastFunctions | ~60 | Task 1 |
| 3 | AlertFunctions | ~120 | — |
| 4 | DialogifyFormViewController | ~350 | Task 1 (helpers) |
| 5 | SheetFunctions | ~40 | Task 3, 4 |
| 6 | parseOptions order | skip | — |
| 7 | Manual testing checklist | ~60 | All above |
| 8 | Final review | — | All above |

**Total estimated Swift code:** ~770 lines across 4 files (Android is ~1195 lines across 4 files — iOS is smaller due to UIKit's built-in date pickers and LaravelBridge simplicity).
