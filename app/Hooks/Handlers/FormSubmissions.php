<?php

namespace FluentCrm\App\Hooks\Handlers;

use FluentCrm\App\Models\Subscriber;

/**
 *  FormSubmissions Class
 *
 * Fluent Forms Integration Class
 *
 * @package FluentCrm\App\Hooks
 *
 * @version 1.0.0
 */
class FormSubmissions
{
    public function pushDefaultFormProviders($providers)
    {
        if (defined('FLUENTFORM')) {
            $providers['fluentform'] = [
                'title' => __('Form Submissions (Fluent Forms)', 'fluent-crm'),
                'name'  => __('Fluent Forms', 'fluent-crm')
            ];
        }
        return $providers;
    }

    public function getFluentFormSubmissions($data, $subscriber)
    {
        if (!defined('FLUENTFORM')) {
            return $data;
        }
        $app = fluentCrm();
        $page = intval($app->request->get('page', 1));
        $per_page = intval($app->request->get('per_page', 10));

        $query = fluentCrmDb()->table('fluentform_submissions')
            ->select([
                'fluentform_submissions.id',
                'fluentform_submissions.form_id',
                'fluentform_forms.title',
                'fluentform_submissions.status',
                'fluentform_submissions.created_at'
            ])
            ->join('fluentform_forms', 'fluentform_forms.id', '=', 'fluentform_submissions.form_id')
            ->where('fluentform_submissions.response', 'LIKE', '%' . $subscriber->email . '%')
            ->limit($per_page)
            ->offset($per_page * ($page - 1))
            ->orderBy('fluentform_submissions.id', 'desc');
        if ($subscriber->user_id) {
            $query = $query->orWhere('fluentform_submissions.user_id', '=', $subscriber->user_id);
        }

        $total = $query->count();

        $submissions = $query->get();

        $formattedSubmissions = [];
        foreach ($submissions as $submission) {
            $submissionUrl = admin_url('admin.php?page=fluent_forms&route=entries&form_id=' . $submission->form_id . '#/entries/' . $submission->id);
            $actionUrl = '<a target="_blank" href="' . $submissionUrl . '">view</a>';
            $formattedSubmissions[] = [
                'id'           => '#' . $submission->id,
                'title'   => $submission->title,
                'Status'       => $submission->status,
                'Submitted At' => $submission->created_at,
                'action'       => $actionUrl
            ];
        }

        return [
            'total' => $total,
            'data'  => $formattedSubmissions,
            'columns_config' => [
                'id' => [
                    'label' => 'ID',
                    'width' => '100px'
                ],
                'title' => [
                    'label' => 'Form Title'
                ],
                'Status' => [
                    'label' => 'Status',
                    'width' => '100px'
                ],
                'Submitted At' => [
                    'label' => 'Submitted At',
                    'width' => '180px'
                ],
                'action' => [
                    'label' => 'Action',
                    'width' => '100px'
                ]
            ]
        ];
    }

    public function parseEditorCodes($code, $form, $keys)
    {
        $contact = FluentCrmApi('contacts')->getCurrentContact(true, true);

        $providedKey = $keys[0];

        // maybe has fallback value
        $dynamicKey = explode('|', $providedKey);
        $fallBack = '';
        if (count($dynamicKey) > 1) {
            $fallBack = $dynamicKey[1];
        }
        $ref = $dynamicKey[0];

        if (!$contact) {
            return $fallBack;
        }

        $validMainProps = (new Subscriber)->getFillable();
        $validMainProps[] = 'id';

        if (in_array($ref, $validMainProps)) {
            if ($contact->{$ref}) {
                return $contact->{$ref};
            }

            return $fallBack;
        }

        // Maybe it's a custom field
        $customData = $contact->custom_fields();

        if ($customData && !empty($customData[$ref])) {
            $value = $customData[$ref];
            if (is_array($value)) {
                return implode(',', $value);
            }

            return $customData[$ref];
        }

        $listMaps = [
            'list_ids' => 'id',
            'list_titles' => 'title',
            'list_slugs' => 'slug'
        ];

        $tagMaps = [
            'tag_ids' => 'id',
            'tag_titles' => 'title',
            'tag_slugs' => 'slug'
        ];


        if(isset($listMaps[$ref])) {
            $listProps = [];
            foreach ($contact->lists as $list) {
                $listProps[] = $list->{$listMaps[$ref]};
            }
            if($listProps) {
                return trim(implode(', ', $listProps));
            }
        } else if(isset($tagMaps[$ref])) {
            $tagProps = [];
            foreach ($contact->tags as $tag) {
                $tagProps[] = $tag->{$tagMaps[$ref]};
            }
            if($tagProps) {
                return trim(implode(', ', $tagProps));
            }
        }

        return $fallBack;
    }
}
