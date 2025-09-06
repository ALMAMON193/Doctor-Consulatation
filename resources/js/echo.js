import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;
const token = '3|p8pLR6UjOyzc5kIsav2mVFCu2oO50PibvV9YauK5339152ce';

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
let consultationId = 1;
// ✅ Listen to messages for a consultation
window.Echo.channel(`consultation.${consultationId}`)
  .listen('.message.sent', (e) => {
      console.log("📩 New Message:", e.message);
  });
