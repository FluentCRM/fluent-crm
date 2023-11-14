<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Models\Funnel;
use FluentCrm\App\Models\Lists;
use FluentCrm\App\Models\Tag;
use FluentCrm\Framework\Support\Arr;
use FluentCrm\Framework\Request\Request;

/**
 *  FormsController - REST API Handler Class
 *
 *  REST API Handler
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */

class FormsController extends Controller
{
    /**
     * Get all of the lists
     *
     * @param \FluentCrm\Framework\Request\Request $request
     * @return \WP_REST_Response|array
     * @throws \WpFluent\Exception
     */
    public function index(Request $request)
    {
        if (!defined('FLUENTFORM')) {
            return [
                'installed' => false,
                'forms'     => (object)[
                    'data'  => [],
                    'total' => 0
                ]
            ];
        }

        // Now let's find the forms which are connected with Fluent Forms
        $connectFeedForms = fluentCrmDb()->table('fluentform_form_meta')
                                         ->where('meta_key', 'fluentcrm_feeds')
                                         ->select(['form_id', 'id', 'value'])
                                         ->groupBy('form_id')
                                         ->get();


        $formIds = [];
        $connectedFormIds = [];
        foreach ($connectFeedForms as $form) {
            $formIds[] = $form->form_id;
            $settings = json_decode($form->value, true);
            $connectedFormIds[$form->form_id] = [
                'feed_id'  => $form->id,
                'settings' => $settings
            ];
        }
        // Now let's get forms ids from funnel
        $fluentFormFunnels = Funnel::where('trigger_name', 'fluentform_submission_inserted')
            ->get();

        $connectedFunnelIds = [];
        foreach ($fluentFormFunnels as $funnel) {
            $formId = Arr::get($funnel->settings, 'form_id');
            if ($formId) {
                $connectedFunnelIds[$formId] = $funnel->id;
                $formIds[] = $formId;
            }
        }

        $formIds = array_unique($formIds);
        $page = $request->get('page', 1);
        $limit = $request->get('per_page', 10);
        $offset = ($page - 1) * $limit;

        $forms = [];


        if ($formIds) {
            $crmBaseUrl = fluentcrm_menu_url_base();

            $search = sanitize_text_field($request->get('search', ''));

            $allFormsQuery = fluentCrmDb()->table('fluentform_forms')
                ->whereIn('id', $formIds);

            if($search) {
                $allFormsQuery->where('title', 'LIKE', '%'.$search.'%');
            }
            $allForms = $allFormsQuery->orderBy('id', 'DESC')
                                      ->limit($limit)
                                      ->offset($offset)
                                      ->get();

            foreach ($allForms as $form) {
                $funnelUrl = '';
                $feedUrl = '';
                $associateTags = [];
                $associateList = '';
                if (isset($connectedFunnelIds[$form->id])) {
                    $funnelUrl = $crmBaseUrl . 'funnel/' . $connectedFunnelIds[$form->id] . '/edit';
                }
                if (isset($connectedFormIds[$form->id])) {
                    $feedUrl = admin_url('admin.php?page=fluent_forms&form_id=' . $form->id . '&route=settings&sub_route=form_settings#/all-integrations/' . $connectedFormIds[$form->id]['feed_id'] . '/fluentcrm');
                    $tagIds = Arr::get($connectedFormIds[$form->id], 'settings.tag_ids');
                    if ($tagIds) {
                        $tags = Tag::whereIn('id', $tagIds)->get();
                        foreach ($tags as $tag) {
                            $associateTags[] = $tag->title;
                        }
                    }
                    $listId = Arr::get($connectedFormIds[$form->id], 'settings.list_id');
                    if ($listId && $list = Lists::find($listId)) {
                        $associateList = $list->title;
                    }
                }

                $forms[] = [
                    'id'              => $form->id,
                    'title'           => $form->title,
                    'status'          => $form->status,
                    'created_at'      => $form->created_at,
                    'funnel_url'      => $funnelUrl,
                    'feed_url'        => $feedUrl,
                    'associate_tags'  => implode(', ', $associateTags),
                    'associate_lists' => $associateList,
                    'shortcode'       => '[fluentform id="' . $form->id . '"]',
                    'edit_url'        => admin_url('admin.php?page=fluent_forms&route=editor&form_id=' . $form->id),
                    'preview_url'     => site_url('?fluent_forms_pages=1&design_mode=1&preview_id=' . $form->id)
                ];
            }
        }

        $total = count($formIds);

        return [
            'installed' => true,
            'forms'     => [
                'data'      => $forms,
                'page'      => $page,
                'per_page'  => $limit,
                'total'     => $total,
                'last_page' => ceil($total / $limit)
            ]
        ];
    }

