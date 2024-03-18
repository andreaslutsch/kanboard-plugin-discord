<?php

namespace Kanboard\Plugin\Discord\Notification;

use DiscordSDK;
use Kanboard\Core\Base;
use Kanboard\Core\Notification\NotificationInterface;
use Kanboard\Model\TaskModel;
use Kanboard\Model\SubtaskModel;
use Kanboard\Model\CommentModel;
use Kanboard\Model\TaskFileModel;
use ReflectionClass;
use ReflectionException;

require_once dirname(__FILE__) . '/../php-discord-sdk/support/sdk_discord.php';

// Helper functions

function clean($string)
{
    $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
    return preg_replace('/[^A-Za-z0-9\-.]/', '', $string); // Removes special chars.
}

// Overloaded classes 
/**
 * Discord Notification
 *
 * @package  notification
 * @author   Andrej Zlámala
 * @author   Andreas
 */

class Discord extends Base implements NotificationInterface
{

    /**
     * @param $projectId
     * @return array
     */
    private function getProjectEventValues($projectId)
    {
        $constants = array();
        try {
            $reflection = new ReflectionClass(TaskModel::class);
            $constants = array_values($reflection->getConstants());
            $reflection = new ReflectionClass(SubtaskModel::class);
            $constants = array_merge($constants, array_values($reflection->getConstants()));
            $reflection = new ReflectionClass(CommentModel::class);
            $constants = array_merge($constants, array_values($reflection->getConstants()));
            $reflection = new ReflectionClass(TaskFileModel::class);
            $constants = array_merge($constants, array_values($reflection->getConstants()));
            $constants = array_filter($constants, 'is_string');
        } catch (ReflectionException $exception) {
            return array();
        } finally {
            $events = array();
        }

        foreach ($constants as $key => $value) {
            $id = str_replace(".", "_", $value);

            $event_value = $this->projectMetadataModel->get($projectId, "Discord_" . $id, $this->configModel->get("Discord_" . $id));

            if ($event_value == 1) {
                array_push($events, $value);
            }
        }

        return $events;
    }

    /**
     * @param $userId
     * @return array
     */
    private function getUserEventValues($userId)
    {
        $constants = array();
        try {
            $reflection = new ReflectionClass(TaskModel::class);
            $constants = $reflection->getConstants();
        } catch (ReflectionException $exception) {
            return array();
        } finally {
            $events = array();
        }

        foreach ($constants as $key => $value) {
            if (strpos($key, 'EVENT') !== false) {
                $id = str_replace(".", "_", $value);

                $event_value = $this->userMetadataModel->get($userId, $id, $this->configModel->get($id));
                if ($event_value == 1) {
                    array_push($events, $value);
                }
            }
        }

        return $events;
    }

    /**
     * Send notification to a user
     *
     * @access public
     * @param  array     $user
     * @param  string    $eventName
     * @param  array     $eventData
     */
    public function notifyUser(array $user, $eventName, array $eventData)
    {
        $webhook = $this->userMetadataModel->get($user['id'], 'discord_webhook_url', $this->configModel->get('discord_webhook_url'));

        if (!empty($webhook)) {
            $events = $this->getUserEventValues($user['id']);

            foreach ($events as $event) {
                if ($eventName == $event) {
                    if ($eventName === TaskModel::EVENT_OVERDUE) {
                        foreach ($eventData['tasks'] as $task) {
                            $project = $this->projectModel->getById($task['project_id']);
                            $eventData['task'] = $task;
                            $this->sendMessage($webhook, $project, $eventName, $eventData);
                        }
                    } else {
                        $project = $this->projectModel->getById($eventData['task']['project_id']);
                        $this->sendMessage($webhook, $project, $eventName, $eventData);
                    }
                }
            }
        }
    }

    /**
     * Send notification to a project
     *
     * @access public
     * @param  array     $project
     * @param  string    $eventName
     * @param  array     $eventData
     */
    public function notifyProject(array $project, $eventName, array $eventData)
    {
        $webhook = $this->projectMetadataModel->get($project['id'], 'discord_webhook_url', $this->configModel->get('discord_webhook_url'));

        if (!empty($webhook)) {
            $events = $this->getProjectEventValues($project['id']);
            foreach ($events as $event) {
                if ($eventName == $event) {
                    $this->sendMessage($webhook, $project, $eventName, $eventData);
                }
            }
        }
    }

