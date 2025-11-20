<?php

namespace FluentCrm\App\Models;

use FluentCrm\Framework\Support\Arr;

/**
 *  CustomCompanyField Model - DB Model for Company Contact Fields
 *
 *  Database Model
 *
 * @package FluentCrm\App\Models
 *
 * @version 2.8.50
 */
class CustomCompanyField extends CustomContactField
{
    protected $globalMetaName = 'company_custom_fields';


    public function getFieldGroups()
    {
        $fieldGroups = fluentcrm_get_option('company_field_groups');

        if (!$fieldGroups) {
            $fieldGroups = [
                [
                    'slug'  => 'default',
                    'title' => __('Custom Company Data', 'fluent-crm')
                ]
            ];
        }

        return $fieldGroups;
    }
}
