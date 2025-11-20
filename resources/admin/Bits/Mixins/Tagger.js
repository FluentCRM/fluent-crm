export default {
    data() {
        return {
            noMatch: false
        }
    },

    computed: {
        choices() {
            return this.options.filter(item => item.status !== false);
        }
    },

    methods: {
        search(query) {
            let noMatch = true;

            const options = this.options.map(item => {
                if (item.title.toLowerCase().includes(query)) {
                    noMatch = false;
                    item.status = true;
                } else {
                    item.status = query && false;
                }

                return item;
            });

            this.$emit('search', this.type, options);

            this.noMatch = !!(query && noMatch);
        },

        subscribe(items) {
            this.$emit('subscribe', this.payload(items));
        },

        payload(data) {
            return {type: this.type, payload: data};
        }
    }
}
