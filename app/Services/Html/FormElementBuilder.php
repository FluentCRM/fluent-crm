<?php

namespace FluentCrm\App\Services\Html;

use FluentCrm\Framework\Support\Arr;

class FormElementBuilder
{

    public function renderFields($fields, $print = false)
    {
        $this->addExternalScriptsAndCss();
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
            return (string) Arr::get($field, 'html');
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
        } else if ($type == 'select-multi') {
            $inputHtml = $this->renderMultiSelect($field);
        } else if ($type == 'radio') {
            $inputHtml = $this->renderRadio($field);
        } else if ($type == 'checkboxes') {
            $inputHtml = $this->renderCheckboxes($field);
        } else if ($type == 'date') {
            $inputHtml = $this->renderDate($field);
        } else if ($type == 'textarea') {
            $inputHtml = $this->renderTextarea($field);
        } else if ($type == 'number') {
            $inputHtml = $this->renderInput($field);
        } else if ($type == 'custom_date') {
            $inputHtml = $this->renderDatePicker($field);
        } else if ($type == 'custom_date_time') {
            $inputHtml = $this->renderDateTimePicker($field);
        }

        return $this->renderLabel($field, $inputHtml);
    }

    public function renderSelect($field)
    {
        $atts = $this->buildAttributes([
            'id' => Arr::get($field, 'id'),
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

    public function renderRadio($field)
    {
        $name = $field['name'];
        $html = '<div class="fc_radio_buttons">';

        foreach ($field['options'] as $key => $label) {
            $attributes = [
                'type' => 'radio',
                'name' => $name,
                'value' => $key
            ];

            if ($key == $field['value']) {
                $attributes['checked'] = true;
            }

            $html .= '<label class="fc_radio_item">';
            $html .= '<input ' . $this->buildAttributes($attributes) . ' /> ' . esc_html($label);
            $html .= '</label>';
        }

        $html .= '</div>';

        return $html;
    }

    public function renderCheckboxes($field)
    {
        $name = $field['name'];
        $options = $field['options'];
        $selectedValues = (array) $field['value'];

        $html = '<div class="fc_checkboxes">';

        $isAssoc = array_keys($options) !== range(0, count($options) - 1);

        foreach ($options as $optionKey => $list_option) {
            $optionValue = $isAssoc ? $optionKey : $list_option;

            $attrbutes = [
                'type'  => 'checkbox',
                'name'  => esc_attr($name) . '[]',
                'value' => esc_attr($optionValue)
            ];

            if (in_array($optionValue, $selectedValues)) {
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
        $containerClass = 'fc_field fc_field_' . $field['name'] . ' fc_field_' . $field['type'];

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
                jQuery(document).ready(function() {
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

        $html .= '<button ' . $this->buildAttributes(Arr::get($field, 'atts', [])) . '>' . wp_kses_post($label) . '</button>';

        $html .= '</div>';

        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

    }

    private function buildAttributes($atts)
    {
        $items = [];

        $singleKeys = ['required', 'disabled', 'readonly', 'checked', 'selected', 'multiple'];

        foreach ($atts as $key => $value) {
            if ($value && in_array($key, $singleKeys)) {
                $items[] = $key;
                continue;
            }

            $items[] = esc_attr($key) . '="' . esc_html($value) . '"';
        }

        return implode(' ', $items);
    }

    private function renderTextarea($field)
    {
        $atts = $this->buildAttributes($field['atts']);
        $value = Arr::get($field, 'value', '');
        return '<textarea ' . $atts . '>' . esc_html($value) . '</textarea>';
    }
    public function renderMultiSelect($field)
    {
        $name = Arr::get($field, 'name');
        if (substr($name, -2) !== '[]') {
            $name .= '[]';
        }

        $atts = $this->buildAttributes([
            'id'               => Arr::get($field, 'id'),
            'name'             => $name,
            'multiple'         => true,
            'class'            => 'fc_input_control fc-js-choice-multi',
            'data-placeholder' => esc_attr(Arr::get($field, 'placeholder', __('Select options', 'fluent-crm'))),
        ]);

        $values = is_array($field['value']) ? $field['value'] : [];

        $html = '<select ' . $atts . '>';
        if ($placeholder = Arr::get($field, 'placeholder')) {
            $html .= '<option value="" disabled hidden>' . esc_html($placeholder) . '</option>';
        }
        foreach ($field['options'] as $key => $label) {
            $selected = in_array($key, $values) ? ' selected="selected"' : '';
            $html .= '<option value="' . esc_attr($key) . '" ' . $selected . '>' . esc_html($label) . '</option>';
        }
        $html .= '</select>';

        return $html;
    }

    public function renderDateTimePicker($field)
    {

        // Build attributes for the input field
        $atts = $this->buildAttributes([
            'type'        => 'text',
            'id'          => Arr::get($field, 'id'),
            'name'        => Arr::get($field, 'name'),
            'value'       => esc_attr($field['value'] ?? ''),
            'class'       => 'fc_input_control fc-js-datetime-picker',
            'placeholder' => esc_attr(Arr::get($field, 'placeholder', 'Select date & time')),
        ]);

        // Return the input element
        return '<input ' . $atts . ' />';
    }
    public function renderDatePicker($field)
    {
        // Build attributes for the input field
        $atts = $this->buildAttributes([
            'type'        => 'text',
            'id'          => Arr::get($field, 'id'),
            'name'        => Arr::get($field, 'name'),
            'value'       => esc_attr($field['value'] ?? ''),
            'class'       => 'fc_input_control fc-js-date-picker',
            'placeholder' => esc_attr(Arr::get($field, 'placeholder', 'Select date')),
        ]);

        // Return the input element
        return '<input ' . $atts . ' />';
    }

    private function addExternalScriptsAndCss()
    {
        add_action('wp_footer', function () {
            $this->renderDatePickerScript();
            $this->renderDateTimePickerScript();
            $this->renderMultiSelectScript();
        });
        
        add_action('wp_enqueue_scripts', function () {
            $this->enqueueDatePickerAssets();
            $this->enqueueMultiSelectAssets();
        });
    }

    private function renderDatePickerScript()
    {
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const datePickers = document.querySelectorAll('.fc-js-date-picker');
                datePickers.forEach(picker => {
                    if (!picker.dataset.fpInitialized) {
                        flatpickr(picker, { dateFormat: 'Y-m-d', allowInput: true });
                        picker.dataset.fpInitialized = true;
                    }
                });
            });
        </script>
        <?php
    }

    private function renderDateTimePickerScript()
    {
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const dateTimePickers = document.querySelectorAll('.fc-js-datetime-picker');
                dateTimePickers.forEach(picker => {
                    if (!picker.dataset.fpInitialized) {
                        flatpickr(picker, {
                            enableTime: true,
                            dateFormat: 'Y-m-d H:i:S',
                            time_24hr: true,
                            allowInput: true
                        });
                        picker.dataset.fpInitialized = true;
                    }
                });
            });
        </script>
        <?php
    }

    private function renderMultiSelectScript()
    {
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const selects = document.querySelectorAll('.fc-js-choice-multi');
                selects.forEach(select => {
                    if (!select.dataset.choicesInitialized) {
                        new Choices(select, {
                            removeItemButton: true,
                            placeholderValue: select.dataset.placeholder || '<?php echo esc_js(__('Select options', 'fluent-crm')); ?>',
                            searchEnabled: true,
                            itemSelectText: '',
                            noResultsText: '<?php echo esc_js(__('No matching options found', 'fluent-crm')); ?>',
                            noChoicesText: '<?php echo esc_js(__('No options available', 'fluent-crm')); ?>'
                        });
                        select.dataset.choicesInitialized = true;
                    }
                });
            });
        </script>
        <?php
    }

    private function enqueueDatePickerAssets()
    {
        wp_enqueue_style('flatpickr-css', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css', [], '4.6.13');
        wp_enqueue_script('flatpickr-js', 'https://cdn.jsdelivr.net/npm/flatpickr', [], '4.6.13', true);
    }

    private function enqueueMultiSelectAssets()
    {
        wp_enqueue_style('choices-css', 'https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css', [], '10.0.0');
        wp_enqueue_script('choices-js', 'https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js', ['jquery'], '10.0.0', true);
    }
}
