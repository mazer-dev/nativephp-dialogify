// resources/ios/Sources/SheetFunctions.swift
import Foundation

enum SheetFunctions {
    class Show: BridgeFunction {
        required init(webView: Any) {}

        func execute(parameters: [String: Any]) throws -> [String: Any] {
            print("[Dialogify] ⚠️ Sheet: iOS implementation pending — no UI will be shown")
            return [:]
        }
    }
}
