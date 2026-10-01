<?php
namespace GlpiPlugin\Filacircular;
use NotificationEvent;

class GroupUser
{
    public static function add($groupUser)
    {
        global $DB;

        $groups_id = (int) $groupUser->fields['groups_id'];
        $users_id  = (int) $groupUser->fields['users_id'];

        $table = 'glpi_plugin_filacircular_group_users';

        $DB->doQuery("
            INSERT INTO `$table`
                (`groups_id`, `users_id`, `is_active`)
            VALUES
                ($groups_id, $users_id, 1)
            ON DUPLICATE KEY UPDATE
                `is_active` = 1
        ");

        return $groupUser;
    }

    public static function purge($groupUser)
    {
        file_put_contents(
            '/tmp/filacircular_purge_debug.txt',
            date('Y-m-d H:i:s')
            . ' login_user_id=' . (int) \Session::getLoginUserID()
            . PHP_EOL,
            FILE_APPEND
        );

        global $DB;

        $groups_id = (int) $groupUser->fields['groups_id'];
        $users_id  = (int) $groupUser->fields['users_id'];

        $table = 'glpi_plugin_filacircular_group_users';

        $last_active_participant = false;
        $last_coordinator = false;

        $result = $DB->request([
            'FROM' => $table,
            'WHERE' => [
                'groups_id' => $groups_id,
                'is_active' => 1
            ]
        ]);

        $active_count = 0;

        foreach ($result as $row) {
            $active_count++;

            if ((int) $row['users_id'] === $users_id) {
                $last_active_participant = true;
            }
        }

        if ($active_count !== 1) {
            $last_active_participant = false;
        }

        $result = $DB->request([
            'FROM' => 'glpi_plugin_filacircular_group_coordinators',
            'WHERE' => [
                'groups_id' => $groups_id
            ]
        ]);

        $coordinator_count = 0;

        foreach ($result as $row) {
            $coordinator_count++;

            if ((int) $row['users_id'] === $users_id) {
                $last_coordinator = true;
            }
        }

        if ($coordinator_count !== 1) {
            $last_coordinator = false;
        }

        file_put_contents(
            '/tmp/filacircular_purge_debug.txt',
            date('Y-m-d H:i:s')
            . ' groups_id=' . $groups_id
            . ' users_id=' . $users_id
            . ' active_count=' . $active_count
            . ' last_active=' . ($last_active_participant ? '1' : '0')
            . ' coordinator_count=' . $coordinator_count
            . ' last_coordinator=' . ($last_coordinator ? '1' : '0')
            . PHP_EOL,
            FILE_APPEND
        );

        $DB->doQuery("
            DELETE FROM `$table`
            WHERE `groups_id` = $groups_id
              AND `users_id` = $users_id
        ");

        $DB->doQuery("
            DELETE FROM `glpi_plugin_filacircular_group_coordinators`
            WHERE `groups_id` = $groups_id
              AND `users_id` = $users_id
        ");

        if ($last_active_participant) {

            $result = $DB->request([
                'FROM' => 'glpi_plugin_filacircular_rr_groups',
                'WHERE' => [
                    'groups_id' => $groups_id,
                    'enabled'   => 1
                ],
                'LIMIT' => 1
            ]);

            $fila_circular_ativa = false;

            foreach ($result as $row) {
                $fila_circular_ativa = true;
                break;
            }

            if ($fila_circular_ativa) {
                NotificationEvent::raiseEvent(
                    'last_active_removed',
                    new FilaCircular(),
                    [
                        'groups_id'         => $groups_id,
                        'users_id'          => $users_id,
                        'date_time'         => date('Y-m-d H:i:s'),
                        'removed_by_user_id' => (int) \Session::getLoginUserID()
                    ]
                );
            }
        }

        if ($last_coordinator) {

            $result = $DB->request([
                'FROM' => 'glpi_plugin_filacircular_rr_groups',
                'WHERE' => [
                    'groups_id' => $groups_id,
                    'enabled'   => 1
                ],
                'LIMIT' => 1
            ]);

            $fila_circular_ativa = false;

            foreach ($result as $row) {
                $fila_circular_ativa = true;
                break;
            }

            if ($fila_circular_ativa) {
                file_put_contents(
                    '/tmp/filacircular_purge_debug.txt',
                    date('Y-m-d H:i:s')
                    . ' removed_by_user_id='
                    . (int) \Session::getLoginUserID()
                    . PHP_EOL,
                    FILE_APPEND
                );
                $notification_result = NotificationEvent::raiseEvent(
                    'last_coordinator_removed',
                    new FilaCircular(),
                    [
                        'groups_id' => $groups_id,
                        'users_id'  => $users_id,
                        'date_time' => date('Y-m-d H:i:s')
                    ]
                );

                file_put_contents(
                    '/tmp/filacircular_purge_debug.txt',
                    date('Y-m-d H:i:s')
                    . ' notification_result='
                    . var_export($notification_result, true)
                    . PHP_EOL,
                    FILE_APPEND
                );
            }
        }

        return $groupUser;
    }


    public static function setActive($groups_id, $users_id, $is_active)
    {
        global $DB;

        $groups_id = (int) $groups_id;
        $users_id  = (int) $users_id;
        $is_active = (int) $is_active;

        if (!GroupCoordinator::canManageParticipants(
            $groups_id,
            (int) \Session::getLoginUserID()
        )) {
            return false;
        }

        $table = 'glpi_plugin_filacircular_group_users';

        $was_active = false;

        $result = $DB->request([
            'FROM' => $table,
            'WHERE' => [
                'groups_id' => $groups_id,
                'users_id'  => $users_id
            ],
            'LIMIT' => 1
        ]);

        foreach ($result as $row) {
            $was_active = ((int) $row['is_active'] === 1);
            break;
        }

        $DB->doQuery("
            UPDATE `$table`
            SET `is_active` = $is_active
            WHERE `groups_id` = $groups_id
              AND `users_id` = $users_id
        ");

        if ($is_active === 0 && $was_active) {
            $result = $DB->request([
                'FROM' => $table,
                'WHERE' => [
                    'groups_id' => $groups_id,
                    'is_active' => 1
                ]
            ]);

            $active_count = 0;

            foreach ($result as $row) {
                $active_count++;
            }

            if ($active_count === 0) {
                $result = $DB->request([
                    'FROM' => 'glpi_plugin_filacircular_rr_groups',
                    'WHERE' => [
                        'groups_id' => $groups_id,
                        'enabled'   => 1
                    ],
                    'LIMIT' => 1
                ]);

                $fila_circular_ativa = false;

                foreach ($result as $row) {
                    $fila_circular_ativa = true;
                    break;
                }

                if ($fila_circular_ativa) {
                    NotificationEvent::raiseEvent(
                        'last_active_removed',
                        new FilaCircular(),
                        [
                            'groups_id' => $groups_id,
                            'users_id'  => $users_id,
                            'date_time' => date('Y-m-d H:i:s')
                        ]
                    );
                }
            }
        }

        return true;
    }
}