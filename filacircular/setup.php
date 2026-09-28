<?php

define('PLUGIN_FILACIRCULAR_VERSION', '1.0.0');
define('PLUGIN_FILACIRCULAR_MIN_GLPI_VERSION', '11.0.3');
define('PLUGIN_FILACIRCULAR_MAX_GLPI_VERSION', '11.0.99');

function plugin_init_filacircular()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['add_javascript']['filacircular'][] = 'js/groupremoval.js';

    Plugin::registerClass(
        \GlpiPlugin\Filacircular\FilaCircular::class,
        [
            'addtabon' => \Group::class
        ]
    );

    $PLUGIN_HOOKS['item_add']['filacircular']['Group_User']
        = ['GlpiPlugin\Filacircular\GroupUser', 'add'];

    $PLUGIN_HOOKS['item_purge']['filacircular']['Group_User']
        = ['GlpiPlugin\Filacircular\GroupUser', 'purge'];

    $PLUGIN_HOOKS['post_prepareadd']['filacircular']['Ticket']
        = ['GlpiPlugin\Filacircular\Assignment', 'prepareAdd'];

    $PLUGIN_HOOKS['item_add']['filacircular']['Ticket']
        = ['GlpiPlugin\Filacircular\Assignment', 'itemAdd'];
}

function plugin_version_filacircular()
{
    return [
        'name' => 'FilaCircular',
        'version' => PLUGIN_FILACIRCULAR_VERSION,
        'author' => 'Prefeitura de Cachoeirinha',
        'license' => 'GPLv3',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_FILACIRCULAR_MIN_GLPI_VERSION,
                'max' => PLUGIN_FILACIRCULAR_MAX_GLPI_VERSION
            ]
        ]
    ];
}

function plugin_filacircular_install($params = [])
{
    global $DB;

    $table = 'glpi_plugin_filacircular_group_users';

    if (!$DB->tableExists($table)) {
        $DB->doQuery("
            CREATE TABLE `$table` (
                `id` int NOT NULL AUTO_INCREMENT,
                `groups_id` int NOT NULL,
                `users_id` int NOT NULL,
                `is_active` tinyint NOT NULL DEFAULT '1',
                PRIMARY KEY (`id`),
                UNIQUE KEY `unicity` (`groups_id`, `users_id`),
                KEY `groups_id` (`groups_id`),
                KEY `users_id` (`users_id`),
                KEY `groups_id_is_active` (`groups_id`, `is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
        ");
    }

    $table = 'glpi_plugin_filacircular_rr_groups';

    if (!$DB->tableExists($table)) {
        $DB->doQuery("
            CREATE TABLE `$table` (
                `groups_id` int NOT NULL,
                `enabled` tinyint NOT NULL DEFAULT '0',
                `allow_coordinator_management` tinyint NOT NULL DEFAULT '0',
                `next_user_id` int DEFAULT NULL,
                PRIMARY KEY (`groups_id`),
                KEY `next_user_id` (`next_user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
        ");
    }

    $table = 'glpi_plugin_filacircular_group_coordinators';

    if (!$DB->tableExists($table)) {
        $DB->doQuery("
            CREATE TABLE `$table` (
                `id` int NOT NULL AUTO_INCREMENT,
                `groups_id` int NOT NULL,
                `users_id` int NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unicity` (`groups_id`,`users_id`),
                KEY `groups_id` (`groups_id`),
                KEY `users_id` (`users_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
        ");
    }

    return true;
}

function plugin_filacircular_uninstall($params = [])
{
    return true;
}
