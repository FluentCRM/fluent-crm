<template>
    <div style="padding: 20px;">
        <el-row :gutter="30">
            <el-col :span="5">.</el-col>
            <el-col :span="9">
                <el-skeleton animated>
                    <template slot="template">
                        <el-skeleton-item variant="image" style="height: 240px;"/>
                        <el-skeleton-item variant="h3" style="width: 50%; margin: 20px auto;"/>
                        <div style="padding: 14px; text-align: left;">
                            <div style="text-align: center;">
                                <el-skeleton-item variant="text" style="width: 30%;"/>
                                <el-skeleton-item variant="text"/>
                                <el-skeleton-item variant="text"/>
                            </div>
                            <el-row :gutter="30">
                                <el-col :span="12">
                                    <el-skeleton-item variant="image"
                                                      style="height: 140px; margin: 20px 0;"/>
                                </el-col>
                                <el-col :span="12">
                                    <el-skeleton-item variant="image"
                                                      style="height: 140px; margin: 20px 0;"/>
                                </el-col>
                            </el-row>
                            <div style="text-align: left;">
                                <el-skeleton-item variant="text"/>
                                <el-skeleton-item variant="text"/>
                                <el-skeleton-item variant="text" style="width: 30%;"/>
                            </div>
                        </div>
                    </template>
                </el-skeleton>
            </el-col>
            <el-col :span="5">.</el-col>
            <el-col :span="4">
                <el-skeleton :rows="12" animated></el-skeleton>
                <el-skeleton animated :rows="3"/>
            </el-col>
            <el-col :span="1">
                <el-skeleton :rows="6"/>
            </el-col>
        </el-row>
    </div>
</template>

<script type="text/babel">
export default {
    name: 'LoaderSkeleton'
}
</script>
