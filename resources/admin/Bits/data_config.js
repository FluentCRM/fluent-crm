export function $t(string) {
    string = window.fcAdmin.trans[string] || string;

    // Prepare the arguments, excluding the first one (the string itself)
    const args = Array.prototype.slice.call(arguments, 1);

    if (args.length === 0) {
        return string;
    }

    // Regular expression to match %s, %d, or %1s, %2s, etc.
    const regex = /%(\d*)s|%d/g;

    // Replace function to handle each match found by the regex
    let argIndex = 0; // Keep track of the argument index for non-numbered placeholders
    string = string.replace(regex, (match, number) => {
        // If it's a numbered placeholder, use the number to find the corresponding argument
        if (number) {
            const index = parseInt(number, 10) - 1; // Convert to zero-based index
            return index < args.length ? args[index] : match; // Replace or keep the placeholder
        } else {
            // For non-numbered placeholders, use the next argument in the array
            return argIndex < args.length ? args[argIndex++] : match; // Replace or keep the placeholder
        }
    });

    return string;
}

export const subscriberColumns = [
    {
        label: $t('Contact Type'),
        value: 'contact_type',
        position: 1
    },
    {
        label: $t('Tags'),
        value: 'tags',
        position: 2
    },
    {
        label: $t('Prefix'),
        value: 'prefix',
        position: 2.5
    },
    {
        label: $t('First Name'),
        value: 'first_name',
        position: 3
    },
    {
        label: $t('Last Name'),
        value: 'last_name',
        position: 4
    },
    {
        label: $t('Lists'),
        value: 'lists',
        position: 5
    },
    {
        label: $t('Status'),
        value: 'status',
        position: 6
    },
    {
        label: $t('Source'),
        value: 'source',
        position: 7
    },
    {
        label: $t('Phone'),
        value: 'phone',
        position: 8
    },
    {
        label: $t('Date Of Birth'),
        value: 'date_of_birth',
        position: 9
    },
    {
        label: $t('Created At'),
        value: 'created_at',
        position: 10
    },
    {
        label: $t('Last Change Date'),
        value: 'updated_at',
        position: 11
    },
    {
        label: $t('Last Activity'),
        value: 'last_activity',
        position: 12
    },
    {
        label: $t('Address Line 1'),
        value: 'address_line_1',
        position: 13
    },
    {
        label: $t('Address Line 2'),
        value: 'address_line_2',
        position: 14
    },
    {
        label: $t('City'),
        value: 'city',
        position: 15
    },
    {
        label: $t('State'),
        value: 'state',
        position: 16
    },
    {
        label: $t('Zip Code'),
        value: 'postal_code',
        position: 17
    },
    {
        label: $t('Country'),
        value: 'country',
        position: 18
    }
];

export const companyColumns = [
    {
        label: $t('Industry'),
        value: 'industry',
        position: 1
    },
    {
        label: $t('Company Type'),
        value: 'type',
        position: 5
    },
    {
        label: $t('Company Owner'),
        value: 'owner_id',
        position: 3
    },
    {
        label: $t('Number of Employees'),
        value: 'employees_number',
        position: 4
    },
    {
        label: $t('Phone'),
        value: 'phone',
        position: 1
    },
    {
        label: $t('Website'),
        value: 'website',
        position: 2
    },
    {
        label: $t('Address'),
        value: 'address',
        position: 5
    },
    {
        label: $t('City'),
        value: 'city',
        position: 7
    },
    {
        label: $t('Country'),
        value: 'country',
        position: 8
    },
    {
        label: $t('Updated At'),
        value: 'updated_at',
        position: 11
    }
];

export const automationFunnelColumns = [
    {
        label: $t('Trigger'),
        value: 'trigger',
        position: 1
    },
    {
        label: $t('Labels'),
        value: 'labels',
        position: 2
    },
    {
        label: $t('Stats'),
        value: 'stats',
        position: 4
    },
    {
        label: $t('Pause/Run'),
        value: 'pause/run',
        position: 5
    },
    {
        label: $t('Action'),
        value: 'action',
        position: 6
    }
];

