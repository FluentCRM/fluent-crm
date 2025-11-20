<template>
    <div class="fc_global_form_builder">
        <el-form @submit.prevent.native="nativeSave" :data="formData" :label-position="label_position">
            <with-label v-for="(field,fieldIndex) in fields" v-if="dependancyPass(field)" :key="fieldIndex" :field="field">
                <component :is="field.type" v-model="formData[fieldIndex]" :field="field"/>
            </with-label>
        </el-form>
    </div>
</template>

<script type="text/babel">
import WithLabel from './_WithLabel';
import InputText from './_InputText';
import PhotoWidget from '../PhotoWidget'
import WpEditor from './_WpEditorField'
import InputTextPopper from './_InputTextPopper'
import ImageRadio from './_ImageRadio'
import InputRadio from './_InputRadio'
import InputOption from './_InputOption'
import InputColor from './_InputColor'
import InputDate from './_InputDate'
import InputNumber from './_InputNumber'
import OptionSelector from './_OptionSelector'
import AjaxSelector from './_AjaxSelector'
import InlineCheckbox from './_InlineCheckbox'
import CheckboxGroup from './_CheckboxGroup'
import VerifiedEmailInput from './_VerifiedEmailInput'
import InputTagList from './_InputTagList'
import HtmlViewer from './_HtmlViewer'
import TagAddRemoveMapping from './TagAddRemoveMapping'
import FormManyDropdownMapper from './_FormManyDropdownMapper'
import WpBaseEditor from '../_wp_editor';

export default {
    name: 'global_form_builder',
    components: {
        WithLabel,
        InputText,
        PhotoWidget,
        InputTextPopper,
        WpEditor,
        ImageRadio,
        InputRadio,
        InputOption,
        AjaxSelector,
        InputColor,
        InputNumber,
        OptionSelector,
        InlineCheckbox,
        CheckboxGroup,
        VerifiedEmailInput,
        InputTagList,
        HtmlViewer,
        TagAddRemoveMapping,
        InputDate,
        WpBaseEditor,
        'form-many-drop-down-mapper': FormManyDropdownMapper
    },
    props: {
        formData: {
            type: Object,
            required: false,
            default() {
                return {}
            }
        },
        label_position: {
            required: false,
            type: String,
            default() {
                return 'top';
            }
        },
        fields: {
            required: true,
            type: Object
        }
    },
    methods: {
        nativeSave() {
            this.$emit('nativeSave', this.formData);
        },
        /**
         * Helper function for show/hide dependent elements
         & @return {Boolean}
         */
        compare(operand1, operator, operand2) {
            switch (operator) {
                case '=':
                    return operand1 === operand2
                case '!=':
                    return operand1 !== operand2
            }
        },

        /**
         * Checks if a prop is dependent on another
         * @param listItem
         * @return {boolean}
         */
        dependancyPass(listItem) {
            if (listItem.dependency) {
                const optionPaths = listItem.dependency.depends_on.split('/');

                const dependencyVal = optionPaths.reduce((obj, prop) => {
                    return obj[prop]
                }, this.formData);

                if (this.compare(listItem.dependency.value, listItem.dependency.operator, dependencyVal)) {
                    return true;
                }
                return false;
            }
            return true;
        }
    }
}
</script>
