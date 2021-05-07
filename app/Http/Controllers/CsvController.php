<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\Includes\Helpers\Arr;
use FluentCrm\Includes\Request\Request;
use League\Csv\Reader;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\Includes\Libs\FileSysytem;

class CsvController extends Controller
{

    /**
     * @param \FluentCrm\Includes\Request\Request $request
     * @return \WP_REST_Response
     * @throws \FluentValidator\ValidationException
     */
    public function upload(Request $request)
    {
        if (is_multisite()) {
            add_filter('upload_mimes', function ($types) {
                if (empty($types['csv'])) {
                    $types['csv'] = 'text/csv';
                }
                return $types;
            });
        }

        $files = $this->validate($this->request->files(), [
            'file' => 'mimetypes:' . implode(',', fluentcrmCsvMimes())
        ], [
            'file.mimetypes' => __('The file must be a valid CSV.', 'fluent-crm')
        ]);

        $delimeter = $request->get('delimiter', 'comma');


        if ($delimeter == 'comma') {
            $delimeter = ',';
        } else {
            $delimeter = ';';
        }

        $uploadedFiles = FileSysytem::put($files);

        try {
            $csv = Reader::createFromString(
                FileSysytem::get($uploadedFiles[0]['file'])
            );
            $csv->setDelimiter($delimeter);
            $headers = $csv->fetchOne();
        } catch (\Exception $exception) {
            return $this->sendError([
                'message' => $exception->getMessage()
            ]);
        }

        if (count($headers) != count(array_unique($headers))) {
            return $this->sendError([
                'message' => 'Looks like your csv has same name header multiple times. Please fix your csv first and remove any duplicate header column'
            ]);
        }

        $mappables = Subscriber::mappables();
        $headerItems = array_filter($headers);
        $subscriberColumns = Subscriber::getColumns();

        $maps = [];

        foreach ($headerItems as $headerItem) {
            $tableMap = (in_array($headerItem, $subscriberColumns)) ? $headerItem : null;

            if(!$tableMap) {
                $santizedItem = str_replace(' ', '_', strtolower($headerItem));
                if(in_array($santizedItem, $subscriberColumns)) {
                    $tableMap = $santizedItem;
                }
            }

            $maps[] = [
                'csv' => $headerItem,
                'table' => $tableMap
            ];
        }

        return $this->send([
            'file'    => $uploadedFiles[0]['file'],
            'headers' => $headerItems,
            'fields'  => $mappables,
            'columns' => $this->app->applyCustomFilters(
                'subscriber_table_columns', $subscriberColumns
            ),
            'map' => $maps
        ]);
    }

    public function import()
    {
        $inputs = $this->request->only([
            'map', 'tags', 'lists', 'file', 'update', 'new_status', 'double_optin_email'
        ]);


        $delimeter = $this->request->get('delimiter', 'comma');


        if ($delimeter == 'comma') {
            $delimeter = ',';
        } else {
            $delimeter = ';';
        }

        $status = $inputs['new_status'];

        try {
            $reader = Reader::createFromString(
                FileSysytem::get($inputs['file'])
            );
            $reader->setDelimiter($delimeter);

            $allRecords = $reader->fetchAssoc();
        } catch (\Exception $exception) {
            return $this->sendError([
                'message' => $exception->getMessage()
            ]);
        }

        $page = $this->request->get('importing_page', 1);
        $processPerRequest = 500;
        $offset = ($page - 1) * $processPerRequest;
        $records = array_slice($allRecords, $offset, $processPerRequest);

        $customFieldKeys = $this->customFieldKeys();
        $subscribers = [];
        $skipped = [];
        foreach ($records as $record) {
            if (!array_filter($record)) {
                continue;
            }

            $subscriber = [
                'custom_values' => []
            ];
            foreach ($inputs['map'] as $map) {
                if (!$map['table']) {
                    continue;
                }
                if (isset($map['csv'], $map['table'])) {
                    if (in_array($map['table'], $customFieldKeys)) {
                        $subscriber['custom_values'][$map['table']] = $record[$map['csv']];
                    } else {
                        $subscriber[$map['table']] = $record[$map['csv']];
                    }
                }
            }

            if (!array_key_exists('email', $subscriber)) {
                return $this->sendError(['email' => "The email field is required."], 422);
            }

            $subscriber['email'] = trim($subscriber['email']);

            if ($subscriber['email'] && is_email($subscriber['email'])) {
                $subscribers[] = $subscriber;
            } else {
                $skipped[] = $subscriber;
            }
        }

        if (!isset($inputs['tags'])) {
            $inputs['tags'] = [];
        }

        if (!isset($inputs['lists'])) {
            $inputs['lists'] = [];
        }

        $sendDoubleOptin = Arr::get($inputs, 'double_optin_email') == 'yes';

        $totalInput = count($subscribers);
        $result = Subscriber::import(
            $subscribers, $inputs['tags'], $inputs['lists'], $inputs['update'], $status, $sendDoubleOptin
        );

        $totalSkipped = count($result['skips']) + count($skipped);

        $completed = $offset + count($records);
        $totalCount = count($allRecords);
        $hasMore = $completed < $totalCount;
        if (!$hasMore) {
            FileSysytem::delete($inputs['file']);
        }

        return $this->sendSuccess([
            'total'                => $totalCount,
            'completed'            => $completed,
            'total_page'           => ceil($totalCount / $processPerRequest),
            'skipped'              => $totalSkipped,
            'invalid_contacts'     => $skipped,
            'skipped_contacts'     => $result['skips'],
            'invalid_email_counts' => count($skipped),
            'inserted'             => count($result['inserted']),
            'updated'              => count($result['updated']),
            'has_more'             => $hasMore,
            'last_page'            => $page,
            'tags'                 => $inputs['tags'],
            'lists'                => $inputs['lists']
        ]);
    }

    protected function customFieldKeys()
    {
        $fields = fluentcrm_get_option('contact_custom_fields', []);
        $keys = [];
        foreach ($fields as $field) {
            $keys[] = $field['slug'];
        }
        return $keys;
    }
}
