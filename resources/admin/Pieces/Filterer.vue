<template>
    <div>
        <el-dropdown
            class="fluentcrm-filter"
            :placement="placement"
            :hide-on-click="false"
            trigger="click"
        >
            <slot name="header">
                <el-button plain size="small">
                    <slot name="label">
                        {{$t('Columns')}}
                    </slot>
                    <slot name="icon">
                        <i class="el-icon-arrow-down el-icon--right"></i>
                    </slot>
                </el-button>
            </slot>

            <el-dropdown-menu slot="dropdown" class="fc_filter_dropdown">
                <slot name="items"/>

                <slot name="footer"/>
            </el-dropdown-menu>
        </el-dropdown>
        <slot name="dropdown_footer"/>
    </div>
</template>

<script type="text/babel">
    export default {
        name: 'Filterer',
        props: {
            placement: {
                default: 'bottom-start'
            },
            filter_type: {
                default: 'filterer'
            }
        }
    }
</script>
