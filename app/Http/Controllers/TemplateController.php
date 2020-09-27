<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Models\Template;
use FluentCrm\App\Services\Helper;
use FluentCrm\Includes\Helpers\Arr;
use FluentCrm\Includes\Request\Request;


class TemplateController extends Controller
{
    public function templates(Request $request)
    {
        $order = $request->get('order') ?: 'desc';
        $orderBy = $request->get('orderBy') ?: 'ID';

        $templates = Template::emailTemplates(
            $request->get('types', ['publish'])
        )
            ->orderBy($orderBy, ($order == 'ascending' ? 'asc' : 'desc'))
            ->paginate();

        return $this->sendSuccess([
            'templates' => $templates
        ]);
    }

    public function template(Request $request, $templateId)
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
                'settings' => [
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
                'settings' => [
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
        $templateId = Template::insert($postData);

        update_post_meta($templateId, '_email_subject', Arr::get($template, 'email_subject'));
        update_post_meta($templateId, '_edit_type', Arr::get($template, 'edit_type'));
        update_post_meta($templateId, '_template_config', Arr::get($template, 'settings.template_config'));
        update_post_meta($templateId, '_design_template', Helper::getDefaultEmailTemplate());

        return $this->sendSuccess([
            'message'     => __('Template successfully updated', 'fluentcrm'),
            'template_id' => $templateId
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
            'message'     => __('Template successfully updated', 'fluentcrm'),
            'template_id' => $id
        ]);
    }

    public function delete(Request $request, $id)
    {
        Template::findOrFail($id)->delete();

        return $this->sendSuccess([
            'message' => 'The template has been deleted successfully.'
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
