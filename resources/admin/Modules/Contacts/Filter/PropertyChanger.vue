<template>
    <filterer placement="bottom-start">
        <slot name="header" slot="header">
            <el-button plain size="mini">
                {{$t('Change')}} {{label}}
                <slot name="icon">
                    <i class="el-icon-arrow-down el-icon--right"></i>
                </slot>
            </el-button>
        </slot>

        <el-dropdown-item slot="items" class="fluentcrm-filter-option no-hover">
            {{$t('Choose New')}} {{label}}:
        </el-dropdown-item>

        <el-radio-group class="fluentcrm_checkable_block" slot="items" v-model="selected_item">
            <el-radio v-for="option in options" :key="option.id" :label="option.id">{{option.title}}</el-radio>
        </el-radio-group>

        <el-dropdown-item slot="footer" class="no-hover">
            <el-button type="primary"
                       size="mini"
                       style="width: 100%"
                       @click="save"
            >
                <slot name="btn-label">{{$t('Change')}} {{label}}</slot>
            </el-button>
        </el-dropdown-item>

    </filterer>
</template>
<script type="text/babel">
    import Filterer from '@/Pieces/Filterer';

    export default {
        name: 'PropertyChanger',
        components: {
            Filterer
        },
        props: ['options', 'label', 'prop_key', 'selectedSubscribers'],
        data() {
            return {
                selected_item: ''
            }
        },
        methods: {
            save() {
                this.changeSubscribersProperty({
                    type: this.prop_key,
                    value: this.selected_item
                });
            },
            changeSubscribersProperty(payload) {
                const {type, value} = payload;
                if (!value) {
                    this.$notify.error(this.$t('Pro_Please_saof'));
                    return;
                }
                this.loading = true;
                this.$put('subscribers/subscribers-property', {
                    property: type,
                    value: value,
                    subscribers: this.selectedSubscribers.map(item => item.id)
                })
                    .then(response => {
                        this.$notify.success(response.message);
                        this.$emit('fetch');
                    })
                    .catch(error => {
                        console.log(error);
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            }
        }
    }
</script>
