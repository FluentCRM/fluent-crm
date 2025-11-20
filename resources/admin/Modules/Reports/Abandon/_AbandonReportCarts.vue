<template>
    <div class="fcrm_abandon_reports_carts">
        <el-table
            border
            :data="carts"
            class="fcrm_abandon_reports_table"
            style="width: 100%"
            stripe
            @selection-change="onSelection"
        >
            <el-table-column type="selection" :width="45"/>
            <el-table-column
                :label="$t('Name')"
                prop="date"
                width="250">
                <template slot-scope="scope">
                    <div class="fc_name_avatar">
                        <div class="fc_avatar">
                            <img :src="scope.row.customer_avatar"/>
                        </div>
                        <div class="fc_names">
                            <div class="fc_name">
                                <router-link
                                    v-if="scope.row.contact_id"
                                    :to="{ name: 'subscriber', params: { id: scope.row.contact_id } }"
                                >
                                    {{ scope.row.full_name }}
                                </router-link>
                                <span v-else>
                                    {{ scope.row.full_name }}
                                </span>
                            </div>
                            <div class="fc_email">{{ scope.row.email }}</div>
                        </div>
                    </div>
                </template>
            </el-table-column>
            <el-table-column
                :label="$t('Automation')"
                prop="automation_id">
                <template slot-scope="scope">
                    <lazy-individual-progress v-if="scope.row.automation && scope.row.contact_id"
                                              :subscriber_id="scope.row.contact_id"
                                              :funnel="scope.row.automation"></lazy-individual-progress>
                    <span v-else>
                        --
                    </span>
                </template>
            </el-table-column>
            <el-table-column
                :label="$t('Cart Total')"
                prop="cart_total"
                sortable
                width="180">
                <template slot-scope="scope">
                    <span @click="editCart(scope.row)" class="cart-total fluentcrm_clickable">{{ scope.row.currency }} {{
                            scope.row.total
                        }}</span>
                </template>
            </el-table-column>
            <el-table-column
                :label="$t('Order Status')"
                prop="cart_total"
                width="180">
                <template slot-scope="scope">
                    <span :class="scope.row.status" class="status">
                        {{ scope.row.status }}
                        <el-tooltip v-if="scope.row.status == 'skipped' && scope.row.note" popper-class="sidebar-popper" effect="dark" placement="top">
                            <div slot="content" v-html="scope.row.note" style="max-width: 400px;"></div>
                            <i class="tooltip-icon el-icon-info"></i>
                        </el-tooltip>
                    </span>
                </template>
            </el-table-column>
            <el-table-column
                :label="$t('Time')"
                prop="cart_total"
                sortable
                width="180">
                <template slot-scope="scope">
                    {{ scope.row.created_at | nsHumanDiffTime }}
                </template>
            </el-table-column>
            <el-table-column
                :label="$t('Action')"
                fixed="right"
                min-width="140">
                <template slot-scope="scope">
                    <div class="action-btns">
                        <el-tooltip class="item" effect="dark" :content="$t('View Cart')" placement="top-start">
                            <i class="el-icon-view" @click="editCart(scope.row)"></i>
                        </el-tooltip>
                        <confirm v-loading="deletingCart" @yes="deleteCarts([scope.row.id])">
                            <i class="el-icon-delete"></i>
                        </confirm>
                    </div>
                </template>
            </el-table-column>
        </el-table>

        <div style="margin-top: 10px;" class="fcrm-abandon-reports-header">
            <div class="left">
                <div v-if="selection">
                    <el-button
                        :loading="deletingCart"
                        @click="handleSelectedCarts"
                        type="danger"
                        size="small"
                    >
                        {{ $t('Delete Selected Carts') }}
                    </el-button>
                </div>
            </div>
            <div class="right">
                <slot name="pagination_block"></slot>
            </div>
        </div>

        <el-dialog
            :append-to-body="true"
            :title="$t('Cart Details')"
            :visible.sync="dialogVisible"
            custom-class="fcrm_abandon_cart_details_popover"
        >
            <div v-if="singleCart" class="fcrm_abandon_cart_details_wrap">
                <div class="fcrm_abandon_cart_address_wrap">
                    <div class="fcrm_abandon_cart_address">
                        <h4>{{ $t('Billing Address') }}</h4>
                        <p v-html="addressHandler(singleCart?.cart?.customer_data, 'billing')"></p>
                    </div>
                    <div class="fcrm_abandon_cart_address">
                        <h4>{{ $t('Shipping Address') }}</h4>
                        <p v-html="addressHandler(singleCart?.cart?.customer_data, 'shipping')"></p>
                    </div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th class="product_image">{{ $t('Image') }}</th>
                            <th>{{ $t('Items') }}</th>
                            <th>{{ $t('Quantity') }}</th>
                            <th>{{ $t('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(product, i) in singleCart.cart?.cart_contents" :key="i">
                        <td class="product_image">
                            <img :src="product.product_image" :alt="product.title">
                        </td>
                        <td>{{ product.title }}</td>
                        <td>{{ product.quantity }}</td>
                        <td>{{ singleCart.currency }} {{ product.line_total }}</td>
                    </tr>
                    </tbody>

                    <tfoot>
                    <tr class="shipping-tr">
                        <td>{{ $t('Shipping') }}</td>
                        <td></td>
                        <td></td>
                        <td>{{ singleCart.currency }} {{ singleCart?.shipping }}</td>
                    </tr>
                    <tr class="tax-tr">
                        <td>{{ $t('Tax(es)') }}</td>
                        <td></td>
                        <td></td>
                        <td>{{ singleCart.currency }} {{ singleCart.tax }}</td>
                    </tr>
                    <tr class="total-tr">
                        <td>{{ $t('Total') }}</td>
                        <td></td>
                        <td></td>
                        <td>{{ singleCart.currency }} {{ singleCart?.total }}</td>
                    </tr>
                    </tfoot>
                </table>
                <div v-if="singleCart.cart?.customer_data?.order_comments">
                    <hr/>
                    <p><b>{{ $t('Order Comments') }}</b></p>
                    <p>{{ singleCart.cart.customer_data.order_comments }}</p>
                </div>
                <div v-if="singleCart.recovery_url">
                    <h4>{{ $t('Recovery URL') }}</h4>
                    <item-copier :text="singleCart.recovery_url"></item-copier>
                </div>
                <div v-if="singleCart.order_url">
                    <a target="_blank" rel="noopener" :href="singleCart.order_url">{{ $t('View Original Order') }}</a>
                </div>
            </div>
        </el-dialog>
    </div>
</template>

<script>
import Confirm from '@/Pieces/Confirm';
import ItemCopier from '@/Pieces/ItemCopier';
import LazyIndividualProgress from '@/Modules/Funnels/parts/_LazyIndividualProgress';

export default {
    name: 'AbandonReportCarts',
    props: ['carts'],
    components: {
        ItemCopier,
        Confirm,
        LazyIndividualProgress
    },
    data() {
        return {
            dialogVisible: false,
            singleCart: '',
            countries: window.fcAdmin.countries,
            deletingCart: false,
            selection: false,
            selectedCarts: []
        }
    },
    methods: {
        handleSelectedCarts() {
            this.$confirm(this.$t('Are you sure you want to delete selected carts?'), this.$t('Warning'), {
                confirmButtonText: this.$t('Yes'),
                cancelButtonText: this.$t('No'),
                type: 'warning'
            }).then(() => {
                this.deleteCarts(this.selectedCarts.map(cart => cart.id));
            });
        },
        deleteCarts(ids) {
            this.deletingCart = true;
            this.$post('abandon-carts/bulk-delete', {
                cart_ids: ids
            })
                .then((response) => {
                    this.$notify.success(response.message);
                    this.selection = false;
                    this.selectedCarts = [];
                    this.$emit('refetch');
                })
                .catch((errors) => {
                    this.handleError(errors);
                })
                .finally(() => {
                    this.deletingCart = false;
                });
        },
        editCart(row) {
            this.dialogVisible = true;
            this.singleCart = row;
        },
        addressHandler(customer, type) {
            if (this.singleCart) {
                let addressVar = customer.shippingAddress;
                if (type == 'billing') {
                    addressVar = customer.billingAddress;
                }

                if (addressVar) {
                    let address = [
                        addressVar.first_name + ' ' + addressVar.last_name,
                        addressVar.address_1 + ' ' + addressVar.address_2,
                        addressVar.postcode,
                        addressVar.city,
                        this.countryName(addressVar.country)
                    ];

                    address = address.filter(function (el) {
                        return !!el;
                    });

                    return address.join('<br>');
                } else {
                    return this.$t('No Address found');
                }
            }
        },
        countryName(countryCode) {
            let countryTitle = '';
            this.countries.map(country => {
                if (country.code == countryCode) {
                    countryTitle = country.title;
                }
            });
            return countryTitle || countryCode;
        },
        onSelection(selectedCarts) {
            this.selection = !!selectedCarts.length;
            this.selectedCarts = selectedCarts;
        }
    },
    mounted() {
    }
}
</script>
