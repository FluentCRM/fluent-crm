<template>
    <div>
        <div>
            <div class="actions">
                <el-dropdown trigger="click" type="primary" placement="bottom-start">
                    <el-button plain>
                        {{ $t('Actions') }}<i class="el-icon-arrow-down el-icon--right"></i>
                    </el-button>

                    <el-dropdown-menu slot="dropdown" style="min-width: 200px">
                        <el-dropdown-item>
                            {{ $t('Delete') }}
                        </el-dropdown-item>
                    </el-dropdown-menu>
                </el-dropdown>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'Activities',
    data() {
        return {
            items: [
                {
                    text: ''
                }
            ]
        }
    }
};
</script>
