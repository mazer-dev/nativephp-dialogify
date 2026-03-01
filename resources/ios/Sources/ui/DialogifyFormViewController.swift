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
        if !dialogTitle.isEmpty {
            let titleLabel = UILabel()
            titleLabel.text = dialogTitle
            titleLabel.font = .boldSystemFont(ofSize: 20)
            titleLabel.textAlignment = .center
            titleLabel.numberOfLines = 0
            contentStack.addArrangedSubview(titleLabel)
        }

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
        if !inputs.isEmpty {
            let spacer = UIView()
            spacer.heightAnchor.constraint(equalToConstant: 8).isActive = true
            contentStack.addArrangedSubview(spacer)
        }

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
                addButton(to: contentStack, btn: btn, index: index)
            }
        } else {
            if let pos = positive { addButton(to: contentStack, btn: pos, index: 0) }
            if let neg = negative { addButton(to: contentStack, btn: neg, index: 1) }
            if let neut = neutral { addButton(to: contentStack, btn: neut, index: 2) }
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
            let keys = options.map { $0.key }
            let labels = options.map { $0.value }
            pickerData[inputId] = (keys: keys, labels: labels)

            let pickerView = UIPickerView()
            pickerView.delegate = self
            pickerView.dataSource = self
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

    // MARK: - Button Builder

    private func addButton(to stack: UIStackView, btn: [String: Any], index: Int) {
        let btnLabel = btn["label"] as? String ?? ""
        let btnStyle = btn["style"] as? String ?? "neutral"
        let btnColor = btn["color"] as? String
        let btnBgColor = btn["backgroundColor"] as? String

        let button = UIButton(type: .system)
        button.setTitle(btnLabel, for: .normal)
        button.titleLabel?.font = .boldSystemFont(ofSize: 16)
        button.contentEdgeInsets = UIEdgeInsets(top: 12, left: 16, bottom: 12, right: 16)
        button.layer.cornerRadius = 8
        button.clipsToBounds = true

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

        button.addAction(UIAction { [weak self] _ in
            self?.handleButtonPress(btn: btn, index: index)
        }, for: .touchUpInside)
        stack.addArrangedSubview(button)
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
        guard let dict = param as? [String: String] else { return [] }
        return dict.map { (key: $0.key, value: $0.value) }
    }

    static func parseDefaults(_ value: Any?) -> Set<String> {
        if let arr = value as? [String] { return Set(arr) }
        if let s = value as? String { return [s] }
        return []
    }
}
