import Foundation
import UIKit

// MARK: - Tab Bar Manager (Singleton)

class TabBarManager {
    static let shared = TabBarManager()

    private var tabBar: UITabBar?
    private var tabBarBottomConstraint: NSLayoutConstraint?
    private var webViewBottomConstraint: NSLayoutConstraint?
    private var tabConfigs: [[String: Any]] = []
    private var styleConfig: [String: Any] = [:]
    private var isVisible: Bool = true

    private init() {}

    // MARK: - Configuration

    func configure(parameters: [String: Any]) {
        guard let tabs = parameters["tabs"] as? [[String: Any]] else { return }

        self.tabConfigs = tabs
        self.styleConfig = parameters["style"] as? [String: Any] ?? [:]

        DispatchQueue.main.async {
            self.setupTabBar()
            self.applyStyle()
            self.applyBadges()

            // Set active tab
            if let activeTabId = parameters["active_tab"] as? String {
                self.selectTab(byId: activeTabId)
            } else {
                // Default to first tab
                self.tabBar?.selectedItem = self.tabBar?.items?.first
            }
        }
    }

    func update(parameters: [String: Any]) {
        guard let tabs = parameters["tabs"] as? [[String: Any]] else { return }

        self.tabConfigs = tabs

        if let style = parameters["style"] as? [String: Any] {
            self.styleConfig = style
        }

        DispatchQueue.main.async {
            self.rebuildTabItems()
            self.applyStyle()
            self.applyBadges()

            if let activeTabId = parameters["active_tab"] as? String {
                self.selectTab(byId: activeTabId)
            }
        }
    }

    func setActive(tabId: String) {
        DispatchQueue.main.async {
            self.selectTab(byId: tabId)
        }
    }

    func setBadge(tabId: String, count: Int?) {
        DispatchQueue.main.async {
            guard let items = self.tabBar?.items else { return }
            guard let index = self.tabConfigs.firstIndex(where: { $0["id"] as? String == tabId }) else { return }
            guard index < items.count else { return }

            if let count = count, count > 0 {
                items[index].badgeValue = "\(count)"
            } else {
                items[index].badgeValue = nil
            }
        }
    }

    func show() {
        guard !isVisible else { return }
        isVisible = true

        DispatchQueue.main.async {
            UIView.animate(withDuration: 0.3) {
                self.tabBar?.isHidden = false
                self.tabBarBottomConstraint?.constant = 0
                self.tabBar?.superview?.layoutIfNeeded()
            }
        }
    }

    func hide() {
        guard isVisible else { return }
        isVisible = false

        DispatchQueue.main.async {
            UIView.animate(withDuration: 0.3) {
                self.tabBarBottomConstraint?.constant = 100
                self.tabBar?.superview?.layoutIfNeeded()
            } completion: { _ in
                self.tabBar?.isHidden = true
            }
        }
    }

    // MARK: - Private Setup

    private func setupTabBar() {
        // Remove existing tab bar if reconfiguring
        tabBar?.removeFromSuperview()

        guard let window = UIApplication.shared.connectedScenes
            .compactMap({ $0 as? UIWindowScene })
            .flatMap({ $0.windows })
            .first(where: { $0.isKeyWindow }),
              let rootView = window.rootViewController?.view else { return }

        let bar = UITabBar()
        bar.delegate = TabBarDelegateHandler.shared
        bar.translatesAutoresizingMaskIntoConstraints = false

        rootView.addSubview(bar)

        let bottomConstraint = bar.bottomAnchor.constraint(equalTo: rootView.safeAreaLayoutGuide.bottomAnchor)

        NSLayoutConstraint.activate([
            bar.leadingAnchor.constraint(equalTo: rootView.leadingAnchor),
            bar.trailingAnchor.constraint(equalTo: rootView.trailingAnchor),
            bottomConstraint,
        ])

        self.tabBar = bar
        self.tabBarBottomConstraint = bottomConstraint

        rebuildTabItems()
    }

