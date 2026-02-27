// resources/ios/Sources/AlertFunctions.swift
import Foundation

enum AlertFunctions {
    class Show: BridgeFunction {
        required init(webView: Any) {}

        func execute(parameters: [String: Any]) throws -> [String: Any] {
            print("[Dialogify] ⚠️ Alert: iOS implementation pending — no UI will be shown")
            return [:]
        }
    }
}
