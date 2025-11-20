<template>
    <div v-loading="loading">
        <h3>{{$t('Select User Roles')}}</h3>
        <el-checkbox
            v-model="checkAll"
            :indeterminate="isIndeterminate"
            @change="all">
            {{$t('All')}}
        </el-checkbox>

        <div style="margin: 15px 0;"></div>

        <el-checkbox-group
            class="fluentcrm_2col_labels"
            v-model="selections"
            @change="checked">
            <el-checkbox
                v-for="(role, key) in roles"
                :label="key"
                :key="key">
                {{ role.name }}
            </el-checkbox>
        </el-checkbox-group>
        <div slot="footer" class="dialog-footer">
            <el-button
                size="small"
                type="primary"
                @click="next">
               {{$t('Next [Review Data]')}}
            </el-button>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'Users',
        data() {
            return {
                roles: [],
                selections: [],
                checkAll: false,
                isIndeterminate: false,
                loading: false
            }
        },
        watch: {
            selections() {
                this.$emit('success', this.selections);
            }
        },
        methods: {
            fetch() {
                this.loading = true;
                this.$get('users/roles')
                    .then(response => {
                        this.roles = response.roles;
                    })
                    .catch((error) => {
                        this.handleError(error);
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            },
            checked(value) {
                const roles = Object.keys(this.roles);
                const checkedCount = value.length;
                this.checkAll = checkedCount === roles.length;
                this.isIndeterminate = checkedCount > 0 && checkedCount < roles.length;
            },
            all(value) {
                this.selections = value ? Object.keys(this.roles) : [];
                this.isIndeterminate = false;
            },
            next() {
                this.$emit('next');
            }
        },
        mounted() {
            this.fetch();
        }
    }
</script>
