<?php

namespace FluentCrm\App\Http\Controllers;


use FluentCrm\App\Models\Funnel;
use FluentCrm\App\Models\Label;
use FluentCrm\Framework\Request\Request;
use FluentCrm\Framework\Support\Arr;

/**
 *  FunnelLabelController - REST API Handler Class
 *
 *  REST API Handler
 *
 * @package FluentCrm\App\Http
 *
 * @version 2.9.25
 */
class GlobalLabelController extends Controller
{
    public function getLabels()
    {
        $labels = Label::orderBy('position', 'ASC')->get();
        return [
            'labels' => $labels
        ];

    }

    public function create(Request $request)
    {
        $data = Arr::get($request->all(), 'label');

        // sanitize the data
        $labelData = [
            'slug' => sanitize_text_field($data['slug']),
            'title' => sanitize_text_field($data['title']),
        ];
        $color = sanitize_hex_color($data['color']);

        $labelData['settings'] = [
            'color' => $color
        ];

        $label = Label::create($labelData);

        return [
            'label' => $label,
            'message' => __('Labels has been Updated successfully', 'fluent-crm')
        ];
    }

    public function update(Request $request, $id)
    {
        $data = Arr::get($request->all(), 'label');

        $label = Label::findOrFail($id);

        // sanitize the data
        $labelData = [
            'slug' => sanitize_text_field($data['slug']),
            'title' => sanitize_text_field($data['title']),
        ];
        $color = sanitize_hex_color($data['color']);

        $labelData['settings'] = [
            'color' => $color
        ];

        $label->update($labelData);

        return [
            'label' => $label,
            'message' => __('Labels has been Updated successfully', 'fluent-crm')
        ];
    }


    public function delete(Request $request, $id)
    {
        $label = Label::findOrFail($id);
        if ($label) {
            $label->delete();
        }

        return [
            'message' => __('Label has been deleted successfully', 'fluent-crm')
        ];
    }

    public function deleteLabel(Request $request)
    {
        $funnelId  = $request->getSafe('funnel_id', 'intval');
        $labelSlug = $request->getSafe('label_slug');
        $action    = $request->getSafe('action');

        if (!$labelSlug) {
            return [
                'message' => __('Please provide label slug', 'fluent-crm')
            ];
        }

        switch ($action) {
            case 'delete_from_funnel':
                $this->deleteLabelFromFunnel($funnelId, $labelSlug);
                return [
                    'message' => __('Removed from funnel successfully', 'fluent-crm')
                ];
            case 'delete_from_funnel_label':
                $this->deleteLabelFromFunnelLabel($labelSlug);
                return [
                    'message' => __('Label has been deleted successfully', 'fluent-crm')
                ];
            default:
                return [
                    'message' => __('Invalid Action', 'fluent-crm')
                ];
        }
    }

    protected function deleteLabelFromFunnel($funnelId, $slug)
    {
        $funnel = Funnel::findOrFail($funnelId);
        if (!$funnel) {
            return [
                'message' => __('Label not found', 'fluent-crm')
            ];
        }

        $labelMeta = $funnel->getLabelMeta();
        if (!$labelMeta) {
            return [
                'message' => __('Label not found', 'fluent-crm')
            ];
        }
        $updatedLabels = array_diff($labelMeta->value, [$slug]);
        $funnel->updateOrDeleteLabel($updatedLabels);
    }

    protected function deleteLabelFromFunnelLabel($slug)
    {
        $customLabels = fluentcrm_get_option('funnel_custom_labels', []);
        if (!array_key_exists($slug, $customLabels)) {
            return [
                'message' => __('Label not found', 'fluent-crm')
            ];
        }

        unset($customLabels[$slug]);
        fluentcrm_update_option('funnel_custom_labels', $customLabels);

        Funnel::removeLabelFromAllFunnels($slug);
    }
}