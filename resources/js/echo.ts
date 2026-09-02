import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo: Echo<'pusher'>;
    }
}

export function initEcho() {
    const key = import.meta.env.VITE_PUSHER_APP_KEY as string | undefined;
    if (!key || typeof window === 'undefined') {
        return null;
    }
    if (window.Echo) {
        return window.Echo;
    }
    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key,
        cluster: (import.meta.env.VITE_PUSHER_APP_CLUSTER as string) || 'mt1',
        forceTLS: true,
    });
    return window.Echo;
}
