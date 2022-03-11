<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Models\Template;
use FluentCrm\App\Services\Helper;
use FluentCrm\Framework\Support\Arr;
use FluentCrm\Framework\Request\Request;

/**
 *  TemplateController - REST API Handler Class
 *
 *  REST API Handler
 *
 * @package FluentCrm\App\Http
 *
 * @version 1.0.0
 */
class TemplateController extends Controller
{
    public function templates(Request $request)
    {
        $order = $request->get('order') ?: 'desc';
        $orderBy = $request->get('orderBy') ?: 'ID';

        $templates = Template::emailTemplates(
            $request->get('types', ['publish', 'draft'])
        );
        if (!empty($request->get('search'))) {
            $templates = $templates->where('post_title', 'LIKE', '%' . $request->get('search') . '%');
        }
        $templates = $templates->orderBy($orderBy, $order)
            ->paginate();

        return $this->sendSuccess([
            'templates' => $templates
        ]);
    }

    public function template(Request $request, $templateId = 0)
    {
        $template = Template::find($templateId);

        if ($template) {
            $editType = get_post_meta($template->ID, '_edit_type', true);
            if (!$editType) {
                $editType = 'html';
            }
            $templateData = [
                'post_title'      => $template->post_title,
                'post_content'    => $template->post_content,
                'post_excerpt'    => $template->post_excerpt,
                'email_subject'   => get_post_meta($template->ID, '_email_subject', true),
                'edit_type'       => $editType,
                'design_template' => get_post_meta($template->ID, '_design_template', true),
                'settings'        => [
                    'template_config' => get_post_meta($template->ID, '_template_config', true)
                ]
            ];
        } else {
            $defaultTemplate = Helper::getDefaultEmailTemplate();
            $templateData = [
                'post_title'      => '',
                'post_content'    => '',
                'post_excerpt'    => '',
                'email_subject'   => '',
                'edit_type'       => 'html',
                'design_template' => $defaultTemplate,
                'settings'        => [
                    'template_config' => Helper::getTemplateConfig($defaultTemplate)
                ]
            ];
        }

        return $this->sendSuccess([
            'template' => $templateData
        ]);
    }

    public function create(Request $request)
    {
        $template = wp_unslash($this->request->get('template'));
        $postData = Arr::only($template, [
            'post_title',
            'post_content',
            'post_excerpt'
        ]);

        $postData['post_modified'] = current_time('mysql');
        $postData['post_modified_gmt'] = date('Y-m-d H:i:s');
        $postData['post_date'] = current_time('mysql');
        $postData['post_date_gmt'] = date('Y-m-d H:i:s');
        $postData['post_type'] = fluentcrmTemplateCPTSlug();

        $templateId = wp_insert_post($postData);

        $designTemplate = Arr::get($template, 'design_template');
        if (!$designTemplate) {
            $designTemplate = Helper::getDefaultEmailTemplate();
        }

        update_post_meta($templateId, '_email_subject', Arr::get($template, 'email_subject'));
        update_post_meta($templateId, '_edit_type', Arr::get($template, 'edit_type'));
        update_post_meta($templateId, '_template_config', Arr::get($template, 'settings.template_config'));
        update_post_meta($templateId, '_design_template', $designTemplate);

        return $this->sendSuccess([
            'message'     => __('Template successfully created', 'fluent-crm'),
            'template_id' => $templateId
        ]);
    }

    public function duplicate($templateId)
    {
        $template = Template::find($templateId)->toArray();
        $postData = Arr::only($template, [
            'post_title',
            'post_content',
            'post_excerpt'
        ]);

        $postData['post_modified'] = current_time('mysql');
        $postData['post_modified_gmt'] = date('Y-m-d H:i:s');
        $postData['post_date'] = current_time('mysql');
        $postData['post_date_gmt'] = date('Y-m-d H:i:s');
        $postData['post_type'] = fluentcrmTemplateCPTSlug();
        $postData['post_title'] = __('[Duplicate] ', 'fluent-crm') . $postData['post_title'];

        $newTemplateId = wp_insert_post($postData);

        update_post_meta($newTemplateId, '_email_subject', get_post_meta($templateId, '_email_subject', true));
        update_post_meta($newTemplateId, '_edit_type', get_post_meta($templateId, '_edit_type', true));
        update_post_meta($newTemplateId, '_template_config', get_post_meta($templateId, '_template_config', true));
        update_post_meta($newTemplateId, '_design_template', get_post_meta($templateId, '_design_template', true));

        return $this->sendSuccess([
            'message'     => __('Template successfully duplicated', 'fluent-crm'),
            'template_id' => $newTemplateId
        ]);
    }

    public function update(Request $request, $id)
    {
        $template = wp_unslash($this->request->get('template'));
        $postData = Arr::only($template, [
            'post_title',
            'post_content',
            'post_excerpt'
        ]);

        $postData['post_modified'] = current_time('mysql');
        $postData['post_modified_gmt'] = date('Y-m-d H:i:s');
        Template::where('ID', $id)->update($postData);
        update_post_meta($id, '_email_subject', Arr::get($template, 'email_subject'));
        update_post_meta($id, '_edit_type', Arr::get($template, 'edit_type'));
        update_post_meta($id, '_design_template', Arr::get($template, 'design_template'));
        update_post_meta($id, '_template_config', Arr::get($template, 'settings.template_config'));

        return $this->sendSuccess([
            'message'     => __('Template successfully updated', 'fluent-crm'),
            'template_id' => $id
        ]);
    }

    public function delete(Request $request, $id)
    {
        Template::findOrFail($id)->delete();

        return $this->sendSuccess([
            'message' => __('The template has been deleted successfully.', 'fluent-crm')
        ]);
    }

    public function render()
    {
        $rendered = Template::findOrFail(
            $this->request->get('ID')
        )->render();

        return $this->sendSuccess($rendered);
    }

    public function allTemplates()
    {
        return $this->sendSuccess([
            'templates'  => Template::emailTemplates(['publish'])->orderBy('ID', 'desc')->get(),
            'smartcodes' => $this->smartCodes()
        ]);
    }

    public function getSmartCodes()
    {
        return $this->sendSuccess([
            'smartcodes' => $this->smartCodes()
        ]);
    }

    protected function smartCodes()
    {
        return Helper::getGlobalSmartCodes();
    }
}
