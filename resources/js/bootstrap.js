/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
window.Pusher = Pusher;

// Gate broadcasting behind an explicit flag to avoid noisy WebSocket errors in environments without a WS server
const echoEnabled = (import.meta.env.VITE_ECHO_ENABLED === 'true');

// Prefer explicit Pusher key, but allow using the Reverb-compatible key as a fallback
const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY ?? import.meta.env.VITE_REVERB_APP_KEY ?? null;

if (echoEnabled && pusherKey) {
    try {
        // Check for Reverb configuration first, then fall back to Pusher
        if (import.meta.env.VITE_REVERB_APP_KEY) {
            window.Echo = new Echo({
                broadcaster: 'reverb',
                key: import.meta.env.VITE_REVERB_APP_KEY,
                wsHost: import.meta.env.VITE_REVERB_HOST,
                wsPort: import.meta.env.VITE_REVERB_PORT,
                wssPort: import.meta.env.VITE_REVERB_PORT,
                forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
                enabledTransports: ['ws', 'wss'],
            });
        } else if (import.meta.env.VITE_PUSHER_APP_KEY) {
            window.Echo = new Echo({
                broadcaster: 'pusher',
                key: import.meta.env.VITE_PUSHER_APP_KEY,
                cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
                wsHost: import.meta.env.VITE_PUSHER_HOST ?? `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1'}.pusher.com`,
                wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
                wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
                forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
                enabledTransports: ['ws', 'wss'],
                authEndpoint: '/broadcasting/auth',
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                    },
                },
                disableStats: true, // Disable stats to reduce errors
            });

            // Handle connection errors gracefully
            if (window.Pusher) {
                window.Pusher.logToConsole = import.meta.env.DEV;
                
                // Suppress connection errors in production
                const originalError = console.error;
                console.error = function(...args) {
                    const errorString = args.join(' ');
                    // Suppress WebSocket connection errors
                    if (errorString.includes('WebSocket connection') || 
                        errorString.includes('ws://') || 
                        errorString.includes('wss://')) {
                        if (import.meta.env.DEV) {
                            console.warn('[Broadcasting] WebSocket connection issue (this is expected if echo server is not running):', ...args);
                        }
                        return;
                    }
                    originalError.apply(console, args);
                };
            }
        }

        console.info('[Broadcasting] Echo initialized successfully');
    } catch (error) {
        console.warn('[Broadcasting] Failed to initialize Echo:', error.message);
        window.Echo = null;
    }
} else {
    // Avoid initializing Echo unless explicitly enabled
    if (echoEnabled) {
        console.warn('[Broadcasting] Echo not initialized: No Pusher or Reverb key found in environment (VITE_PUSHER_APP_KEY or VITE_REVERB_APP_KEY).');
    } else {
        console.info('[Broadcasting] Echo disabled by config (VITE_ECHO_ENABLED != "true").');
    }
    window.Echo = null;
}
