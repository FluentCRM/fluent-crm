<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Models\SystemLog;
use FluentCrm\Framework\Request\Request;

/**
 *  SystemLog Controller - REST API Handler Class
 *
 *  REST API Handler
 *
 * @package FluentCrm\App\Http
 *
 * @version 2.8.40
 */
class SystemLogController extends Controller
{
    /**
     * Get all the System Logs
     * @param \FluentCrm\Framework\Request\Request $request
     * @return array || \WP_REST_Response
     */
    public function index(Request $request)
    {
        $search = sanitize_text_field($request->get('search'));

        $logs = SystemLog::orderBy('id', 'DESC');

        if (!empty($search)) {
            $logs = $logs->where('title', 'LIKE', "%{$search}%")
                ->orWhere('description', 'LIKE', "%{$search}%");
        }

        $logs = $logs->paginate($request->per_page ?: 20);

        return [
            'logs' => $logs
        ];
    }

    public function deleteAll(Request $request)
    {
        SystemLog::where('id', '>', 0)->delete();

        return [
            'message' => __('All logs has been deleted', 'fluent-crm')
        ];
    }
}
