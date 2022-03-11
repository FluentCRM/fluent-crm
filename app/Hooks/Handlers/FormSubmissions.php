<?php

namespace FluentCrm\App\Hooks\Handlers;

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
        if(defined('FLUENTFORM')) {
            $providers['fluentform'] = [
                'title' => __('Form Submissions (Fluent Forms)', 'fluent-crm'),
                'name' => __('Fluent Forms', 'fluent-crm')
            ];
        }
        return $providers;
    }

    public function getFluentFormSubmissions($data, $subscriber)
    {
        if(!defined('FLUENTFORM')) {
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
                    ->where('fluentform_submissions.response', 'LIKE', '%'.$subscriber->email.'%')
                    ->limit($per_page)
                    ->offset($per_page * ($page - 1))
                    ->orderBy('fluentform_submissions.id', 'desc');
        if($subscriber->user_id) {
            $query = $query->orWhere('fluentform_submissions.user_id', '=', $subscriber->user_id);
        }

        $total = $query->count();

        $submissions = $query->get();

        $formattedSubmissions = [];
        foreach ($submissions as $submission) {
            $submissionUrl = admin_url('admin.php?page=fluent_forms&route=entries&form_id='.$submission->form_id.'#/entries/'.$submission->id);
            $actionUrl = '<a target="_blank" href="'.$submissionUrl.'">View Submission</a>';
            $formattedSubmissions[] = [
                'id' => '#'.$submission->id,
                'Form Title' => $submission->title,
                'Status' => $submission->status,
                'Submitted At' => $submission->created_at,
                'action' => $actionUrl
            ];
        }

        return [
            'total' => $total,
            'data' => $formattedSubmissions
        ];
    }
}
