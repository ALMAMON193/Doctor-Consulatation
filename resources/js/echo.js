import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;
const token = '4|oOoNBoGYyAPW292NPBUKy1EYpMxV0hOEuaTRKzgO4cc1749d';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/api/broadcasting/auth',
    auth: {
        headers: {
            Authorization: `Bearer ${token}`
        }
    }
});
let consultationId = 2;
// ✅ Listen to messages for a consultation
window.Echo.private(`consultation.${consultationId}`)
  .listen('.message.sent', (e) => {
      console.log("📩 New Message:", e.message);
  });
