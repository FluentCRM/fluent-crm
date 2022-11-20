<?php

namespace FluentCrm\App\Services\Html;

use FluentCrm\Framework\Support\Arr;

class FormElementBuilder
{

    public function renderFields($fields, $print = false)
    {
        $html = '';
        foreach ($fields as $field) {
            $html .= $this->renderField($field);
        }
        if ($print) {
            echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        return $html;
    }

    public function renderField($field)
    {
        $type = Arr::get($field, 'type');
        if ($type == 'container') {
            return $this->renderContainer($field);
        }

        if ($type == 'raw_html') {
            return (string)Arr::get($field, 'html');
        }

        if ($type == 'hidden') {
            $atts = $this->buildAttributes($field['atts']);
            return '<input type="hidden" ' . $atts . '"/>';
        }

        $inputHtml = '';

        if ($type == 'input') {
            $inputHtml = $this->renderInput($field);
        } else if ($type == 'select') {
            $inputHtml = $this->renderSelect($field);
        } else if ($type == 'checkboxes') {
            $inputHtml = $this->renderCheckboxes($field);
        } else if ($type == 'date') {
            $inputHtml = $this->renderDate($field);
        }

        return $this->renderLabel($field, $inputHtml);

    }

    public function renderSelect($field)
    {
        $atts = $this->buildAttributes([
            'id'   => Arr::get($field, 'id'),
            'name' => Arr::get($field, 'name'),
        ]);

        $html = '<select ' . $atts . '>';

        if ($placeholder = Arr::get($field, 'placeholder')) {
            $selected = $field['value'] ? '' : 'selected';
            $html .= '<option ' . $selected . ' value="">' . esc_html($placeholder) . '</option>';
        }

        foreach ($field['options'] as $key => $label) {
            $selected = ($key == $field['value']) ? 'selected' : '';
            $html .= '<option ' . $selected . ' value="' . esc_html($key) . '">' . esc_html($label) . '</option>';
        }

        $html .= '</select>';

        return $html;
    }

    public function renderCheckboxes($field)
    {

        $name = $field['name'];
        $html = '<div class="fc_checkboxes">';
        foreach ($field['options'] as $optionKey => $list_option) {
            $attrbutes = [
                'type'  => 'checkbox',
                'name'  => esc_attr($name) . '[]',
                'value' => $optionKey
            ];

            if (in_array($optionKey, $field['value'])) {
                $attrbutes['checked'] = true;
            }

            $html .= '<label class="fc_list_items">';
            $html .= '<input ' . $this->buildAttributes($attrbutes) . ' /> ' . esc_html($list_option);
            $html .= '</label>';
        }

        $html .= '</div>';

        return $html;
    }

    public function renderContainer($field)
    {
        $innerFields = Arr::get($field, 'fields', []);
        if (!$innerFields) {
            return '';
        }

        $html = '<div class="fc_field_container ' . esc_attr(Arr::get($field, 'container_class')) . '">';
        $html .= $this->renderFields($innerFields);
        $html .= '</div>';
        return $html;
    }

    public function renderLabel($field, $innerHtml = '')
    {
        $containerClass = 'fc_field fc_field_' . $field['name'];

        if ($givenClass = Arr::get($field, 'container_class')) {
            $containerClass .= ' ' . $givenClass;
        }

        $html = '<div class="' . esc_attr($containerClass) . '">';

        if ($label = Arr::get($field, 'label')) {
            if ($id = Arr::get($field, 'id')) {
                $labelAtts = $this->buildAttributes([
                    'for' => $id
                ]);
            } else {
                $labelAtts = '';
            }
            $required = '';
            if (Arr::get($field, 'required')) {
                $required = ' <span class="fc_required_mark">*</span>';
            }

            $html .= '<label ' . $labelAtts . '>' . esc_html($label) . $required . '</label>';
        }

        return $html . $innerHtml . '</div>';
    }

    public function renderInput($field)
    {
        $atts = Arr::get($field, 'atts', []);
        $atts['name'] = $field['name'];

        if (!empty($field['required'])) {
            $atts['required'] = true;
        }

        if (!empty($field['id'])) {
            $atts['id'] = $field['id'];
        }

        if (empty($atts['class'])) {
            $atts['class'] = 'fc_input_control';
        } else {
            $atts['class'] .= ' fc_input_control';
        }

        $atts['value'] = $field['value'];

        return '<input ' . $this->buildAttributes($atts) . '/>';
    }

    public function renderDate($field)
    {
        wp_enqueue_script('combodate', FLUENTCRM_PLUGIN_URL . 'assets/libs/combodate/combodate.js', ['jquery', 'moment'], '1.0.7', true);

        add_action('wp_footer', function () use ($field) {
            ?>
            <script>
                jQuery(document).ready(function () {
                    jQuery('#<?php echo esc_attr($field['id']); ?>').combodate();
                });
            </script>
            <?php
        });
        return $this->renderInput($field);
    }

    public function renderButton($field)
    {
        $containerClass = 'fc_field fc_field_btn';

        if ($givenClass = Arr::get($field, 'container_class')) {
            $containerClass .= ' ' . $givenClass;
        }

        $html = '<div class="' . esc_attr($containerClass) . '">';

        $label = Arr::get($field, 'btn_text');

        $html .= '<button '.$this->buildAttributes(Arr::get($field, 'atts', [])).'>'.wp_kses_post($label).'</button>';

        $html .= '</div>';

        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

    }

    private function buildAttributes($atts)
    {
        $items = [];

        $singleKeys = ['required', 'disabled', 'readonly', 'checked', 'selected'];

        foreach ($atts as $key => $value) {
            if ($value && in_array($key, $singleKeys)) {
                $items[] = $key;
                continue;
            }

            $items[] = esc_attr($key) . '="' . esc_html($value) . '"';
        }

        return implode(' ', $items);
    }
}
