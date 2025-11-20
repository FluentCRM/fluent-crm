<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Models\ActivityLog;
use FluentCrm\Framework\Request\Request;

class ActivityLogController extends Controller
{
    /**
     * Get all the System Logs
     * @param \FluentCrm\Framework\Request\Request $request
     * @return array || \WP_REST_Response
     */
    public function index(Request $request)
    {
        $search = sanitize_text_field($request->get('search'));

        $logs = ActivityLog::orderBy('id', 'DESC');

        if (!empty($search)) {
            $logs = $logs->where('action', 'LIKE', "%{$search}%")
                ->orWhere('description', 'LIKE', "%{$search}%");
        }

        $logs = $logs->paginate($request->per_page ?: 20);

        return [
            'logs' => $logs
        ];
    }

    public function deleteAll(Request $request)
    {
        ActivityLog::where('id', '>', 0)->delete();

        return [
            'message' => __('All activity logs have been deleted', 'fluent-crm')
        ];
    }
}
