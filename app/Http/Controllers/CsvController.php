<?php

namespace FluentCrm\App\Http\Controllers;

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
        $files = $this->validate($this->request->files(), [
            'file' => 'mimetypes:' . implode(',', fluentcrmCsvMimes())
        ], ['file.mimetypes' => 'The file must be a valid CSV.']);

        $delimeter = $request->get('delimiter', 'comma');


        if($delimeter == 'comma') {
            $delimeter = ',';
        } else {
            $delimeter = ';';
        }

        $uploadedFiles = FileSysytem::put($files);

        $csv = Reader::createFromString(
            FileSysytem::get($uploadedFiles[0]['file'])
        );
        $csv->setDelimiter($delimeter);
        $headers = $csv->fetchOne();

        return $this->send([
            'file'    => $uploadedFiles[0]['file'],
            'headers' => array_filter($headers),
            'fields'  => Subscriber::mappables(),
            'columns' => $this->app->applyCustomFilters(
                'subscriber_table_columns', Subscriber::getColumns()
            )
        ]);
    }

    public function import()
    {
        $inputs = $this->request->only([
            'map', 'tags', 'lists', 'file', 'update', 'new_status'
        ]);

        $delimeter = $this->request->get('delimiter', 'comma');


        if($delimeter == 'comma') {
            $delimeter = ',';
        } else {
            $delimeter = ';';
        }

        $status = $inputs['new_status'];

        $reader =  Reader::createFromString(
            FileSysytem::get($inputs['file'])
        );
        $reader->setDelimiter($delimeter);

        $allRecords = $reader->fetchAssoc();

        $page = $this->request->get('importing_page', 1);
        $processPerRequest = 500;
        $offset = ($page - 1) * $processPerRequest;
        $records = array_slice($allRecords, $offset, $processPerRequest);


        $customFieldKeys = $this->customFieldKeys();
        $subscribers = [];
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

            if (is_email($subscriber['email'])) {
                $subscribers[] = $subscriber;
            }
        }

        $result = Subscriber::import(
            $subscribers, $inputs['tags'], $inputs['lists'], $inputs['update'], $status
        );

        $completed = $offset + count($records);
        $totalCount = count($allRecords);
        $hasMore =  $completed < $totalCount;
        if(!$hasMore) {
            FileSysytem::delete($inputs['file']);
        }

        return $this->sendSuccess([
            'total' => $totalCount,
            'completed' => $completed,
            'total_page' => ceil($totalCount / $processPerRequest),
            'has_more' => $hasMore,
            'last_page' => $page
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
