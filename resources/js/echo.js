import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
    },
});

console.log('Reverb initialized', {
    key: import.meta.env.VITE_REVERB_APP_KEY,
    host: import.meta.env.VITE_REVERB_HOST,
    port: import.meta.env.VITE_REVERB_PORT,
    scheme: import.meta.env.VITE_REVERB_SCHEME,
});

// Example: Dynamic user data (should come from auth or UI)
const currentUserType = 'doctor_profile'; // Example
const currentUserId = 3; // Example
const otherUserType = 'patient'; // Example
const otherUserId = 1; // Example

// Sort IDs to match channel naming
const ids = [currentUserId, otherUserId].sort();
const channelName = `chat.${currentUserType}.${ids[0]}.${otherUserType}.${ids[1]}`;
console.log('Subscribing to channel', { channelName });

window.Echo.private(channelName)
    .listen('.MessageSent', (data) => {
        console.log('Message received', {
            message_id: data.id,
            sender: data.sender,
            message: data.message,
            file_url: data.file_url,
            created_at: data.created_at,
        });
    })
    .error((error) => {
        console.error('WebSocket error', { error });
    });

