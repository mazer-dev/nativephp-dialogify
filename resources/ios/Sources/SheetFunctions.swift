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
