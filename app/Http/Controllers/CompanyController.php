<?php

namespace FluentCrm\App\Http\Controllers;

use FluentCrm\App\Http\Controllers\Controller;
use FluentCrm\App\Models\Company;
use FluentCrm\App\Models\CompanyNote;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\App\Models\SubscriberNote;
use FluentCrm\App\Services\Helper;
use FluentCrm\App\Services\Libs\FileSystem;
use FluentCrm\App\Services\Sanitize;
use FluentCrm\Framework\Request\Request;
use FluentCrm\Framework\Support\Arr;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $order = [
            'by' => $request->getSafe('sort_by', 'id', 'sanitize_sql_orderby'),
            'order' => $request->getSafe('sort_order', 'DESC', 'sanitize_sql_orderby')
        ];

        $companies = Company::orderBy($order['by'], $order['order'])
            ->with(['owner'])
            ->searchBy($request->getSafe('search'))
            ->paginate();

        foreach ($companies as $company) {
            $company->contacts_count = $company->getContactsCount();
        }

        return [
            'companies' => $companies
        ];
    }

    public function searchCompanies(Request $request)
    {
        $search = $request->getSafe('search');
        $companies = Company::orderBy('name', 'ASC')
            ->searchBy($search);

        $subscriberId = $request->getSafe('subscriber_id', 'intval');

        if ($subscriberId) {
            $companies = $companies->doesnthave('subscribers', 'and', function ($query) use ($subscriberId) {
                $query->where('fc_subscribers.id', $subscriberId);
            });
        }

        $companies = $companies->limit(50)
            ->get();

        $formatted = [];

        $values = (array)$request->getSafe('values', []);

        $pushedIds = [];

        foreach ($companies as $company) {
            $pushedIds[] = $company->id;
            $formatted[] = [
                'id'      => $company->id,
                'name'    => $company->name,
                'email'   => $company->email,
                'logo'    => $company->logo,
                'phone'   => $company->phone,
                'website' => $company->website
            ];
        }

        if ($values && $newIds = array_diff($values, $pushedIds)) {
            $newItems = Company::whereIn('id', $newIds)
                ->get();
            foreach ($newItems as $item) {
                $formatted[] = [
                    'id'      => $item->id,
                    'name'    => $item->name,
                    'email'   => $item->email,
                    'logo'    => $item->logo,
                    'phone'   => $item->phone,
                    'website' => $item->website
                ];
            }
        }

        return [
            'results'  => $formatted,
            'has_more' => Company::count() >= 50
        ];
    }

    public function searchUnattachedContacts(Request $request)
    {
        $search = $request->getSafe('search');
        $companyId = $request->getSafe('company_id', '', 'intval');

        $contacts = Subscriber::orderBy('id', 'DESC')
            ->searchBy($search)
            ->whereDoesntHave('companies', function ($query) use ($companyId) {
                $query->where('fc_companies.id', $companyId);
            })
            ->limit($request->getSafe('limit', 20, 'intval'))
            ->get();

        return [
            'results' => $contacts
        ];
    }

    public function attachSubscribers(Request $request)
    {
        $subscriberIds = $request->get('subscriber_ids');
        $companyIds = $request->get('company_ids');

        $result = FluentCrmApi('companies')->attachContactsByIds($subscriberIds, $companyIds);

        if(!$result) {
            return $this->sendError('Invalid data', 423);
        }

        return [
            'message' => __('Selected Companies has been attached successfully', 'fluent-crm'),
            'companies' => $result['companies']
        ];
    }

    public function detachSubscribers(Request $request)
    {
        $subscriberIds = $request->get('subscriber_ids');
        $companyIds = $request->get('company_ids');

        $result = FluentCrmApi('companies')->detachContactsByIds($subscriberIds, $companyIds);

        if(!$result) {
            return $this->sendError('Invalid data', 423);
        }
        $result['message'] =  __('Company has been successfully detached', 'fluent-crm');

        return $result;
    }

    /**
     * Find a company.
     */
    public function find(Request $request, $id)
    {

        $findBy = $request->getSafe('find_by', 'id');
        $findByValue = $request->getSafe('find_by_value');


        $customFindBys = ['name', 'email', 'phone'];

        if (in_array($findBy, $customFindBys)) {
            $company = Company::where($findBy, $findByValue)->find();
            if (!$company) {
                return $this->sendError('Company not found', 423);
            }
        } else {
            $company = Company::findOrFail($id);
        }


        $company->load(['owner']);
        if ($company->owner) {
            $company->owner->stats = $company->owner->stats();
        }


        $company->contacts_count = $company->getContactsCount();

        return [
            'company' => $company
        ];
    }

    /**
     * Store a company.
     * @param \FluentCrm\Framework\Request\Request $request
     * @return \WP_REST_Response | array
     */
    public function create(Request $request)
    {
        $allData = $request->all();

        $allData = $this->validate($allData, [
            'name' => 'required|unique:fc_companies,name'
        ]);

        $data = $this->getSanitizedData($allData);

        if (empty($data['logo']) && !empty($allData['website']) && Helper::isExperimentalEnabled('company_auto_logo')) {
            $data['logo'] = $this->getLogoWebsiteUrl($allData['website']);
        }

        $company = FluentCrmApi('companies')->createOrUpdate($data);

        if ($contactId = $request->get('intended_contact_id')) {
            $contact = Subscriber::find($contactId);
            if($contact) {
                $contact->attachCompanies([$company->id]);
                if (!$contact->company_id) {
                    $contact->company_id = $company->id;
                    $contact->save();
                }
            }
        }

        return [
            'message' => __('Company has been created successfully', 'fluent-crm'),
            'company' => $company
        ];
    }

    public function update(Request $request, $id = 0)
    {
        if ($id == 0) {
            return $this->create($request);
        }

        $company = Company::findOrFail($id);

        $allData = $request->all();

        $name = sanitize_text_field($allData['name']);

        if (Company::where('id', '!=', $id)->where('name', $name)->first()) {
            return $this->sendError([
                'message' => 'Company name already exists. Please use a different company name'
            ], 423);
        }

        $data = $this->getSanitizedData($allData);


        $company = FluentCrmApi('companies')->createOrUpdate($data);

        return [
            'message' => __('Company has been updated', 'fluent-crm'),
            'company' => $company
        ];

    }

    public function updateProperty()
    {
        $column = $this->request->getSafe('property');
        $value = $this->request->getSafe('value');
        $companyIds = $this->request->getSafe('companies', [], 'intval');

        $validColumns = ['type', 'logo', 'owner_id', 'refetch_logo'];
        $types = Helper::companyTypes();
        $statuses = Helper::companyTypes();

        $this->validate([
            'column' => $column,
            'value' => $value,
            'company_ids' => $companyIds
        ], [
            'column' => 'required',
            'value' => 'required',
            'company_ids' => 'required'
        ]);

        if (!in_array($column, $validColumns)) {
            return $this->sendError([
                'message' => __('Column is not valid', 'fluent-crm')
            ]);
        }

        if ($column == 'type' && !in_array($value, $types)) {
            return $this->sendError([
                'message' => __('Value is not valid', 'fluent-crm')
            ]);
        } else if ($column == 'status' && !in_array($value, $statuses)) {
            return $this->sendError([
                'message' => __('Value is not valid', 'fluent-crm')
            ]);
        }

        $companies = Company::whereIn('id', $companyIds)->get();

        foreach ($companies as $company) {

            if($column == 'refetch_logo') {
                $newLogo = $this->getLogoWebsiteUrl($company->website);
                if($newLogo) {
                    $company->logo = $newLogo;
                    $company->save();
                    return [
                        'message' => 'Logo has been updated successfully',
                        'updated_logo' => $newLogo
                    ];
                }

                return $this->sendError([
                    'message' => __('Sorry, we could not find the logo from website. Please upload manually', 'fluent-crm')
                ]);
            }

            $oldValue = $company->{$column};
            if ($oldValue != $value) {
                $company->{$column} = $value;
                $company->save();
                if (in_array($column, ['type', 'status', 'owner_id'])) {
                    do_action('fluent_crm/company_' . $column . '_to_' . $value, $company, $oldValue);
                }
            }
        }

        return $this->sendSuccess([
            'message' => __('Company successfully updated', 'fluent-crm')
        ]);
    }

    public function delete(Request $request, $id)
    {
        $company = Company::findOrFail($id);
        do_action('fluent_crm/before_company_delete', $company);
        $company->delete();
        do_action('fluent_crm/company_deleted', $id);

        return [
            'message' => __('Company has been deleted successfully', 'fluent-crm')
        ];
    }

    public function handleBulkActions(Request $request)
    {
        $actionName = sanitize_text_field($request->get('action_name', ''));

        $companyIds = array_map('intval', $request->get('company_ids', []));
        $companyIds = array_filter($companyIds);

        if (!$companyIds) {
            return $this->sendError([
                'message' => __('Companies selection is required', 'fluent-crm')
            ]);
        }

        if ($actionName == 'delete_companies') {

            $companies = Company::whereIn('id', $companyIds)->get();

            foreach ($companies as $company) {
                $id = $company->id;
                do_action('fluent_crm/before_company_delete', $company);
                $company->delete();
                do_action('fluent_crm/company_deleted', $id);
            }

            return $this->sendSuccess([
                'message' => __('Selected Companies has been deleted permanently', 'fluent-crm'),
            ]);
        } elseif ($actionName == 'change_company_status') {
            $newStatus = sanitize_text_field($request->get('new_status', ''));
            if (!$newStatus) {
                return $this->sendError([
                    'message' => __('Please select status', 'fluent-crm')
                ]);
            }

            $companies = Company::whereIn('id', $companyIds)->get();

            foreach ($companies as $company) {
                $oldStatus = $company->status;
                if ($oldStatus != $newStatus) {
                    $company->status = $newStatus;
                    $company->save();
                    do_action('fluent_crm/company_status_to_' . $newStatus, $company, $oldStatus);
                }
            }

            return [
                'message' => __('Status has been changed for the selected companies', 'fluent-crm')
            ];
        } else if ($actionName == 'change_company_type') {
            $companies = Company::whereIn('id', $companyIds)->get();
            $newType = sanitize_text_field($request->get('new_status', ''));
            if (!$newType) {
                return $this->sendError([
                    'message' => 'Please select new type'
                ]);
            }
            foreach ($companies as $company) {
                $oldType = $company->type;
                if ($oldType != $newType) {
                    $company->type = $newType;
                    $company->save();
                    do_action('fluent_crm/company_type_to_' . $newType, $company, $oldType);
                }
            }

            return [
                'message' => __('Company Type has been updated for the selected companies', 'fluent-crm')
            ];
        } else if ($actionName == 'change_company_category') {
            $companies = Company::whereIn('id', $companyIds)->get();
            $newCategory = sanitize_text_field($request->get('new_status', ''));
            if (!$newCategory) {
                return $this->sendError([
                    'message' => 'Please select new category'
                ]);
            }
            foreach ($companies as $company) {
                $oldCategory = $company->industry;
                if ($oldCategory != $newCategory) {
                    $company->industry = $newCategory;
                    $company->save();
                    do_action('fluent_crm/company_category_to_' . $newCategory, $company, $oldCategory);
                }
            }

            return [
                'message' => __('Company Category has been updated for the selected companies', 'fluent-crm')
            ];
        }

        return [
            'message' => __('Selected bulk action has been successfully completed', 'fluent-crm')
        ];
    }

    private function getSanitizedData($allData)
    {
        $rules = [
            'name' => 'required'
        ];

        if (Arr::get($allData, 'website')) {
            $allData['website'] = $this->makeHttpUrl($allData['website']);
            $rules['website'] = 'url';
        }

        if (Arr::get($allData, 'linkedin_url')) {
            $allData['linkedin_url'] = $this->makeHttpUrl($allData['linkedin_url']);
            $rules['linkedin_url'] = 'url';
        }

        if (Arr::get($allData, 'facebook_url')) {
            $allData['facebook_url'] = $this->makeHttpUrl($allData['facebook_url']);
            $rules['facebook_url'] = 'url';
        }

        if (Arr::get($allData, 'twitter_url')) {
            $allData['twitter_url'] = $this->makeHttpUrl($allData['twitter_url']);
            $rules['twitter_url'] = 'url';
        }

        $allData = $this->validate($allData, $rules);

        $data = Sanitize::company($allData);

        return Arr::only($data, array_keys($allData));
    }

    private function makeHttpUrl($url) {
        if(!$url) {
            return $url;
        }
        $parsed_url = parse_url($url);
        if (!$parsed_url || empty($parsed_url['scheme'])) {
            $url = 'https://' . $url;
        }

        return $url;
    }

    private function getLogoWebsiteUrl($url)
    {
        if(!$url) {
            return NULL;
        }

        $url = $this->makeHttpUrl($url);

        $response = wp_remote_get($url, [
            'timeout' => 10, // Set a timeout of 10 seconds
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3' // Set a User-Agent header to avoid 403 Forbidden error
        ]);

        // Check for errors in the response
        if (is_wp_error($response)) {
            return NULL;
        }

        // Extract the HTML content from the response
        $html = wp_remote_retrieve_body($response);

        preg_match('/<link rel="apple-touch-icon"(?:.*?)href="([^"]+)"/i', $html, $matches);

        // Use regular expressions to find the logo image URL
        if (!isset($matches[1])) {
            preg_match('/<link rel="(?:shortcut|icon)"(?:.*?)href="([^"]+)"/i', $html, $matches);
        }

        // If a logo URL is found, download the image to the uploads directory
        if (isset($matches[1])) {
            $logoUrl = $matches[1];
            $uploadDir = wp_upload_dir(); // Get the uploads directory

            $filename = md5($url . time()) . '-' . basename($logoUrl); // Get the filename from the URL
            $filepath = $uploadDir['basedir'] . '/fluentcrm/' . $filename; // Combine the uploads directory path with the filename

            // Download the image using wp_remote_get() and save it to the uploads directory
            $image = wp_remote_get($logoUrl);
            if (!is_wp_error($image)) {
                // Check if the downloaded file is actually an image
                $headers = wp_remote_retrieve_headers($image);
                $content_type = wp_remote_retrieve_header($headers, 'content-type');

                if (!$content_type) {
                    $content_type = $headers['content-type'];
                }

                if (strpos($content_type, 'image/') === 0) {
                    global $wp_filesystem;

                    if(!$wp_filesystem) {
                        require_once ( ABSPATH . '/wp-admin/includes/file.php' );
                        WP_Filesystem();
                    }

                    FileSystem::setCustomUploadDir([
                        'baseurl' => $uploadDir['baseurl'],
                        'basedir' => $uploadDir['basedir'],
                    ]);

                    $wp_filesystem->put_contents($filepath, wp_remote_retrieve_body($image));
                    // Return the URL of the saved image
                    return $uploadDir['baseurl'] .FLUENTCRM_UPLOAD_DIR.'/' . $filename;
                } else {
                    // If the downloaded file is not an image, delete the file and return null
                    @unlink($filepath);
                }
            }
        }

        // If no logo URL is found, or if an error occurs, or if the downloaded file is not an image, return null
        return NULL;
    }

    public function getNotes()
    {
        $companyId = $this->request->get('id');
        $search = $this->request->get('search');

        $notes = CompanyNote::where('subscriber_id', $companyId);

        if (!empty($search)) {
            $notes = $notes->where('title', 'LIKE', '%' . $search . '%');
        }

        $notes = $notes->orderBy('id', 'DESC')
            ->paginate();

        foreach ($notes as $note) {
            $note->added_by = $note->createdBy();
        }

        return $this->sendSuccess([
            'notes' => $notes
        ]);
    }

    public function addNote(Request $request, $id)
    {
        $company = Company::findOrFail($id);
        $note = $this->validate($request->get('note'), [
            'title'       => 'required',
            'description' => 'required',
            'type'        => 'required',
            'created_at'  => 'sometimes|datetime'
        ]);

        if (empty($note['created_at'])) {
            $note['created_at'] = current_time('mysql');
        }

        $note['subscriber_id'] = $id;

        $note = Sanitize::contactNote($note);

        $subscriberNote = CompanyNote::create(wp_unslash($note));

        /**
         * Subscriber's Note Added
         *
         * @param SubscriberNote $subscriberNote Note Model.
         * @param Subscriber $subscriber Contact Model.
         * @param array $note Contact Note Data Array.
         * @since 1.0
         */
        do_action('fluent_crm/company_note_added', $subscriberNote, $company, $note);

        return $this->sendSuccess([
            'note'    => $subscriberNote,
            'message' => __('Note has been successfully added', 'fluent-crm')
        ]);
    }

    public function updateNote(Request $request, $id, $noteId)
    {
        $company = Company::findOrFail($id);

        $note = $this->validate($request->get('note'), [
            'title'       => 'required',
            'description' => 'required',
            'type'        => 'required',
            'created_at'  => 'sometimes|datetime'
        ]);

        $note = Arr::only(wp_unslash($note), ['title', 'description', 'type', 'created_at']);

        if (empty($note['created_at'])) {
            unset($note['created_at']);
        }

        $note = Sanitize::contactNote($note);

        $companyNote = CompanyNote::findOrFail($noteId);
        $companyNote->fill($note);
        $companyNote->save();

        /**
         * Subscriber's Note Updated
         *
         * @param CompanyNote $companyNote Note Model.
         * @param Company $company Contact Model.
         * @param array $note Contact Note Data Array.
         * @since 1.0
         */
        do_action('fluent_crm/company_note_updated', $companyNote, $company, $note);

        return $this->sendSuccess([
            'note'    => $companyNote,
            'message' => __('Note successfully updated', 'fluent-crm')
        ]);
    }

    public function deleteNote($id, $noteId)
    {
        $company = Company::findOrFail($id);
        CompanyNote::where('id', $noteId)->delete();

        /**
         * Subscriber's Note Delete
         *
         * @param int $noteId Note ID.
         * @param Company $company Company Model.
         * @since 1.0
         */
        do_action('fluent_crm/company_note_deleted', $noteId, $company);

        return $this->sendSuccess([
            'message' => __('Note successfully deleted', 'fluent-crm')
        ]);
    }
}
