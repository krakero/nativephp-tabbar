package com.krakero.plugins.tabbar

import android.app.Activity
import android.content.res.ColorStateList
import android.graphics.Color
import android.os.Handler
import android.os.Looper
import android.view.Gravity
import android.view.Menu
import android.view.View
import android.view.ViewGroup
import android.webkit.WebView
import android.widget.FrameLayout
import com.google.android.material.bottomnavigation.BottomNavigationView
import com.google.android.material.badge.BadgeDrawable
import com.nativephp.mobile.bridge.BridgeFunction
import com.nativephp.mobile.bridge.BridgeResponse
import org.json.JSONObject

// ============================================================
// Tab Bar Manager (Singleton)
// ============================================================

object TabBarManager {

    private var bottomNav: BottomNavigationView? = null
    private var tabConfigs: List<Map<String, Any>> = emptyList()
    private var styleConfig: Map<String, Any> = emptyMap()
    private var isVisible: Boolean = true
    private var activity: Activity? = null

    // SF Symbol name -> Material Icon mapping
    private val iconMap = mapOf(
        "house" to android.R.drawable.ic_menu_recent_history,
        "house.fill" to android.R.drawable.ic_menu_recent_history,
        "magnifyingglass" to android.R.drawable.ic_menu_search,
        "plus.circle" to android.R.drawable.ic_menu_add,
        "plus.circle.fill" to android.R.drawable.ic_menu_add,
        "bell" to android.R.drawable.ic_popup_reminder,
        "bell.fill" to android.R.drawable.ic_popup_reminder,
        "person.circle" to android.R.drawable.ic_menu_myplaces,
        "person.circle.fill" to android.R.drawable.ic_menu_myplaces,
        "gear" to android.R.drawable.ic_menu_preferences,
        "gearshape" to android.R.drawable.ic_menu_preferences,
        "star" to android.R.drawable.btn_star_big_off,
        "star.fill" to android.R.drawable.btn_star_big_on,
        "heart" to android.R.drawable.ic_menu_close_clear_cancel,
        "heart.fill" to android.R.drawable.ic_menu_close_clear_cancel,
        "envelope" to android.R.drawable.ic_dialog_email,
        "camera" to android.R.drawable.ic_menu_camera,
        "camera.fill" to android.R.drawable.ic_menu_camera,
        "map" to android.R.drawable.ic_menu_mapmode,
        "info.circle" to android.R.drawable.ic_menu_info_details,
        "trash" to android.R.drawable.ic_menu_delete,
        "pencil" to android.R.drawable.ic_menu_edit,
        "square.and.arrow.up" to android.R.drawable.ic_menu_share,
    )

    // ----------------------------------------------------------
    // Public API
    // ----------------------------------------------------------

    fun configure(activity: Activity, parameters: Map<String, Any>) {
        this.activity = activity

        @Suppress("UNCHECKED_CAST")
        val tabs = parameters["tabs"] as? List<Map<String, Any>> ?: return
        this.tabConfigs = tabs
        @Suppress("UNCHECKED_CAST")
        this.styleConfig = parameters["style"] as? Map<String, Any> ?: emptyMap()

        Handler(Looper.getMainLooper()).post {
            setupBottomNav(activity)
            applyStyle()
            applyBadges()

            val activeTabId = parameters["active_tab"] as? String
            if (activeTabId != null) {
                selectTab(activeTabId)
            }
        }
    }

    fun update(activity: Activity, parameters: Map<String, Any>) {
        this.activity = activity

        @Suppress("UNCHECKED_CAST")
        val tabs = parameters["tabs"] as? List<Map<String, Any>> ?: return
        this.tabConfigs = tabs

        @Suppress("UNCHECKED_CAST")
        val style = parameters["style"] as? Map<String, Any>
        if (style != null) {
            this.styleConfig = style
        }

        Handler(Looper.getMainLooper()).post {
            rebuildMenu()
            applyStyle()
            applyBadges()

            val activeTabId = parameters["active_tab"] as? String
            if (activeTabId != null) {
                selectTab(activeTabId)
            }
        }
    }

    fun setActive(tabId: String) {
        Handler(Looper.getMainLooper()).post {
            selectTab(tabId)
        }
    }

    fun setBadge(tabId: String, count: Int?) {
        Handler(Looper.getMainLooper()).post {
            val nav = bottomNav ?: return@post
            val index = tabConfigs.indexOfFirst { it["id"] == tabId }
            if (index < 0) return@post

            val menuItem = nav.menu.getItem(index) ?: return@post

            if (count != null && count > 0) {
                val badge = nav.getOrCreateBadge(menuItem.itemId)
                badge.number = count
                badge.isVisible = true
            } else {
                nav.removeBadge(menuItem.itemId)
            }
        }
    }

