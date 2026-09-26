import type { CapacitorConfig } from '@capacitor/cli';

/**
 * The app loads the live Sirah site, so the mobile app is always identical to
 * the web app and updates the moment the site is deployed.
 *
 * Set SIRAH_URL before `npx cap sync`:
 *   production:        SIRAH_URL=https://your-domain.com
 *   Android emulator:  SIRAH_URL=http://10.0.2.2:8000   (local `php artisan serve`)
 */
const serverUrl = process.env.SIRAH_URL ?? 'https://your-domain.com';
const host = new URL(serverUrl).host;

const config: CapacitorConfig = {
    appId: 'com.sirah.cvbuilder',
    appName: 'Sirah',
    webDir: 'www',
    server: {
        url: serverUrl,
        cleartext: serverUrl.startsWith('http://'),
        // Pages the app may navigate to inside the WebView (everything else opens outside).
        allowNavigation: [host, 'checkout.stripe.com', '*.stripe.com'],
        errorPath: 'offline.html',
    },
    android: {
        allowMixedContent: false,
    },
    plugins: {
        SplashScreen: {
            launchShowDuration: 1200,
            backgroundColor: '#ffffff',
            showSpinner: false,
        },
        StatusBar: {
            style: 'LIGHT',
            backgroundColor: '#ffffff',
            overlaysWebView: false,
        },
    },
};

export default config;
