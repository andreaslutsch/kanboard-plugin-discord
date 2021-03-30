<?php

namespace Kanboard\Plugin\Discord;

use Kanboard\Core\Translator;
use Kanboard\Core\Plugin\Base;

/**
 * Discord Plugin
 *
 * @package  slack
 * @author   Frederic Guillot
 */
class Plugin extends Base
{
    public function initialize()
    {
        $this->template->hook->attach('template:config:integrations', 'discord:config/integration');
        $this->template->hook->attach('template:project:integrations', 'discord:project/integration');
        $this->template->hook->attach('template:user:integrations', 'discord:user/integration');

        $this->userNotificationTypeModel->setType('discord', t('Discord'), '\Kanboard\Plugin\Discord\Notification\Discord');
        $this->projectNotificationTypeModel->setType('discord', t('Discord'), '\Kanboard\Plugin\Discord\Notification\Discord');
    }

    public function onStartup()
    {
        Translator::load($this->languageModel->getCurrentLanguage(), __DIR__.'/Locale');
    }

    public function getPluginDescription()
    {
        return 'Receive notifications on Discord';
    }

    public function getPluginAuthor()
    {
        return 'Frédéric Guillot';
    }

    public function getPluginVersion()
    {
        return '1.0.7';
    }

    public function getPluginHomepage()
    {
        return 'https://github.com/andreaslutsch/kanboard-plugin-discord';
    }

    public function getCompatibleVersion()
    {
        return '>=1.0.37';
    }
}
