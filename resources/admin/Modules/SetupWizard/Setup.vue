<template>
    <div class="setup_container">
        <div class="setup_wrapper">
            <div class="header">
                <h1>FluentCRM</h1>
            </div>
            <div class="navigation">
                <el-steps finish-status="success" :active="active_step" align-center>
                    <el-step :title="$t('Welcome')" icon="el-icon-s-home"></el-step>
                    <el-step :title="$t('Business Info')" icon="el-icon-office-building"></el-step>
                    <el-step :title="$t('Lists')" icon="el-icon-files"></el-step>
                    <el-step :title="$t('Tags')" icon="el-icon-price-tag"></el-step>
                    <el-step :title="$t('Complete')" icon="el-icon-check"></el-step>
                </el-steps>
            </div>
            <div class="setup_body">
                <template v-if="active_step === 0">
                    <h3>{{ $t('Welcome to FluentCRM!') }}</h3>
                    <p>{{ $t('thankyou_for_using_fluentcrm') }}
                        <b>{{ $t("FluentCrm_Welcome_desc") }}</b></p>
                    <p>{{ $t('welcome_to_fluentcrm_return_to_wordpress_dashboard') }}</p>
                    <div class="setup_footer">
                        <a class="el-button el-link el-button--default"
                           :href="config.dashboard_url">{{ $t('Not Right Now') }}</a>
                        <el-button @click="active_step = 1" class="pull-right" type="success">{{ $t('Let\'s Go') }}
                        </el-button>
                    </div>
                </template>
                <!--Business Info-->
                <div v-loading="loading" class="business_info" v-else-if="active_step === 1">
                    <div class="section_heading">
                        <h3>{{ $t('Please Provide your business information') }}</h3>
                        <p>{{ $t('subscribers_frontpage_email_campaign') }}</p>
                    </div>
                    <el-form label-position="top" :model="business_settings">
                        <el-form-item :label="$t('Business Name')">
                            <el-input :placeholder="$t('MyAwesomeBusiness')"
                                      v-model="business_settings.business_name"></el-input>
                        </el-form-item>
                        <el-form-item :label="$t('Business Full Address')">
                            <el-input :placeholder="$t('street, state, zip, country')"
                                      v-model="business_settings.business_address"></el-input>
                        </el-form-item>
                        <el-form-item :label="$t('Logo')">
                            <photo-widget v-model="business_settings.logo"></photo-widget>
                        </el-form-item>
                    </el-form>
                    <div class="setup_footer">
                        <el-button :disabled="loading" @click="saveBusinessSettings()" class="pull-right"
                                   type="success">
                            {{ $t('Next') }}
                        </el-button>
                    </div>
                </div>

                <!--Lists-->
                <div v-loading="loading" class="list_info" v-else-if="active_step === 2">
                    <div class="section_heading">
                        <h3>{{ $t('Contact Segment Lists') }}</h3>
                        <p>{{ $t('setup_lists_to_segment_your_conntacts') }}</p>
                    </div>
                    <table class="fc_horizontal_table">
                        <thead>
                        <tr>
                            <th>{{ $t('Segment Name') }}</th>
                            <th>{{ $t('Slug') }}</th>
                            <th>{{ $t('Action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(list, listIndex) in list_segments" :key="listIndex">
                            <td>
                                <el-input @change="slugifyList(listIndex)"
                                          :placeholder="$t('EG: User Type')+' '+ (listIndex+1)"
                                          v-model="list.title"/>
                            </td>
                            <td>
                                <el-input v-model="list.slug"/>
                            </td>
                            <td>
                                <el-button @click="deleteListItem(listIndex)" size="small" type="danger"
                                           :disabled="list_segments.length == 1" icon="el-icon-delete"></el-button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <el-button size="small" type="primary" @click="addListItem()">{{ $t('Add More') }}</el-button>
                    <div class="setup_footer">
                        <el-button :disabled="loading" @click="saveLists()" class="pull-right" type="success">
                            {{ $t('Next') }}
                        </el-button>
                    </div>
                </div>

                <!--Tags-->
                <div v-loading="loading" class="tag_info" v-else-if="active_step === 3">
                    <div class="section_heading">
                        <h3>{{ $t('Contact Tags') }}</h3>
                        <p>{{ $t('create_some_tags') }} <b>{{ $t('Example:') }} <em>Product-X User, Product-Y User,
                            Influencer etc</em></b>
                        </p>
                    </div>
                    <table class="fc_horizontal_table">
                        <thead>
                        <tr>
                            <th>{{ $t('Tag Name') }}</th>
                            <th>{{ $t('Slug') }}</th>
                            <th>{{ $t('Action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(tag, tagIndex) in tag_segments" :key="tagIndex">
                            <td>
                                <el-input @change="slugifyTag(tagIndex)" :placeholder="$t('EG: Tag')+' '+ (tagIndex+1)"
                                          v-model="tag.title"/>
                            </td>
                            <td>
                                <el-input v-model="tag.slug"/>
                            </td>
                            <td>
                                <el-button @click="deleteTagItem(tagIndex)" size="small" type="danger"
                                           :disabled="tag_segments.length == 1" icon="el-icon-delete"></el-button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <el-button size="small" type="primary" @click="addTagItem()">
                        {{ $t('Add More') }}
                    </el-button>
                    <div class="setup_footer">
                        <el-button :disabled="loading" @click="saveTags()" class="pull-right" type="success">
                            {{ $t('Next') }}
                        </el-button>
                    </div>
                </div>

                <!--Plugins & Tracker-->
                <div v-loading="loading" element-loading-text="Completing Installation..." class="tag_info" v-else-if="active_step === 4">
                    <div class="section_heading">
                        <h3>{{ $t('Almost Done!') }}</h3>
                        <p v-if="!config.has_fluentform">
                            {{ $t('install_fluentform') }} <b>Fluent Forms</b>
                            plugin. {{ $t('fluentform_info') }}
                        </p>
                        <p v-else>
                            {{ $t('Thank you again for configuring your own CRM in WordPress.') }}<br/>
                            {{ $t('Setup.Subscribe_Newsletter') }}
                        </p>
                    </div>
                    <div v-if="!config.has_fluentform" class="suggest_box">
                        <el-checkbox true-label="yes" false-label="no" v-model="install_fluentforms">
                            {{ $t('Setup.Install_FluentForm') }}
                        </el-checkbox>
                    </div>

                    <div v-if="!config.has_fluentcart" class="suggest_box">
                        <el-checkbox true-label="yes" false-label="no" v-model="install_fluentcart">
                            Install FluentCart to sale Products, Subscriptions, Digital Downloads.
                        </el-checkbox>
                    </div>

                    <div class="suggest_box share_essential">
                        <p style="margin-bottom: 10px;"><b>{{ $t('Help us to make FluentCRM better') }}</b></p>
                        <el-checkbox true-label="yes" false-label="no" v-model="share_essentials">
                            {{ $t('Share Essentials') }}
                        </el-checkbox>
                        <p style="margin-top: 10px;">
                            {{ $t('Setup.FluentCrm.Share_Essentials.desc') }}
                            <span
                                @click="show_essential = !show_essential">{{ $t('what we collect') }}</span></p>
                        <p v-if="show_essential">{{ $t('what_we_collect_infos') }}</p>
                    </div>

                    <div class="suggest_box email_optin">
                        <label>{{ $t('Your Email Address') }}</label>
                        <el-input :placeholder="$t('Email Address for bi-monthly newsletter')" type="email"
                                  v-model="email_address"></el-input>
                        <br/>
                        <p style="margin-top: 20px; font-size: 13px;">
                            {{ $t('Setup.Send_Marketing_tips') }}</p>
                    </div>

                    <div class="setup_footer">
                        <el-button :disabled="loading" @click="complete()" class="pull-right" type="success">
                            {{ $t('Complete Installation') }}
                        </el-button>
                    </div>
                </div>

                <!--Congrats-->
                <div class="congrates" v-else-if="active_step === 5">
                    <div class="section_heading">
                        <h3>{{ $t('Congratulations') }}</h3>
                        <p>{{ $t('Everything is ready.') }}</p>
                    </div>
                    <div class="next_box">
                        <h4>{{ $t('Next') }}...</h4>
                        <ul class="congrates_lists">
                            <li><a :href="config.dashboard_url+'#/subscribers'">{{ $t('Import Contacts') }}</a></li>
                            <li><a :href="config.dashboard_url">{{ $t('Go to CRM Dashboard') }}</a></li>
                        </ul>
                    </div>

                </div>
            </div>
            <div v-if="!rest_statuses.put_request_status || !rest_statuses.delete_request_status" class="server_issues">
                <h3>{{ $t('Server Issue detected') }}</h3>
                <p>{{ $t('server_does_not_support') }}</p>
                <p>{{ $t('using_gridpane') }}</p>
                <p><a target="_blank" rel="nofollow"
                      href="https://gridpane.com/kb/making-nginx-accept-put-delete-and-patch-verbs/">
                    {{ $t('View GridPane Article') }}
                </a></p>
            </div>
        </div>
        <el-dialog
            title="Build a better FluentCRM"
            :close-on-click-modal="false"
            :show-close="false"
            :append-to-body="true"
            class="fc_essential_modal"
            :visible.sync="show_essential_modal"
            width="30%">
            <p>
                {{ $t('Get_Improved_Help') }}
            </p>
            <span slot="footer" class="dialog-footer">
                <el-button style="color: gray; float: left;" type="text" @click="handleDataShare('no')">{{ $t('No Thanks') }}</el-button>
                <el-button type="primary" @click="handleDataShare('yes')">{{ $t('Yes, Count me in!') }}</el-button>
          </span>
        </el-dialog>
    </div>
</template>

<script type="text/babel">
import PhotoWidget from '@/Pieces/PhotoWidget';

export default {
    name: 'FluentSetupWizard',
    components: {
        PhotoWidget
    },
    data() {
        return {
            loading: false,
            active_step: 0,
            config: window.fcAdmin,
            business_settings: window.fcAdmin.business_settings,
            list_segments: [
                {
                    title: '',
                    slug: ''
                },
                {
                    title: '',
                    slug: ''
                }
            ],
            tag_segments: [
                {
                    title: '',
                    slug: ''
                },
                {
                    title: '',
                    slug: ''
                }
            ],
            share_essentials: 'no',
            install_fluentforms: 'yes',
            install_fluentcart: 'no',
            show_essential: false,
            email_address: '',
            rest_statuses: {
                put_request_status: 'checking',
                delete_request_status: 'checking'
            },
            show_essential_modal: false
        }
    },
    methods: {
        saveBusinessSettings() {
            this.$put('setting', {settings: {business_settings: this.business_settings}})
                .then(r => {
                    this.$notify.success({
                        title: this.$t('Great!'),
                        message: this.$t('Business Settings has been saved'),
                        offset: 19
                    });
                    this.active_step = 2;
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        saveLists() {
            const validLists = this.list_segments.filter((item) => {
                return item.title && item.slug;
            });

            if (!validLists.length) {
                return this.$notify.error(this.$t('Please add at least one list'));
            }
            this.loading = true;
            this.$post('lists/bulk', {
                lists: validLists
            })
                .then(response => {
                    this.$notify.success({
                        title: this.$t('Great!'),
                        message: response.message,
                        offset: 19
                    });
                    this.active_step = 3;
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        slugifyList(index) {
            if (this.list_segments[index].title) {
                this.list_segments[index].slug = this.slugify(this.list_segments[index].title);
            }
        },
        addListItem() {
            this.list_segments.push({
                title: '',
                slug: ''
            })
        },
        deleteListItem(index) {
            this.list_segments.splice(index, 1);
        },
        saveTags() {
            const validTags = this.tag_segments.filter((item) => {
                return item.title && item.slug;
            });

            if (!validTags.length) {
                return this.$notify.error(this.$t('Please add at least one Tag'));
            }
            this.loading = true;
            this.$post('tags/bulk', {
                tags: validTags
            })
                .then(response => {
                    this.$notify.success({
                        title: this.$t('Great!'),
                        message: response.message,
                        offset: 19
                    });
                    this.active_step = 4;
                })
                .catch((error) => {
                    this.handleError(error);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        slugifyTag(index) {
            if (this.tag_segments[index].title) {
                this.tag_segments[index].slug = this.slugify(this.tag_segments[index].title);
            }
        },
        addTagItem() {
            this.tag_segments.push({
                title: '',
                slug: ''
            })
        },
        deleteTagItem(index) {
            this.tag_segments.splice(index, 1);
        },
        handleDataShare(status) {
            this.share_essentials = status;
            this.show_essential_modal = false;
            this.complete(true);
        },
        complete(force = false) {
            if (this.share_essentials != 'yes' && !force) {
                this.show_essential_modal = true;
                return;
            }
            this.loading = true;
            this.$post('setting/complete-installation', {
                install_fluentform: this.install_fluentforms,
                share_essentials: this.share_essentials,
                optin_email: this.email_address,
                install_fluentcart: this.install_fluentcart
            })
                .then(response => {
                    this.$notify.success({
                        title: this.$t('Great!'),
                        message: response.message,
                        offset: 19
                    });
                })
                .catch((error) => {
                    console.log(error);
                    this.handleError(error);
                })
                .finally(() => {
                    this.active_step = 5;
                });
        },
        checkRestRequest(key, type) {
            this[type]('setting/test')
                .then((response) => {
                    if (response.message) {
                        this.rest_statuses[key] = true;
                    } else {
                        this.rest_statuses[key] = false;
                    }
                })
                .catch((errors) => {
                    this.rest_statuses[key] = false;
                });
        }
    },
    mounted() {
        this.checkRestRequest('put_request_status', '$put');
        this.checkRestRequest('delete_request_status', '$del');

        jQuery('.update-nag,.notice, #wpbody-content > .updated, #wpbody-content > .error').remove();
    }
};
</script>
