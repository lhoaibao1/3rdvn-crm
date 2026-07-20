<livewire:wirechat.modal />
<x-wirechat::toast />

@php
    $chatChannel = 'chats.participant.'
        .\Wirechat\Wirechat\Helpers\MorphClassResolver::encode(auth()->user()->getMorphClass())
        .'.'.auth()->id();
@endphp

<script data-navigate-once>
    (() => {
        const bindChatNotifications = () => {
            if (window.__crmChatNotificationsBound || ! window.Echo) return;

            window.__crmChatNotificationsBound = true;
            const channel = @js($chatChannel);
            const eventName = @js('.Wirechat\Wirechat\Events\NotifyParticipant');

            window.Echo.private(channel).listen(eventName, (event) => {
                window.Livewire?.dispatch('refresh-chats');

                if (! ('Notification' in window)) return;

                const message = event?.message ?? {};
                const conversation = message?.conversation ?? {};
                const sender = message?.sendable ?? {};
                const isGroup = conversation?.type === 'group';
                const title = isGroup
                    ? (conversation?.group?.name || 'Tin nhắn nhóm')
                    : (sender?.wirechat_name || 'Tin nhắn mới');
                const body = isGroup
                    ? `${sender?.wirechat_name || 'Người dùng'}: ${message?.body || ''}`
                    : (message?.body || 'Bạn có tin nhắn mới.');
                const icon = isGroup
                    ? conversation?.group?.cover_url
                    : sender?.wirechat_avatar_url;

                const showNotification = () => {
                    const notification = new Notification(title, {
                        body,
                        icon: icon || undefined,
                        tag: `crm-chat-${conversation?.id || Date.now()}`,
                    });

                    notification.onclick = () => {
                        window.focus();
                        window.Livewire?.dispatch('chat-conversation-opened');
                        if (conversation?.id) {
                            window.Livewire?.dispatch('open-chat', { conversation: conversation.id });
                        }
                        notification.close();
                    };
                };

                if (Notification.permission === 'granted') {
                    showNotification();
                } else if (Notification.permission === 'default') {
                    Notification.requestPermission().then((permission) => {
                        if (permission === 'granted') showNotification();
                    });
                }
            });
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bindChatNotifications, { once: true });
        } else {
            bindChatNotifications();
        }

        document.addEventListener('livewire:initialized', bindChatNotifications, { once: true });
    })();
</script>

<script data-navigate-once>
    (() => {
        const channel = @js('App.Models.User.'.auth()->id());
        let attempts = 0;

        const bindDatabaseNotifications = () => {
            if (window.__crmDatabaseNotificationsBound || ! window.Echo) {
                return Boolean(window.__crmDatabaseNotificationsBound);
            }

            window.__crmDatabaseNotificationsBound = true;
            window.Echo.private(channel).listen('.database-notifications.sent', () => {
                window.crmHandleRealtimeNotification?.();
            });

            return true;
        };

        if (! bindDatabaseNotifications()) {
            const timer = window.setInterval(() => {
                attempts++;

                if (bindDatabaseNotifications() || attempts >= 20) {
                    window.clearInterval(timer);
                }
            }, 500);
        }
    })();
</script>
