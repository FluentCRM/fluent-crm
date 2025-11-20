export function subscribe({type, payload}) {
    const {attach, detach} = payload;

    this.loading = true;

    const query = {
        type,
        attach,
        detach,
        subscribers: this.selectedSubscribers.map(item => item.id)
    };

    this.$post('subscribers/sync-segments', query).then(response => {
        response.subscribers.forEach(subscriber => {
            const index = this.subscribers.findIndex(item => {
                return item.id === subscriber.id;
            });

            if (index !== -1) {
                this.subscribers.splice(index, 1, subscriber);
            }

            this.$refs.subscribersTable.toggleRowSelection(this.subscribers[index]);
        });

        const selected = `selected_${type}`;

        if (this[selected].length && detach.length) {
            const items = this[selected].filter(item => detach.includes(item));

            if (items.length) {
                this.filter({type, payload: items})
            }
        }

        this.loading = false;

        this.$notify.success({
            title: 'Great!',
            message: response.message,
            offset: 19
        });
    });
}
