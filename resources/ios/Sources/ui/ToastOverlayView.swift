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
            wrapper.isUserInteractionEnabled = true
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