    fun show() {
        if (isVisible) return
        isVisible = true

        Handler(Looper.getMainLooper()).post {
            bottomNav?.visibility = View.VISIBLE
        }
    }

    fun hide() {
        if (!isVisible) return
        isVisible = false

        Handler(Looper.getMainLooper()).post {
            bottomNav?.visibility = View.GONE
        }
    }

    // ----------------------------------------------------------
    // Private Setup
    // ----------------------------------------------------------

    private fun setupBottomNav(activity: Activity) {
        // Remove existing nav if reconfiguring
        bottomNav?.let {
            (it.parent as? ViewGroup)?.removeView(it)
        }

        val nav = BottomNavigationView(activity)
        nav.id = View.generateViewId()
        nav.labelVisibilityMode = BottomNavigationView.LABEL_VISIBILITY_LABELED

        // Add to root view
        val rootView = activity.findViewById<ViewGroup>(android.R.id.content)
        val params = FrameLayout.LayoutParams(
            FrameLayout.LayoutParams.MATCH_PARENT,
            FrameLayout.LayoutParams.WRAP_CONTENT
        )
        params.gravity = Gravity.BOTTOM
        rootView.addView(nav, params)

        // Handle tab selection
        nav.setOnItemSelectedListener { menuItem ->
            val index = indexForMenuItemId(menuItem.itemId)
            if (index >= 0) {
                handleTabTap(activity, index)
            }
            true
        }

        this.bottomNav = nav
        rebuildMenu()
    }

    private fun rebuildMenu() {
        val nav = bottomNav ?: return
        nav.menu.clear()

        for ((index, config) in tabConfigs.withIndex()) {
            val label = config["label"] as? String ?: ""
            val iconName = config["icon"] as? String ?: ""
            val iconType = config["icon_type"] as? String ?: "system"
            val visible = config["visible"] as? Boolean ?: true

            if (!visible) continue

            val iconRes = if (iconType == "custom") {
                // Look up custom drawable by name
                activity?.resources?.getIdentifier(iconName, "drawable", activity?.packageName) ?: 0
            } else {
                // Map SF Symbol name to Android resource
                iconMap[iconName] ?: android.R.drawable.ic_menu_help
            }

            val menuItem = nav.menu.add(Menu.NONE, index, index, label)
            menuItem.setIcon(iconRes)
        }
    }

    private fun applyStyle() {
        val nav = bottomNav ?: return
        val isDarkMode = (nav.resources.configuration.uiMode and
                android.content.res.Configuration.UI_MODE_NIGHT_MASK) ==
                android.content.res.Configuration.UI_MODE_NIGHT_YES

        @Suppress("UNCHECKED_CAST")
        val darkStyle = styleConfig["dark"] as? Map<String, Any> ?: emptyMap()

        fun resolveColor(key: String): Int? {
            if (isDarkMode) {
                val darkHex = darkStyle[key] as? String
                if (darkHex != null) return parseColor(darkHex)
            }
            val lightHex = styleConfig[key] as? String
            if (lightHex != null) return parseColor(lightHex)
            return null
        }

        // Background
        resolveColor("background_color")?.let { nav.setBackgroundColor(it) }

        // Active / inactive icon and text colors
        val activeColor = resolveColor("active_color")
        val inactiveColor = resolveColor("inactive_color")

        if (activeColor != null || inactiveColor != null) {
            val states = arrayOf(
                intArrayOf(android.R.attr.state_checked),
                intArrayOf(-android.R.attr.state_checked)
            )
            val colors = intArrayOf(
                activeColor ?: Color.parseColor("#007AFF"),
                inactiveColor ?: Color.parseColor("#8E8E93")
            )
            val colorStateList = ColorStateList(states, colors)
            nav.itemIconTintList = colorStateList
            nav.itemTextColor = colorStateList
        }

        // Elevation
        val elevation = styleConfig["elevation"]
        if (elevation is Number) {
            nav.elevation = elevation.toFloat() * nav.resources.displayMetrics.density
        }

        // Badge colors
        resolveColor("badge_color")?.let { badgeColor ->
            for (i in 0 until nav.menu.size()) {
                val badge = nav.getBadge(nav.menu.getItem(i).itemId) ?: continue
                badge.backgroundColor = badgeColor
            }
        }

        resolveColor("badge_text_color")?.let { textColor ->
            for (i in 0 until nav.menu.size()) {
                val badge = nav.getBadge(nav.menu.getItem(i).itemId) ?: continue
                badge.badgeTextColor = textColor
            }
        }
    }

