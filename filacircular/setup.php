<?php

define('PLUGIN_FILACIRCULAR_VERSION', '1.0.0');
define('PLUGIN_FILACIRCULAR_MIN_GLPI_VERSION', '11.0.3');
define('PLUGIN_FILACIRCULAR_MAX_GLPI_VERSION', '11.0.99');

function plugin_filacircular_ensure_notifications()
{
    global $DB;

    $itemtype = \GlpiPlugin\Filacircular\FilaCircular::class;

    $notifications = [
        [
            'event' => 'last_active_removed',
            'name' => 'Alerta - grupo sem técnico ativo',
            'comment' => 'Notificação enviada quando o último técnico ativo do grupo é removido.',
            'template_name' => 'Alerta - grupo sem técnico ativo',
            'template_comment' => 'Notificação enviada quando o último técnico ativo do grupo é removido.',
            'subject' => 'Alerta: grupo ##filacircular.group## sem técnico ativo',
            'content_text' => "Atenção!\n\n"
                . "O grupo ##filacircular.group## ficou sem técnicos ativos disponíveis para atendimento pela FilaCircular.\n\n"
                . "Técnico removido: ##filacircular.user##\n"
                . "Data e hora: ##filacircular.datetime##\n\n"
                . "As demandas deste grupo ficarão sem atendimento pela FilaCircular até que um técnico ativo esteja disponível novamente."
        ],
        [
            'event' => 'last_coordinator_removed',
            'name' => 'Alerta - grupo sem Coordenador',
            'comment' => 'Notificação enviada quando o último Coordenador do grupo é removido.',
            'template_name' => 'Alerta - grupo sem Coordenador',
            'template_comment' => 'Notificação enviada quando o último Coordenador do grupo é removido.',
            'subject' => 'Alerta: grupo ##filacircular.group## sem Coordenador',
            'content_text' => "Atenção!\n\n"
                . "O grupo ##filacircular.group## ficou sem Coordenador na FilaCircular.\n\n"
                . "Coordenador removido: ##filacircular.user##\n"
                . "Data e hora: ##filacircular.datetime##\n\n"
                . "O grupo está sem Coordenador cadastrado e necessita de regularização."
        ]
    ];

    foreach ($notifications as $notification_data) {

        $result = $DB->request([
            'FROM' => 'glpi_notifications',
            'WHERE' => [
                'itemtype' => $itemtype,
                'event'    => $notification_data['event']
            ],
            'LIMIT' => 1
        ]);

        $notification_id = 0;

        foreach ($result as $row) {
            $notification_id = (int) $row['id'];
            break;
        }

        if ($notification_id === 0) {

            $template = new \NotificationTemplate();

            $template_id = $template->add([
                'name'     => $notification_data['template_name'],
                'itemtype' => $itemtype,
                'comment'  => $notification_data['template_comment']
            ]);

            if (!$template_id) {
                continue;
            }

            $translation = new \NotificationTemplateTranslation();

            $translation->add([
                'notificationtemplates_id' => $template_id,
                'language'                 => '',
                'subject'                  => $notification_data['subject'],
                'content_text'             => $notification_data['content_text'],
                'content_html'             => ''
            ]);

            $notification = new \Notification();

            $notification_id = $notification->add([
                'name'             => $notification_data['name'],
                'entities_id'      => 0,
                'itemtype'        => $itemtype,
                'event'            => $notification_data['event'],
                'comment'          => $notification_data['comment'],
                'is_recursive'     => 0,
                'is_active'        => 1,
                'allow_response'   => 0,
                'attach_documents' => -2
            ]);

            if (!$notification_id) {
                continue;
            }

            $notification_notificationtemplate =
                new \Notification_NotificationTemplate();

            $notification_notificationtemplate->add([
                'notifications_id'         => $notification_id,
                'mode'                     => 'mailing',
                'notificationtemplates_id' => $template_id
            ]);
        }

        \NotificationTarget::updateTargets([
            'itemtype'        => $itemtype,
            'notifications_id' => $notification_id,
            '_targets'        => [
                \Notification::USER_TYPE . '_1000'
            ]
        ]);
    }
}

function plugin_init_filacircular()
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['add_javascript']['filacircular'][] = 'js/groupremoval.js';

    Plugin::registerClass(
        \GlpiPlugin\Filacircular\FilaCircular::class,
        [
            'addtabon' => \Group::class,
            'notificationtemplates_types' => true,
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

    $PLUGIN_HOOKS['item_add_targets']['filacircular']['GlpiPlugin\Filacircular\NotificationTargetFilaCircular']
        = ['GlpiPlugin\Filacircular\NotificationTargetFilaCircular', 'addSpecificTargets'];
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
                `emergency_email` varchar(255) DEFAULT NULL,
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

    plugin_filacircular_ensure_notifications();

    return true;
}

function plugin_filacircular_uninstall($params = [])
{
    return true;
}