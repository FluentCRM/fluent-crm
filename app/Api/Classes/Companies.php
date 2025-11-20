<?php

namespace FluentCrm\App\Api\Classes;

defined('ABSPATH') || exit;

use FluentCrm\App\Models\Company;
use FluentCrm\App\Models\CustomCompanyField;
use FluentCrm\App\Models\Subscriber;
use FluentCrm\Framework\Support\Arr;

/**
 * Company Class - PHP APi Wrapper
 *
 * Company API Wrapper Class that can be used as <code>FluentCrmApi('companies')</code> to get the class instance
 *
 * @package FluentCrm\App\Api\Classes
 * @namespace FluentCrm\App\Api\Classes
 *
 * @version 2.8.0
 */
class Companies
{
    private $instance = null;

    private $allowedInstanceMethods = [
        'all',
        'get',
        'find',
        'first',
        'paginate'
    ];

    public function __construct(Company $instance)
    {
        $this->instance = $instance;
    }

    public function getCompany($idOrName, $with = [])
    {
        if (is_numeric($idOrName)) {
            return Company::where('id', $idOrName)->with($with)->first();
        }
        if (is_string($idOrName)) {
            return Company::where('email', $idOrName)->with($with)->first();
        }
        return false;
    }

    public function createOrUpdate($data)
    {
        $exist = null;

        if (!empty($data['id'])) {
            $exist = Company::where('id', $data['id'])->first();
        } else {
            $exist = Company::where('name', $data['name'])->first();
        }

        if ($exist) {
            if (!empty($data['owner_id']) && $data['owner_id'] != $exist->owner_id) {
                $contact = Subscriber::find($data['owner_id']);
                if ($contact) {
                    $contact->attachCompanies([$exist->id]);
                    if (empty($contact->company_id)) {
                        $contact->company_id = $exist->id;
                        $contact->save();
                    }
                }
            }

            if (isset($data['custom_values'])) {
                $existingMeta = $exist->meta;
                $values = Arr::get($data, 'custom_values', []);
                $values = (new CustomCompanyField())->formatCustomFieldValues($values);
                $temp = $existingMeta['custom_values'];
                foreach ($values as $key => $value)
                {
                    $temp[$key] = $value;
                }
                $existingMeta['custom_values'] = $temp;

                $exist->meta = $existingMeta;
                unset($data['custom_values']);
            }

            $exist->fill($data);
            $exist->save();
            do_action('fluent_crm/company_updated', $exist, $data);
            return $exist;
        } else if (empty($data['name'])) {
            return false;
        }

        $fillables = (new Company())->getFillable();
        $fillables[] = 'custom_values';
        $data = Arr::only($data, $fillables);
        $values = Arr::get($data, 'custom_values', []);
        $values = (new CustomCompanyField())->formatCustomFieldValues($values);

        $data['meta'] = [
            'custom_values' => $values
        ];

        $company = Company::create($data);

        do_action('fluent_crm/company_created', $company, $data);

        if ($company->owner_id) {
            $owner = Subscriber::find($company->owner_id);
            if ($owner) {
                $owner->attachCompanies([$company->id]);
                if (empty($owner->company_id)) {
                    $owner->company_id = $company->id;
                    $owner->save();
                }
            }
        }

        return $company;
    }

    public function attachContactsByIds($contactIds, $companyIds)
    {
        $companyIds = array_map('intval', $companyIds);
        $subscriberIds = array_map('intval', $contactIds);

        $subscribers = Subscriber::whereIn('id', $subscriberIds)->get();
        $companies = Company::whereIn('id', $companyIds)->get();

        if ((count($companyIds) != count($companies)) || $subscribers->isEmpty() || $companies->isEmpty()) {
            return false;
        }

        $firstCompanyId = $companyIds[0];

        $validIds = [];
        foreach ($companies as $company) {
            $validIds[] = $company->id;
        }

        foreach ($subscribers as $subscriber) {
            $subscriber->attachCompanies($validIds);
            if (!$subscriber->company_id) {
                $subscriber->company_id = $firstCompanyId;
                $subscriber->save();
            }
        }

        return [
            'companies'   => $companies,
            'subscribers' => $subscribers
        ];
    }

    public function detachContactsByIds($contactIds, $companyIds)
    {
        $companyIds = array_map('intval', $companyIds);
        $subscriberIds = array_map('intval', $contactIds);

        $subscribers = Subscriber::whereIn('id', $subscriberIds)->get();
        $companies = Company::whereIn('id', $companyIds)->get();

        if ((count($companyIds) != count($companies)) || $subscribers->isEmpty() || $companies->isEmpty()) {
            return false;
        }

        $validIds = [];
        foreach ($companies as $company) {

            if ($company->owner_id && in_array($company->owner_id, $subscriberIds)) {
                $company->owner_id = NULL;
                $company->save();
            }

            $validIds[] = $company->id;
        }

        $lastPrimaryId = false;

        foreach ($subscribers as $subscriber) {
            $subscriber = $subscriber->detachCompanies($validIds);
            if (in_array($subscriber->company_id, $validIds)) {
                $companies = $subscriber->companies;

                if (count($companies)) {
                    $lastPrimaryId = $companies[0]->id;
                    $subscriber->company_id = $lastPrimaryId;
                    $subscriber->save();
                    continue;
                }

                $subscriber->company_id = NULL;
                $subscriber->save();
            }
        }

        return [
            'companies'               => $companies,
            'last_primary_company_id' => $lastPrimaryId
        ];
    }
}
