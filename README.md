# FluentCRM
** Marketing Automation Plugin**
### Core Features

* Contacts & Companies
* Automations
* Abandoned Cart
* Reports
* Email Campaigns
* Dozens of Integrations

### Build Files
- WordPress Plugin Repository: [fluent-crm.zip (https://wordpress.org/plugins/fluent-crm/)](https://wordpress.org/plugins/fluent-crm/)

#### Releases on Github
- For build files then go to
  [Releases (Free)](https://github.com/WPManageNinja/fluent-crm/releases) and download the version
  as per your need.
- For Pro Version,  [Releases (Pro)](https://github.com/WPManageNinja/fluentcampaign-pro/releases) . Download
  Source code (zip) from Assets Section

### Development

- Clone this repository in the same WP installations
- `npm install`

Then for running dev
- Main App: `npm install && npx mix watch`
- Build Main App: `npx mix --production`

### Production Build
- Run  `bash build.sh`
- For Pro Version: `bash build.sh --with-pro`
- It will create a zip files in the `/builds` folder

### Tech

- PHP 7.3+
- Vue.js 2+
- Element
- action-scheduler (for queuing email)
- wp-scheduler
- Webhooks
- CSV Library
- Emogrifier
- Shortcode Parser
- Vue (Frontend) action/filters
