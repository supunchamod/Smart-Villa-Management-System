<script>
    (function () {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        function updateSentBadge(trigger, sentAt) {
            const label = 'Sent ' + sentAt;
            let badge = trigger.querySelector('.wa-sent-badge');

            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'wa-sent-badge';
                trigger.appendChild(badge);
            }

            badge.textContent = label;
        }

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('.wa-send-btn');

            if (!trigger || !trigger.dataset.bookingId || !trigger.dataset.waType) {
                return;
            }

            fetch(`/bookings/${trigger.dataset.bookingId}/log-wa-sent`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ type: trigger.dataset.waType }),
            })
                .then((response) => (response.ok ? response.json() : null))
                .then((data) => data && updateSentBadge(trigger, data.sent_at))
                .catch(() => {});
        });
    })();
</script>
