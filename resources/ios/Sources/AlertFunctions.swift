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
                }
                textFieldMap.append((inputId, alert.textFields!.last!))
            }

            var buttonPressed = false

            func addButton(_ btnDict: [String: Any]?, defaultStyle: UIAlertAction.Style, index: Int) {
                guard let btn = btnDict else { return }
                let btnId = btn["id"] as? String ?? ""
                let btnLabel = btn["label"] as? String ?? ""
                let btnStyle = btn["style"] as? String

                let actionStyle: UIAlertAction.Style
                switch btnStyle {
                case "destructive": actionStyle = .destructive
                case "cancel": actionStyle = .cancel
                default: actionStyle = defaultStyle
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

            addButton(positive, defaultStyle: .default, index: 0)
            addButton(negative, defaultStyle: .cancel, index: 1)
            addButton(neutral, defaultStyle: .default, index: 2)

            // If no buttons at all, add a default OK
            if positive == nil && negative == nil && neutral == nil {
                alert.addAction(UIAlertAction(title: "OK", style: .default) { _ in
                    buttonPressed = true
                })
            }

            // Handle cancel-dismiss: if cancelable and no explicit cancel-style button exists,
            // add a hidden cancel action so the dismiss fires the "cancelled" event.
            // UIAlertController with .alert style can be dismissed by tapping outside on iPad.
            let hasCancelAction = [positive, negative, neutral].compactMap { $0 }.contains {
                ($0["style"] as? String) == "cancel"
            }
            if cancelable && !hasCancelAction {
                alert.addAction(UIAlertAction(title: "Cancel", style: .cancel) { _ in
                    if !buttonPressed {
                        var payload: [String: Any] = [
                            "buttonId": "cancelled",
                            "label": "Cancelled",
                            "index": -1,
                        ]
                        if let id = id { payload["dialogId"] = id }
                        LaravelBridge.shared.send?("MazerDev\\Dialogify\\Events\\ButtonPressed", payload)
                    }
                })
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
