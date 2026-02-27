// resources/ios/Sources/ToastFunctions.swift
import Foundation

enum ToastFunctions {
    class Show: BridgeFunction {
        required init(webView: Any) {}

        func execute(parameters: [String: Any]) throws -> [String: Any] {
            print("[Dialogify] ⚠️ Toast: iOS implementation pending — no UI will be shown")
            return ["success": true]
        }
    }
}
