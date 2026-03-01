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