    /**
     * Get message to send
     *
     * @access public
     * @param  array     $project
     * @param  string    $eventName
     * @param  array     $eventData
     * @return array
     */
    public function getMessage(array $project, $eventName, array $eventData)
    {
        $fileInfo = array();
        $avatar_fileType = '';
        $file_type = '';

        // Get user information if logged in
        if ($this->userSession->isLogged()) {
            $user = $this->userSession->getAll();
            $author = $this->helper->user->getFullname();
            $title = $this->notificationModel->getTitleWithAuthor($author, $eventName, $eventData);
            if (!empty($user['avatar_path'])) {
                $avatar_path = getcwd() . '/data/files/' . $user['avatar_path'];

                $avatar_mime = mime_content_type($avatar_path);
                $avatar_fileType = substr($avatar_mime, strpos($avatar_mime, "/") + 1);

                $avatar_file = array(
                    "name" => "file",
                    "filename" => "avatar.{$avatar_fileType}",
                    "type" => $avatar_mime,
                    "data" => file_get_contents($avatar_path),
                );
                $fileInfo["avatar"] = $avatar_file;
            }
        } else {
            $title = $this->notificationModel->getTitleWithoutAuthor($eventName, $eventData);
        }

        $task_name = '**' . $eventData['task']['title'] . '**';
        $task_url = $this->helper->url->to('TaskViewController', 'show', array('task_id' => $eventData['task']['id'], 'project_id' => $project['id']), '', true);

        $title = "📝" . $title;

        $task_name = str_replace(
            $task_name,
            '[' . $task_name . '](' . $task_url . ')',
            $task_name
        );

        $message = $task_name . "\n " . $title;

        $description_events = array(TaskModel::EVENT_CREATE, TaskModel::EVENT_UPDATE, TaskModel::EVENT_USER_MENTION);
        $subtask_events = array(SubtaskModel::EVENT_CREATE, SubtaskModel::EVENT_UPDATE, SubtaskModel::EVENT_DELETE);
        $comment_events = array(CommentModel::EVENT_UPDATE, CommentModel::EVENT_CREATE, CommentModel::EVENT_DELETE, CommentModel::EVENT_USER_MENTION);

        if (in_array($eventName, $subtask_events))  // For subtask events
        {
            $subtask_status = $eventData['subtask']['status'];
            $subtask_symbol = '';

            if ($subtask_status == SubtaskModel::STATUS_DONE) {
                $subtask_symbol = '❌ ';
            } elseif ($subtask_status == SubtaskModel::STATUS_TODO) {
                $subtask_symbol = '';
            } elseif ($subtask_status == SubtaskModel::STATUS_INPROGRESS) {
                $subtask_symbol = '🕘 ';
            }

            $message .= "\n  ↳ " . $subtask_symbol . $eventData['subtask']['title'];
        } elseif (in_array($eventName, $description_events))  // If description available
        {
            if ($eventData['task']['description'] != '') {
                $message .= "\n✏️ " . $eventData['task']['description'];
            }
        } elseif (in_array($eventName, $comment_events))  // If comment available
        {
            $message .= "\n💬 " . $eventData['comment']['comment'];
        } elseif ($eventName === TaskFileModel::EVENT_CREATE)  // If attachment available
        {
            $file_path = getcwd() . "/data/files/" . $eventData['file']['path'];
            $file_mime = mime_content_type($file_path);

            $attachment_file = array(
                "name" => "file2",
                "filename" => clean($eventData['file']['name']),
                "type" => $file_mime,
                "data" => file_get_contents($file_path),
            );

            $fileInfo["attachment"] = $attachment_file;
        }

        // Create embed object

        $embedTitle = isset($eventData['project_name']) ? '**[' . $eventData['project_name'] . ']** ' : '**[' . $eventData['task']['project_name'] . ']** ';
        $embedType = 'rich';
        $embedDescription = $message;
        $embedTimestamp = date("c", strtotime("now"));
        $embedColor = hexdec('f9df18');
        // $embedFooter = [
        //     'text' => $author,
        //     'icon_url' => 'attachment://avatar.png',
        // ];
        $embedAuthor = [
            'name' => $author,
            #'url' => 'https://kanboard.org',
            'icon_url' => "attachment://avatar.{$avatar_fileType}",
        ];

        $embed = array(array(
            'title' => $embedTitle,
            'type' => $embedType,
            'description' => $embedDescription,
            'timestamp' => $embedTimestamp,
            'color' => $embedColor,
            #'footer' => $embedFooter,
            'author' => $embedAuthor,
            // 'fields' => [
            //     [
            //     "name" => "value",
            //     "value" => "value",
            //     "inline" => false,
            //     ],
            // ] ,
        ));

        if (isset($fileInfo["attachment"])) {
            if (str_contains($fileInfo['attachment']['type'], "image")) {
                $embedImage = ['url' => "attachment://{$fileInfo['attachment']['filename']}"];
                $embed[0]["image"] = $embedImage;
            }
        }

        $payload = [
            'username' => 'Kanboard',
            'avatar_url' => 'https://raw.githubusercontent.com/kanboard/kanboard/master/assets/img/favicon.png',
            'embeds' => $embed,
        ];

        $data = [
            "options" => $payload,
            "fileInfo" => $fileInfo,
        ];

        return $data;
    }

    /**
     * Send message to Discord
     *
     * @access protected
     * @param  string    $webhook
     * @param  array     $project
     * @param  string    $eventName
     * @param  array     $eventData
     */
    protected function sendMessage($webhook, array $project, $eventName, array $eventData)
    {
        $payload = $this->getMessage($project, $eventName, $eventData);
        DiscordSDK::SendWebhookMessage($webhook, $payload["options"], $payload["fileInfo"]);
    }
}