    private func rebuildTabItems() {
        var items: [UITabBarItem] = []

        for (index, config) in tabConfigs.enumerated() {
            let label = config["label"] as? String ?? ""
            let iconName = config["icon"] as? String ?? ""
            let iconType = config["icon_type"] as? String ?? "system"
            let visible = config["visible"] as? Bool ?? true

            guard visible else { continue }

            let image: UIImage?
            if iconType == "custom" {
                image = UIImage(named: iconName)
            } else {
                image = UIImage(systemName: iconName)
            }

            // Active icon
            var selectedImage: UIImage? = nil
            if let activeIconName = config["active_icon"] as? String {
                let activeIconType = config["active_icon_type"] as? String ?? "system"
                if activeIconType == "custom" {
                    selectedImage = UIImage(named: activeIconName)
                } else {
                    selectedImage = UIImage(systemName: activeIconName)
                }
            }

            let item = UITabBarItem(title: label, image: image, selectedImage: selectedImage)
            item.tag = index

            items.append(item)
        }

        tabBar?.setItems(items, animated: false)
    }

    private func applyStyle() {
        guard let bar = tabBar else { return }

        let isDarkMode = bar.traitCollection.userInterfaceStyle == .dark
        let darkStyle = styleConfig["dark"] as? [String: Any] ?? [:]

        // Resolve color based on current appearance
        func resolveColor(_ lightKey: String) -> UIColor? {
            if isDarkMode, let darkHex = darkStyle[lightKey] as? String {
                return UIColor(hex: darkHex)
            }
            if let lightHex = styleConfig[lightKey] as? String {
                return UIColor(hex: lightHex)
            }
            return nil
        }

        let appearance = UITabBarAppearance()

        if let translucent = styleConfig["translucent"] as? Bool, !translucent {
            appearance.configureWithOpaqueBackground()
        } else {
            appearance.configureWithDefaultBackground()
        }

        if let bgColor = resolveColor("background_color") {
            appearance.backgroundColor = bgColor
        }

        if let activeColor = resolveColor("active_color") {
            bar.tintColor = activeColor
        }

        if let inactiveColor = resolveColor("inactive_color") {
            appearance.stackedLayoutAppearance.normal.iconColor = inactiveColor
            appearance.stackedLayoutAppearance.normal.titleTextAttributes = [.foregroundColor: inactiveColor]
        }

        if let borderColor = resolveColor("border_color") {
            appearance.shadowColor = borderColor
        }

        // Badge styling
        if let badgeColor = resolveColor("badge_color") {
            appearance.stackedLayoutAppearance.normal.badgeBackgroundColor = badgeColor
            appearance.stackedLayoutAppearance.selected.badgeBackgroundColor = badgeColor
        }

        if let badgeTextColor = resolveColor("badge_text_color") {
            appearance.stackedLayoutAppearance.normal.badgeTextAttributes = [.foregroundColor: badgeTextColor]
            appearance.stackedLayoutAppearance.selected.badgeTextAttributes = [.foregroundColor: badgeTextColor]
        }

        bar.standardAppearance = appearance
        bar.scrollEdgeAppearance = appearance
    }

    private func applyBadges() {
        guard let items = tabBar?.items else { return }

        for (index, config) in tabConfigs.enumerated() {
            guard index < items.count else { break }

            if let badge = config["badge"] as? Int, badge > 0 {
                items[index].badgeValue = "\(badge)"

                if let badgeColor = config["badge_color"] as? String {
                    items[index].badgeColor = UIColor(hex: badgeColor)
                }
            }
        }
    }

    private func selectTab(byId tabId: String) {
        guard let items = tabBar?.items else { return }
        guard let index = tabConfigs.firstIndex(where: { $0["id"] as? String == tabId }) else { return }
        guard index < items.count else { return }

        tabBar?.selectedItem = items[index]
    }

    // MARK: - Tab Tap Handling (called by delegate)

