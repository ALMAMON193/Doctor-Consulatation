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
    authEndpoint: '/api/broadcasting/auth',
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Authorization': 'Bearer ' + localStorage.getItem('access_token'),
        },
    },
    enabledTransports: ['ws', 'wss'],
});
console.log("revert start");


const doctorId = 2; // doctor id
const userType = 'patient'; //  'patient' or 'doctor_profile'
const userId = 1; // patient_member  (patient_member id, patient id, doctor_profile id)

let channelName;

if (userType === 'patient_member') {
    channelName = `chat.doctor.${doctorId}.member.${userId}`;
} else if (userType === 'patient') {
    channelName = `chat.doctor.${doctorId}.patient.${userId}`;
} else {
    // doctor_profile
    channelName = null;
}

if (channelName) {
    window.Echo.private(channelName)
        .listen('.MessageSent', (e) => {
            console.log('message send:', e.message);
        });
}