    public function create(Request $request)
    {
        $form = $this->validate($request->all(), [
            'template_id'   => 'required',
            'title'         => 'required|unique:fluentform_forms',
            'selected_tags' => 'required',
            'selected_list' => 'required'
        ]);
        $template = $this->getSelectedTemplate($form['template_id']);
        $now = current_time('mysql');
        $formData = [
            'title'       => $form['title'],
            'status'      => 'published',
            'type'        => 'form',
            'created_by'  => get_current_user_id(),
            'created_at'  => $now,
            'updated_at'  => $now,
            'form_fields' => $template['form_fields']
        ];

        $formId = fluentCrmDb()->table('fluentform_forms')->insertGetId($formData);

        if ($template['custom_css']) {
            fluentCrmDb()->table('fluentform_form_meta')
                ->insert([
                    'form_id'  => $formId,
                    'meta_key' => '_custom_form_css',
                    'value'    => $template['custom_css']
                ]);
        }

        $defaultSettings = (new \FluentForm\App\Modules\Form\Form(wpFluentForm()))->getFormsDefaultSettings();

        if ($form['double_optin']) {
            $defaultSettings['confirmation']['messageToShow'] = __('Please check your inbox to confirm your subscription', 'fluent-crm');
        } else {
            $defaultSettings['confirmation']['messageToShow'] = __('You are successfully subscribed to our email list', 'fluent-crm');
        }
        fluentCrmDb()->table('fluentform_form_meta')
            ->insert(array(
                'form_id'  => $formId,
                'meta_key' => 'formSettings',
                'value'    => json_encode($defaultSettings)
            ));

        $feedDefaults = [
            'name'           => __('FluentCRM Integration Feed', 'fluent-crm'),
            'first_name'     => '',
            'last_name'      => '',
            'email'          => 'email',
            'other_fields'   => [
                [
                    'item_value' => '',
                    'label'      => ''
                ]
            ],
            'list_id'        => $form['selected_list'],
            'tag_ids'        => $form['selected_tags'],
            'skip_if_exists' => false,
            'double_opt_in'  => $form['double_optin'],
            'conditionals'   => [
                'conditions' => [],
                'status'     => false,
                'type'       => 'all'
            ],
            'enabled'        => true,
            'status'         => true
        ];
        if (is_array($template['map_fields'])) {
            $feedDefaults = wp_parse_args($template['map_fields'], $feedDefaults);
        }
        $feedData = [
            'meta_key' => 'fluentcrm_feeds',
            'form_id'  => $formId,
            'value'    => \json_encode($feedDefaults)
        ];

        $createdFeedId = fluentCrmDb()->table('fluentform_form_meta')
            ->insertGetId($feedData);

        do_action('fluentform/inserted_new_form', $formId, $formData);
        do_action('fluentcrm_created_new_fluentform', $formId, $formData);

        $feedUrl = admin_url('admin.php?page=fluent_forms&form_id=' . $formId . '&route=settings&sub_route=form_settings#/all-integrations/' . $createdFeedId . '/fluentcrm');

        return [
            'message'      => __('Form has been created', 'fluent-crm'),
            'created_form' => [
                'id'          => $formId,
                'shortcode'   => '[fluentform id="' . $formId . '"]',
                'feed_url'    => $feedUrl,
                'edit_url'    => admin_url('admin.php?page=fluent_forms&route=editor&form_id=' . $formId),
                'preview_url' => site_url('?fluent_forms_pages=1&design_mode=1&preview_id=' . $formId)
            ]
        ];
    }