    func handleTabTap(at index: Int) {
        guard index < tabConfigs.count else { return }

        let config = tabConfigs[index]
        let id = config["id"] as? String ?? ""
        let type = config["type"] as? String ?? "url"
        let url = config["url"] as? String
        let action = config["action"] as? String

        // Dispatch TabSelected event for all taps
        let selectedPayload: [String: Any] = [
            "id": id,
            "url": url ?? "",
            "index": index,
        ]

        LaravelBridge.shared.send?(
            "Krakero\\TabBar\\Events\\TabSelected",
            selectedPayload
        )

        if type == "url", let url = url {
            // Navigate the webview
            navigateWebView(to: url)
        } else if type == "action", let action = action {
            // Dispatch action event
            let actionPayload: [String: Any] = [
                "id": id,
                "action": action,
            ]

            LaravelBridge.shared.send?(
                "Krakero\\TabBar\\Events\\TabActionTriggered",
                actionPayload
            )
        }
    }

    private func navigateWebView(to url: String) {
        // Inject JavaScript to navigate the webview
        guard let window = UIApplication.shared.connectedScenes
            .compactMap({ $0 as? UIWindowScene })
            .flatMap({ $0.windows })
            .first(where: { $0.isKeyWindow }),
              let rootVC = window.rootViewController else { return }

        // Find the WKWebView in the view hierarchy
        if let webView = findWebView(in: rootVC.view) {
            let js = "window.location.href = '\(url)';"
            webView.evaluateJavaScript(js, completionHandler: nil)
        }
    }

    private func findWebView(in view: UIView) -> WKWebView? {
        if let webView = view as? WKWebView {
            return webView
        }
        for subview in view.subviews {
            if let found = findWebView(in: subview) {
                return found
            }
        }
        return nil
    }
}

// MARK: - Tab Bar Delegate

class TabBarDelegateHandler: NSObject, UITabBarDelegate {
    static let shared = TabBarDelegateHandler()

    func tabBar(_ tabBar: UITabBar, didSelect item: UITabBarItem) {
        TabBarManager.shared.handleTabTap(at: item.tag)
    }
}

// MARK: - UIColor Hex Extension

extension UIColor {
    convenience init?(hex: String) {
        var hexSanitized = hex.trimmingCharacters(in: .whitespacesAndNewlines)
        hexSanitized = hexSanitized.replacingOccurrences(of: "#", with: "")

        var rgb: UInt64 = 0
        guard Scanner(string: hexSanitized).scanHexInt64(&rgb) else { return nil }

        let length = hexSanitized.count
        if length == 6 {
            self.init(
                red: CGFloat((rgb & 0xFF0000) >> 16) / 255.0,
                green: CGFloat((rgb & 0x00FF00) >> 8) / 255.0,
                blue: CGFloat(rgb & 0x0000FF) / 255.0,
                alpha: 1.0
            )
        } else if length == 8 {
            self.init(
                red: CGFloat((rgb & 0xFF000000) >> 24) / 255.0,
                green: CGFloat((rgb & 0x00FF0000) >> 16) / 255.0,
                blue: CGFloat((rgb & 0x0000FF00) >> 8) / 255.0,
                alpha: CGFloat(rgb & 0x000000FF) / 255.0
            )
        } else {
            return nil
        }
    }
}

// MARK: - WKWebView Import

import WebKit

// MARK: - Bridge Functions

enum TabBarFunctions {

    class Configure: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            TabBarManager.shared.configure(parameters: parameters)
            return BridgeResponse.success(data: ["configured": true])
        }
    }

    class Update: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            TabBarManager.shared.update(parameters: parameters)
            return BridgeResponse.success(data: ["updated": true])
        }
    }

    class SetActive: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let id = parameters["id"] as? String else {
                return BridgeResponse.error(message: "Missing required parameter: id")
            }
            TabBarManager.shared.setActive(tabId: id)
            return BridgeResponse.success(data: ["active": id])
        }
    }

    class SetBadge: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let id = parameters["id"] as? String else {
                return BridgeResponse.error(message: "Missing required parameter: id")
            }
            let count = parameters["count"] as? Int
            TabBarManager.shared.setBadge(tabId: id, count: count)
            return BridgeResponse.success(data: ["id": id, "badge": count ?? 0])
        }
    }

    class Show: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            TabBarManager.shared.show()
            return BridgeResponse.success(data: ["visible": true])
        }
    }

    class Hide: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            TabBarManager.shared.hide()
            return BridgeResponse.success(data: ["visible": false])
        }
    }
}
