<?php

namespace FluentCrm\App\Services\Funnel;

class StaticTemplates
{
    public static function get()
    {
        return [
            [
                'id'          => 1,
                'label'       => 'Welcome Email',
                'description' => 'Send a welcome email to new subscribers',
                'category'    => 'email',
                'icon'        => 'el-icon-message',
                'disabled'    => false,
                'depends_on'    => ['crm', 'email'],
                'ribbon'      => 'Free',
                'funnel_data' => [
                    "id"              => 20,
                    "type"            => "funnels",
                    "title"           => "List Applied (Created at 2024-07-03)",
                    "trigger_name"    => "fluentcrm_contact_added_to_lists",
                    "status"          => "published",
                    "conditions"      => [
                        "run_multiple" => "no",
                    ],
                    "settings"        => [
                        "lists"       => [
                        ],
                        "select_type" => "any",
                    ],
                    "created_by"      => "1",
                    "created_at"      => "2024-07-03 11:10:24",
                    "updated_at"      => "2024-07-03 17:56:07",
                    "settingsFields"  => [
                        "title"     => "List Applied",
                        "sub_title" => "This will run when selected lists have been applied to a contact",
                        "fields"    => [
                            "lists"       => [
                                "type"        => "option_selectors",
                                "option_key"  => "lists",
                                "is_multiple" => true,
                                "label"       => "Select Lists",
                                "placeholder" => "Select List",
                                "creatable"   => true,
                            ],
                            "select_type" => [
                                "label"      => "Run When",
                                "type"       => "radio",
                                "options"    => [
                                    [
                                        "id"    => "any",
                                        "title" => "contact added in any of the selected lists",
                                    ],
                                    [
                                        "id"    => "all",
                                        "title" => "contact added in all of the selected lists",
                                    ],
                                ],
                                "dependency" => [
                                    "depends_on" => "lists",
                                    "operator"   => "!=",
                                    "value"      => [
                                    ],
                                ],
                            ],
                        ],
                    ],
                    "conditionFields" => [
                        "run_multiple" => [
                            "type"        => "yes_no_check",
                            "label"       => "",
                            "check_label" => "Restart the Automation Multiple times for a contact for this event. (Only enable if you want to restart automation for the same contact)",
                            "inline_help" => "If you enable, then it will restart the automation for a contact if the contact already in the automation. Otherwise, It will just skip if already exist",
                        ],
                    ],
                    "sequences"       => [
                        [
                            "id"             => 21,
                            "funnel_id"      => "20",
                            "parent_id"      => "0",
                            "action_name"    => "add_contact_to_company",
                            "condition_type" => null,
                            "type"           => "action",
                            "title"          => "Apply Company",
                            "description"    => "Add contact to the selected company",
                            "status"         => "published",
                            "conditions"     => [
                            ],
                            "settings"       => [
                                "company" => null,
                            ],
                            "note"           => null,
                            "delay"          => "0",
                            "c_delay"        => "0",
                            "sequence"       => "1",
                            "created_by"     => "1",
                            "created_at"     => "2024-07-03 17:55:55",
                            "updated_at"     => "2024-07-03 17:56:07",
                        ],
                        [
                            "id"             => 22,
                            "funnel_id"      => "20",
                            "parent_id"      => "0",
                            "action_name"    => "fluentcrm_wait_times",
                            "condition_type" => null,
                            "type"           => "action",
                            "title"          => "Wait X Days/Hours",
                            "description"    => "Wait defined timespan before execute the next action",
                            "status"         => "published",
                            "conditions"     => [
                            ],
                            "settings"       => [
                                "wait_type"         => "unit_wait",
                                "wait_time_amount"  => 0,
                                "wait_time_unit"    => "days",
                                "is_timestamp_wait" => "",
                                "wait_date_time"    => "",
                                "to_day"            => [
                                ],
                                "to_day_time"       => "",
                            ],
                            "note"           => null,
                            "delay"          => "0",
                            "c_delay"        => "0",
                            "sequence"       => "2",
                            "created_by"     => "1",
                            "created_at"     => "2024-07-03 17:56:01",
                            "updated_at"     => "2024-07-03 17:56:07",
                        ],
                    ],
                    "site_hash"       => "74401346bac500b8b0e449bdc75b2255",
                    "export_date"     => "2024-07-03 11:56:26",
                ],
            ],
        ];
    }

}