    public function getTemplates()
    {
        return apply_filters('fluent_crm/ff_form_templates', [
            'inline_subscribe'    => [
                'label'       => __('Inline Opt-in Form', 'fluent-crm'),
                'image'       => fluentCrmMix('images/forms/form_1.svg'),
                'id'          => 'inline_subscribe',
                'form_fields' => '{"fields":[{"index":1,"element":"input_email","attributes":{"type":"email","name":"email","value":"","id":"","class":"extra_spaced","placeholder":"Email Address"},"settings":{"container_class":"","label":"","label_placement":"","help_message":"","admin_field_label":"Email Address","validation_rules":{"required":{"value":true,"message":"This field is required"},"email":{"value":true,"message":"This field must contain a valid email"}},"conditional_logics":{"type":"any","status":false,"conditions":[{"field":"","value":"","operator":""}]},"is_unique":"no","unique_validation_message":"Email address need to be unique."},"editor_options":{"title":"Email Address","icon_class":"ff-edit-email","template":"inputText"},"uniqElKey":"el_1601142291509"}],"submitButton":{"uniqElKey":"el_1524065200616","element":"button","attributes":{"type":"submit","class":""},"settings":{"align":"left","button_style":"default","container_class":"top_merged","help_message":"","background_color":"#409EFF","button_size":"md","color":"#ffffff","button_ui":{"type":"default","text":"Subscribe","img_url":""},"normal_styles":{"backgroundColor":"#409EFF","borderColor":"#409EFF","color":"#ffffff","borderRadius":"","minWidth":""},"hover_styles":{"backgroundColor":"#ffffff","borderColor":"#409EFF","color":"#409EFF","borderRadius":"","minWidth":""},"current_state":"normal_styles"},"editor_options":{"title":"Submit Button"}}}',
                'custom_css'  => $this->getFormCss('inline_subscribe'),
                'map_fields'  => [
                    'email' => 'email'
                ]
            ],
            'simple_optin'        => [
                'label'       => __('Simple Opt-in Form', 'fluent-crm'),
                'image'       => fluentCrm('url.assets') . 'images/forms/form_2.svg',
                'id'          => 'simple_optin',
                'form_fields' => '{"fields":[{"index":1,"element":"input_email","attributes":{"type":"email","name":"email","value":"","id":"","class":"","placeholder":"Your Email Address"},"settings":{"container_class":"","label":"","label_placement":"","help_message":"","admin_field_label":"Email Address","validation_rules":{"required":{"value":true,"message":"This field is required"},"email":{"value":true,"message":"This field must contain a valid email"}},"conditional_logics":[],"is_unique":"no","unique_validation_message":"Email address need to be unique."},"editor_options":{"title":"Email Address","icon_class":"ff-edit-email","template":"inputText"},"uniqElKey":"el_16011431576720.7540920979222681"}],"submitButton":{"uniqElKey":"el_1524065200616","element":"button","attributes":{"type":"submit","class":""},"settings":{"align":"left","button_style":"default","container_class":"","help_message":"","background_color":"#409EFF","button_size":"md","color":"#ffffff","button_ui":{"type":"default","text":"Subscribe To Newsletter","img_url":""},"normal_styles":{"backgroundColor":"#409EFF","borderColor":"#409EFF","color":"#ffffff","borderRadius":"","minWidth":""},"hover_styles":{"backgroundColor":"#ffffff","borderColor":"#409EFF","color":"#409EFF","borderRadius":"","minWidth":""},"current_state":"normal_styles"},"editor_options":{"title":"Submit Button"}}}',
                'custom_css'  => '',
                'map_fields'  => [
                    'email' => 'email'
                ]
            ],
            'with_name_subscribe' => [
                'label'       => __('Subscription Form', 'fluent-crm'),
                'image'       => fluentCrm('url.assets') . 'images/forms/form_3.svg',
                'id'          => 'with_name_subscribe',
                'form_fields' => '{"fields":[{"index":0,"element":"input_name","attributes":{"name":"names","data-type":"name-element"},"settings":{"container_class":"","admin_field_label":"Name","conditional_logics":{"type":"any","status":false,"conditions":[{"field":"","value":"","operator":""}]},"label_placement":""},"fields":{"first_name":{"element":"input_text","attributes":{"type":"text","name":"first_name","value":"","id":"","class":"","placeholder":"First Name"},"settings":{"container_class":"","label":"First Name","help_message":"","visible":true,"validation_rules":{"required":{"value":false,"message":"This field is required"}},"conditional_logics":[]},"editor_options":{"template":"inputText"}},"middle_name":{"element":"input_text","attributes":{"type":"text","name":"middle_name","value":"","id":"","class":"","placeholder":"","required":false},"settings":{"container_class":"","label":"Middle Name","help_message":"","error_message":"","visible":false,"validation_rules":{"required":{"value":false,"message":"This field is required"}},"conditional_logics":[]},"editor_options":{"template":"inputText"}},"last_name":{"element":"input_text","attributes":{"type":"text","name":"last_name","value":"","id":"","class":"","placeholder":"Last Name","required":false},"settings":{"container_class":"","label":"Last Name","help_message":"","error_message":"","visible":true,"validation_rules":{"required":{"value":false,"message":"This field is required"}},"conditional_logics":[]},"editor_options":{"template":"inputText"}}},"editor_options":{"title":"Name Fields","element":"name-fields","icon_class":"ff-edit-name","template":"nameFields"},"uniqElKey":"el_1570866006692"},{"index":1,"element":"input_email","attributes":{"type":"email","name":"email","value":"","id":"","class":"","placeholder":"Email Address"},"settings":{"container_class":"","label":"Email","label_placement":"","help_message":"","admin_field_label":"","validation_rules":{"required":{"value":true,"message":"This field is required"},"email":{"value":true,"message":"This field must contain a valid email"}},"conditional_logics":{"type":"any","status":false,"conditions":[{"field":"","value":"","operator":""}]},"is_unique":"no","unique_validation_message":"Email address need to be unique."},"editor_options":{"title":"Email Address","icon_class":"ff-edit-email","template":"inputText"},"uniqElKey":"el_1570866012914"}],"submitButton":{"uniqElKey":"el_1524065200616","element":"button","attributes":{"type":"submit","class":""},"settings":{"align":"left","button_style":"default","container_class":"","help_message":"","background_color":"#409EFF","button_size":"md","color":"#ffffff","button_ui":{"type":"default","text":"Subscribe","img_url":""},"normal_styles":{"backgroundColor":"#409EFF","borderColor":"#409EFF","color":"#ffffff","borderRadius":"","minWidth":""},"hover_styles":{"backgroundColor":"#ffffff","borderColor":"#409EFF","color":"#409EFF","borderRadius":"","minWidth":""},"current_state":"normal_styles"},"editor_options":{"title":"Submit Button"}}}',
                'custom_css'  => '',
                'map_fields'  => [
                    'email'      => 'email',
                    'first_name' => '{inputs.names.first_name}',
                    'last_name'  => '{inputs.names.last_name}'
                ]
            ]
        ]);
    }

    private function getFormCss($name)
    {
        $css = '';
        if ($name == 'inline_subscribe') {
            $css = '.fluent_form_FF_ID {
    position: relative;
}
.fluent_form_FF_ID .top_merged.ff_submit_btn_wrapper {
    position: absolute;
    top: 5px;
    right: 5px;
}
.fluent_form_FF_ID .extra_spaced {
    padding: 12px 15px !important;
}';
        }
        return $css;
    }

    private function getSelectedTemplate($templateId)
    {
        $templates = $this->getTemplates();
        if (isset($templates[$templateId])) {
            return $templates[$templateId];
        }
        $templatesArray = array_values($templates);
        return $templatesArray[0];
    }
}
