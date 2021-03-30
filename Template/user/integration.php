<?php

use Kanboard\Model\TaskModel;
use Kanboard\Model\SubTaskModel;
use Kanboard\Model\CommentModel;
use Kanboard\Model\TaskFileModel;

$reflection = new ReflectionClass(TaskModel::class);
$constants = $reflection->getConstants();

$taskEvents = array_filter($constants, function ($key) {
    return strpos($key, "EVENT") !== false;
}, ARRAY_FILTER_USE_KEY);

$reflection = new ReflectionClass(SubTaskModel::class);
$constants = $reflection->getConstants();

$subTaskEvents = array_filter($constants, function ($key) {
    return strpos($key, "EVENT") !== false;
}, ARRAY_FILTER_USE_KEY);

$reflection = new ReflectionClass(CommentModel::class);
$constants = $reflection->getConstants();

$commentEvents = array_filter($constants, function ($key) {
    return strpos($key, "EVENT") !== false;
}, ARRAY_FILTER_USE_KEY);

$reflection = new ReflectionClass(TaskFileModel::class);
$constants = $reflection->getConstants();

$taskFileEvents = array_filter($constants, function ($key) {
    return strpos($key, "EVENT") !== false;
}, ARRAY_FILTER_USE_KEY);
?>

<h3><i class="fa fa-discord fa-fw"></i>Discord</h3>
<div class="panel">

    <div style="display:flex; flex-direction:row;">
        <div style="display:flex; flex-direction:column; margin:1rem;">
            <h3>General</h3>
            <div style="display:flex; flex-direction:column;">

                <?= $this->form->label(t('Webhook URL'), 'discord_webhook_url') ?>
                <?= $this->form->text('discord_webhook_url', $values) ?>


                <p class="form-help">
                    <a href="https://github.com/Revolware-com/plugin-discord#configuration" target="_blank"><?= t('Help on Discord integration') ?></a>
                </p>

            </div>
        </div>

        <div style="display:flex; flex-direction:column; margin:1rem;">
            <h3>Trigger Events</h3>

            <h4 style="margin:1rem 1rem 0rem 1rem">Task Events</h4>

            <div style="display:flex; flex-direction:row; flex-wrap:wrap; justify-content: start;">

                <?php foreach ($taskEvents as $key => $name) {
                    $id = str_replace(".", "_", $name);
                    $value = str_replace("event_", "", strtolower($key));
                    $checked = isset($values[$id]) && $values[$id] == 1;
                ?>
                    <div style="display:flex; flex-direction:column; margin: 0.5rem 1rem 0.5rem 0; text-align:center;">
                        <?= $this->form->hidden($id, array($id => 0)) ?>
                        <?= $this->form->checkbox($id, $value, 1, $checked) ?>
                    </div>
                <?php
                }
                ?>

            </div>

            <h4 style="margin:1rem 1rem 0rem 1rem">Subtask Events</h4>

            <div style="display:flex; flex-direction:row; flex-wrap:wrap; justify-content: start;">

                <?php foreach ($subTaskEvents as $key => $name) {
                    $id = str_replace(".", "_", $name);
                    $value = str_replace("event_", "", strtolower($key));
                    $checked = isset($values[$id]) && $values[$id] == 1;
                ?>
                    <div style="display:flex; flex-direction:column; margin: 0.5rem 1rem 0.5rem 0; text-align:center;">
                        <?= $this->form->hidden($id, array($id => 0)) ?>
                        <?= $this->form->checkbox($id, $value, 1, $checked) ?>
                    </div>
                <?php
                }
                ?>

            </div>

            <h4 style="margin:1rem 1rem 0rem 1rem">Comment Events</h4>

            <div style="display:flex; flex-direction:row; flex-wrap:wrap; justify-content: start;">

                <?php foreach ($commentEvents as $key => $name) {
                    $id = str_replace(".", "_", $name);
                    $value = str_replace("event_", "", strtolower($key));
                    $checked = isset($values[$id]) && $values[$id] == 1;
                ?>
                    <div style="display:flex; flex-direction:column; margin: 0.5rem 1rem 0.5rem 0; text-align:center;">
                        <?= $this->form->hidden($id, array($id => 0)) ?>
                        <?= $this->form->checkbox($id, $value, 1, $checked) ?>
                    </div>
                <?php
                }
                ?>

            </div>

            <h4 style="margin:1rem 1rem 0rem 1rem">TaskFile Events</h4>

            <div style="display:flex; flex-direction:row; flex-wrap:wrap; justify-content: start;">

                <?php foreach ($taskFileEvents as $key => $name) {
                    $id = str_replace(".", "_", $name);
                    $value = str_replace("event_", "", strtolower($key));
                    $checked = isset($values[$id]) && $values[$id] == 1;
                ?>
                    <div style="display:flex; flex-direction:column; margin: 0.5rem 1rem 0.5rem 0; text-align:center;">
                        <?= $this->form->hidden($id, array($id => 0)) ?>
                        <?= $this->form->checkbox($id, $value, 1, $checked) ?>
                    </div>
                <?php
                }
                ?>

            </div>
        </div>
    </div>

    <div class="form-actions">
        <input type="submit" value="<?= t('Save') ?>" class="btn btn-blue" />
    </div>
</div>
