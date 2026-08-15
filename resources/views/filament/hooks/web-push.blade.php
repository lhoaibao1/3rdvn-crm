@auth
@php($vapidPublicKey = app(\App\Support\Notifications\VapidKeyStore::class)->publicKey())
<script data-navigate-once>
    (() => {
        if (window.__crmWebPushBound || !('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
            return;
        }

        window.__crmWebPushBound = true;
        const vapidPublicKey = @js($vapidPublicKey);
        let registrationInFlight = false;

        const base64UrlToUint8Array = (value) => {
            const padding = '='.repeat((4 - value.length % 4) % 4);
            const base64 = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
            return Uint8Array.from(atob(base64), (character) => character.charCodeAt(0));
        };

        const persist = async (subscription) => {
            const json = subscription.toJSON();
            const response = await fetch('/crm/push-subscriptions', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    endpoint: json.endpoint,
                    keys: json.keys,
                    content_encoding: PushManager.supportedContentEncodings?.[0] || 'aes128gcm',
                }),
            });

            if (!response.ok) {
                throw new Error(`Push subscription failed: ${response.status}`);
            }
        };

        const register = async (requestPermission = false) => {
            if (registrationInFlight || Notification.permission === 'denied') return;
            registrationInFlight = true;

            try {
                if (requestPermission && Notification.permission === 'default') {
                    const permission = await Notification.requestPermission();
                    if (permission !== 'granted') return;
                }

                if (Notification.permission !== 'granted') return;

                const serviceWorker = await navigator.serviceWorker.register('/sw.js', { updateViaCache: 'none' });
                let subscription = await serviceWorker.pushManager.getSubscription();

                if (!subscription) {
                    subscription = await serviceWorker.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: base64UrlToUint8Array(vapidPublicKey),
                    });
                }

                await persist(subscription);
            } catch (error) {
                console.warn('Không thể bật Web Push:', error);
            } finally {
                registrationInFlight = false;
            }
        };

        if (Notification.permission === 'granted') {
            register(false);
        } else if (Notification.permission === 'default') {
            document.addEventListener('pointerdown', () => register(true), { once: true, passive: true });
        }
    })();
</script>
@endauth
