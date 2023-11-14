<?php

(new \FluentCrm\App\Services\ExternalIntegrations\Oxygen\ConditionBuilder())->init();

function fcrmOxyCheckTagCondition($value, $operator)
{
    $contactApi = FluentCrmApi('contacts');
    $contact = $contactApi->getCurrentContact(true, true);

    if(!$contact) {
        if($operator == 'exist') {
            return false;
        } else {
            return true;
        }
    }

    $contactTags = $contact->tags;

    if($operator == 'exist') {
        foreach ($contactTags as $tag) {
            if($tag->slug == $value) {
                return true;
            }
        }
        return false;
    }

    foreach ($contactTags as $tag) {
        if($tag->slug == $value) {
            return false;
        }
    }

    return true;
}

function fcrmOxyCheckStatusCondition($value, $operator)
{
    $contactApi = FluentCrmApi('contacts');
    $contact = $contactApi->getCurrentContact(true, true);

    if(!$contact) {
        if($operator == '=') {
            return false;
        } else {
            return true;
        }
    }

    if($operator == '=') {
        return $contact->status == $value;
    }

    return $contact->status != $value;

}

function fcrmOxyCheckContactExistCondition($value, $operator)
{
    $contactApi = FluentCrmApi('contacts');
    $contact = $contactApi->getCurrentContact(true, true);

    if($value == 'yes') {
        return !!$contact;
    }

    return !$contact;
}
