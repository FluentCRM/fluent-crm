<template>
    <div class="flc_nav_bar">
        <ul>
            <li>
                <a @click.prevent="goPrev()" title="Previous Contact" href="#">
                    <i class="el-icon el-icon-arrow-left"></i>
                </a>
            </li>
            <li v-if="subscriber.phone">
                <a title="Call" :href="'tel:'+subscriber.phone">
                    <i class="el-icon el-icon-phone-outline"></i>
                </a>
            </li>
            <li>
                <a title="Send Email Natively" :href="'mailto:'+subscriber.email">
                    <i class="el-icon el-icon-message"></i>
                </a>
            </li>
            <li>
                <a @click.prevent="goNext()" title="Next Contact" href="#">
                    <i class="el-icon el-icon-arrow-right"></i>
                </a>
            </li>
        </ul>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'ContactNavigation',
    props: ['subscriber'],
    methods: {
        goPrev() {
            this.$emit('prev');
        },
        goNext() {
            this.$emit('next');
        }
    },
    mounted() {
        console.log(this.subscriber);
    }
}
</script>

<style lang="scss">
body.folded #fluent_contact_nav {
    left: calc(50% + 17px) !important;
}
#fluent_contact_nav {
    position: fixed;
    bottom: 1px;
    z-index: 99999;
    width: auto;
    color: white;
    left: calc(50% + 80px);
    transform: translate(-50%, 0);

    ul {
        margin: 0;
        padding: 0;
        list-style: none;
        display: flex;
        align-items: center;
        justify-content: space-around;
        flex-wrap: wrap;
        align-content: center;

        li {
            background: #1f1f25;
            border: none;
            border-right: 1px solid #000;
            box-sizing: border-box;
            cursor: pointer;
            justify-content: center;
            flex: 1;
            align-items: center;
            display: flex;

            a {
                padding: 1rem 1.5rem;
                font-size: 16px;
                height: 100%;
                color: #f1f2f3;
                &:focus {
                    box-shadow: none;
                    outline: none;
                }
            }

            &:hover {
                background: #34343b;
            }

            &:first-child {
                border-bottom-left-radius: 0.5rem;
                border-top-left-radius: 0.5rem;
            }

            &:last-child {
                border-bottom-right-radius: 0.5rem;
                border-top-right-radius: 0.5rem;
            }

            @media all and (max-width: 425px) {
                a {
                    padding: 5px 10px;
                }
            }
        }
    }
}
</style>
