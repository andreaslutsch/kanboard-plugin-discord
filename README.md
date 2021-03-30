Discord plugin for Kanboard
=========================

Receive Kanboard notifications on Discord.

Author
------

- Frederic Guillot
- Revolware
- License MIT

Requirements
------------

- Kanboard >= 1.0.37

Installation
------------

You have the choice between 3 methods:

1. Install the plugin from the Kanboard plugin manager in one click
2. Download the zip file and decompress everything under the directory `plugins/Discord`
3. Clone this repository into the folder `plugins/Discord` and pull submodules.

Note: Plugin folder is case-sensitive.

Configuration
-------------

Firstly, you have to generate a new webhook url in Discord (**Server configuration > Integrations > New Webhook**) [wiki](https://support.discord.com/hc/en-us/articles/228383668-Intro-to-Webhooks).

### Receive project notifications to a channel

- Go to the project settings then choose **Integrations > Discord**
- Copy and paste the webhook url from Discord
- Enable Discord in your project notifications **Notifications > Discord**

## Troubleshooting

- Enable the debug mode
- All connection errors with the Discord API are recorded in the log files `data/debug.log` or syslog
