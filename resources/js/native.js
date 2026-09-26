/**
 * Bridges to the Capacitor shell when the site runs inside the mobile app.
 * On the web these helpers fall back to normal browser behaviour.
 */
export const isNativeApp = () => Boolean(window.Capacitor?.isNativePlatform?.());

/**
 * Opens a file download. WebViews do not handle Content-Disposition downloads,
 * so the app hands the (signed, short-lived) link to the system browser.
 */
export function openDownload(url) {
    const browser = window.Capacitor?.Plugins?.Browser;

    if (isNativeApp() && browser) {
        browser.open({ url });

        return;
    }

    window.location.href = url;
}

export function registerServiceWorker() {
    if (!isNativeApp() && 'serviceWorker' in navigator && window.isSecureContext) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
    }
}

export function markNativeShell() {
    if (isNativeApp()) {
        document.documentElement.classList.add('native-app');
    }
}