    private fun applyBadges() {
        val nav = bottomNav ?: return

        for ((index, config) in tabConfigs.withIndex()) {
            if (index >= nav.menu.size()) break

            val badge = config["badge"]
            val menuItem = nav.menu.getItem(index)

            if (badge is Number && badge.toInt() > 0) {
                val badgeDrawable = nav.getOrCreateBadge(menuItem.itemId)
                badgeDrawable.number = badge.toInt()
                badgeDrawable.isVisible = true

                val badgeColor = config["badge_color"] as? String
                if (badgeColor != null) {
                    parseColor(badgeColor)?.let { badgeDrawable.backgroundColor = it }
                }
            }
        }
    }

    private fun selectTab(tabId: String) {
        val nav = bottomNav ?: return
        val index = tabConfigs.indexOfFirst { it["id"] == tabId }
        if (index < 0 || index >= nav.menu.size()) return

        nav.selectedItemId = nav.menu.getItem(index).itemId
    }

    private fun indexForMenuItemId(itemId: Int): Int {
        return itemId // We use index as the menu item ID
    }

    // ----------------------------------------------------------
    // Tab Tap Handling
    // ----------------------------------------------------------

    private fun handleTabTap(activity: Activity, index: Int) {
        if (index >= tabConfigs.size) return

        val config = tabConfigs[index]
        val id = config["id"] as? String ?: ""
        val type = config["type"] as? String ?: "url"
        val url = config["url"] as? String
        val action = config["action"] as? String

        // Dispatch TabSelected event for all taps
        val selectedPayload = JSONObject().apply {
            put("id", id)
            put("url", url ?: "")
            put("index", index)
        }

        Handler(Looper.getMainLooper()).post {
            NativeActionCoordinator.dispatchEvent(
                activity,
                "Krakero\\TabBar\\Events\\TabSelected",
                selectedPayload.toString()
            )
        }

        if (type == "url" && url != null) {
            // Navigate the webview
            navigateWebView(activity, url)
        } else if (type == "action" && action != null) {
            // Dispatch action event
            val actionPayload = JSONObject().apply {
                put("id", id)
                put("action", action)
            }

            Handler(Looper.getMainLooper()).post {
                NativeActionCoordinator.dispatchEvent(
                    activity,
                    "Krakero\\TabBar\\Events\\TabActionTriggered",
                    actionPayload.toString()
                )
            }
        }
    }

    private fun navigateWebView(activity: Activity, url: String) {
        Handler(Looper.getMainLooper()).post {
            val rootView = activity.findViewById<ViewGroup>(android.R.id.content)
            val webView = findWebView(rootView)
            webView?.evaluateJavascript("window.location.href = '$url';", null)
        }
    }

    private fun findWebView(view: View): WebView? {
        if (view is WebView) return view
        if (view is ViewGroup) {
            for (i in 0 until view.childCount) {
                val found = findWebView(view.getChildAt(i))
                if (found != null) return found
            }
        }
        return null
    }

    // ----------------------------------------------------------
    // Utility
    // ----------------------------------------------------------

    private fun parseColor(hex: String): Int? {
        return try {
            Color.parseColor(if (hex.startsWith("#")) hex else "#$hex")
        } catch (e: Exception) {
            null
        }
    }
}

// ============================================================
// Bridge Functions
// ============================================================

object TabBarFunctions {

    class Configure : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val activity = NativeActionCoordinator.getCurrentActivity()
                ?: return BridgeResponse.error("No active activity found")

            TabBarManager.configure(activity, parameters)
            return BridgeResponse.success(mapOf("configured" to true))
        }
    }

    class Update : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val activity = NativeActionCoordinator.getCurrentActivity()
                ?: return BridgeResponse.error("No active activity found")

            TabBarManager.update(activity, parameters)
            return BridgeResponse.success(mapOf("updated" to true))
        }
    }

    class SetActive : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val id = parameters["id"] as? String
                ?: return BridgeResponse.error("Missing required parameter: id")

            TabBarManager.setActive(id)
            return BridgeResponse.success(mapOf("active" to id))
        }
    }

    class SetBadge : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val id = parameters["id"] as? String
                ?: return BridgeResponse.error("Missing required parameter: id")

            val count = (parameters["count"] as? Number)?.toInt()
            TabBarManager.setBadge(id, count)
            return BridgeResponse.success(mapOf("id" to id, "badge" to (count ?: 0)))
        }
    }

    class Show : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            TabBarManager.show()
            return BridgeResponse.success(mapOf("visible" to true))
        }
    }

    class Hide : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            TabBarManager.hide()
            return BridgeResponse.success(mapOf("visible" to false))
        }
    }
}
