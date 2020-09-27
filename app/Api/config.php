<?php

// Register the classes to make available for the developers
// The key will be used to access the class, for example:
// FluentCrmApi('contacts') or FluentCrmApi->contacts

return [
    'contacts' => 'FluentCrm\App\Api\Classes\Contacts',
    'tags' => 'FluentCrm\App\Api\Classes\Tags',
    'lists' => 'FluentCrm\App\Api\Classes\Lists',
];