export const emailFontFamilies = {
    SystemUi: "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol'",
    Arial: "Arial, 'Helvetica Neue', Helvetica, sans-serif",
    'Comic Sans': "'Comic Sans MS', 'Marker Felt-Thin', Arial, sans-serif",
    'Courier New': "'Courier New', Courier, 'Lucida Sans Typewriter', 'Lucida Typewriter', monospace",
    Georgia: "Georgia, Times, 'Times New Roman', serif",
    Helvetica: 'Helvetica , Arial, Verdana, sans-serif',
    Lucida: "'Lucida Sans Unicode', 'Lucida Grande', sans-serif",
    Tahoma: 'Tahoma, Verdana, Segoe, sans-serif',
    'Times New Roman': "'Times New Roman', Times, Baskerville, Georgia, serif",
    'Trebuchet MS': "'Trebuchet MS', 'Lucida Grande', 'Lucida Sans Unicode', 'Lucida Sans', Tahoma, sans-serif",
    Verdana: 'Verdana, Geneva, sans-serif',
    Lato: "'Lato', 'Helvetica Neue', Helvetica, Arial, sans-serif",
    Lora: "'Lora', Georgia, 'Times New Roman', serif",
    Merriweather: "'Merriweather', Georgia, 'Times New Roman', serif",
    'Merriweather Sans': "'Merriweather Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif",
    'Noticia Text': "'Noticia Text', Georgia, 'Times New Roman', serif",
    'Open Sans': "'Open Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif",
    Roboto: "'Roboto', 'Helvetica Neue', Helvetica, Arial, sans-serif",
    'Source Sans Pro': "'Source Sans Pro', 'Helvetica Neue', Helvetica, Arial, sans-serif"
};

export const dateConfig = [
    {
        text: $t('Last week'),
        onClick(picker) {
            const end = new Date();
            const start = new Date();
            start.setTime(start.getTime() - 3600 * 1000 * 24 * 7);
            picker.$emit('pick', [start, end]);
        }
    },
    {
        text: $t('This Month'),
        onClick(picker) {
            const end = new Date();
            const start = new Date(end.getFullYear(), end.getMonth(), 1);
            picker.$emit('pick', [start, end]);
        }
    },
    {
        text: $t('Last month'),
        onClick(picker) {
            const end = new Date();
            const start = new Date();
            start.setTime(start.getTime() - 3600 * 1000 * 24 * 30);
            picker.$emit('pick', [start, end]);
        }
    },
    {
        text: $t('Last 3 months'),
        onClick(picker) {
            const end = new Date();
            const start = new Date();
            start.setTime(start.getTime() - 3600 * 1000 * 24 * 90);
            picker.$emit('pick', [start, end]);
        }
    },
    {
        text: $t('This quarter'),
        onClick(picker) {
            const today = new Date();
            const quarter = Math.floor((today.getMonth() / 3));
            const start = new Date(today.getFullYear(), quarter * 3, 1);
            picker.$emit('pick', [start, today]);
        }
    },
    {
        text: $t('Last quarter'),
        onClick(picker) {
            const today = new Date();
            const quarter = Math.floor((today.getMonth() / 3));
            const start = new Date(today.getFullYear(), quarter * 3 - 3, 1);
            const end = new Date(start.getFullYear(), start.getMonth() + 3, 0);
            picker.$emit('pick', [start, end]);
        }
    },
    {
        text: $t('Year to Date'),
        onClick(picker) {
            const end = new Date();
            const start = new Date(new Date().getFullYear(), 0, 1);
            picker.$emit('pick', [start, end]);
        }
    }
];

export const emailDateConfig = {
    disabledDate(date) {
        return date.getTime() <= (Date.now() - 3600 * 1000 * 24);
    },
    shortcuts: [{
        text: $t('After 1 Hour'),
        onClick(picker) {
            const date = new Date();
            date.setTime(date.getTime() + 3600 * 1000 * 1);
            picker.$emit('pick', date);
        }
    }, {
        text: $t('Tomorrow'),
        onClick(picker) {
            const date = new Date();
            date.setTime(date.getTime() + 3600 * 1000 * 24 * 1);
            picker.$emit('pick', date);
        }
    }, {
        text: $t('After 2 Days'),
        onClick(picker) {
            const date = new Date();
            date.setTime(date.getTime() + 3600 * 1000 * 24 * 2);
            picker.$emit('pick', date);
        }
    }, {
        text: $t('After 1 Week'),
        onClick(picker) {
            const date = new Date();
            date.setTime(date.getTime() + 3600 * 1000 * 24 * 7);
            picker.$emit('pick', date);
        }
    }]
}

export const emailDateRangeConfig = {
    disabledDate(date) {
        return date.getTime() <= (Date.now() - 3600 * 1000 * 24);
    }
}

export const getDomainName = function(url) {
    if (!url) {
        return '';
    }

    let domain = url;
    if (domain.indexOf('://') > -1) {
        domain = domain.split('/')[2];
    } else {
        domain = domain.split('/')[0];
    }
    domain = domain.split(':')[0];
    return domain;
}

export const getFormattedAddress = function(row) {
    return [
        row.address_line_1,
        row.address_line_2,
        row.city,
        row.state,
        row.postal_code,
        row.country
    ].filter(Boolean).join(', ');
}
