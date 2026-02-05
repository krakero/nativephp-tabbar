/**
 * NativePHP TabBar - JavaScript Bridge
 *
 * Use this in Vue, React, or vanilla JS to interact with the native tab bar.
 *
 * Usage:
 *   import { configure, setActive, setBadge, show, hide } from './vendor/krakero/nativephp-tabbar/resources/js/tabbar.js';
 */

const baseUrl = '/_native/api/call';

async function bridgeCall(method, params = {}) {
    const response = await fetch(baseUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ method, params }),
    });
    return response.json();
}

/**
 * Configure the tab bar with a full set of tabs.
 *
 * @param {Object} config - { tabs: [...], style?: {...}, active_tab?: string }
 * @returns {Promise<Object>}
 */
export async function configure(config) {
    return bridgeCall('TabBar.Configure', config);
}

/**
 * Update the tab bar configuration at runtime.
 *
 * @param {Object} config - { tabs: [...], style?: {...}, active_tab?: string }
 * @returns {Promise<Object>}
 */
export async function update(config) {
    return bridgeCall('TabBar.Update', config);
}

/**
 * Programmatically switch the active tab.
 *
 * @param {string} tabId - The tab identifier
 * @returns {Promise<Object>}
 */
export async function setActive(tabId) {
    return bridgeCall('TabBar.SetActive', { id: tabId });
}

/**
 * Set or clear a numeric badge on a tab.
 *
 * @param {string} tabId - The tab identifier
 * @param {number|null} count - The badge count, or null to clear
 * @returns {Promise<Object>}
 */
export async function setBadge(tabId, count) {
    return bridgeCall('TabBar.SetBadge', { id: tabId, count });
}

/**
 * Show the tab bar.
 *
 * @returns {Promise<Object>}
 */
export async function show() {
    return bridgeCall('TabBar.Show', {});
}

/**
 * Hide the tab bar.
 *
 * @returns {Promise<Object>}
 */
export async function hide() {
    return bridgeCall('TabBar.Hide', {});
}
