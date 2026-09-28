<?php

namespace GlpiPlugin\Filacircular;

class Assignment
{
    private static array $assignments = [];

    public static function prepareAdd($ticket)
    {
        global $DB;

        if (!isset($ticket->input['_groups_id_assign'])) {
            return $ticket;
        }

        $groups_id = (int) $ticket->input['_groups_id_assign'];

        if ($groups_id <= 0) {
            return $ticket;
        }

        $config_result = $DB->request([
            'FROM'   => 'glpi_plugin_filacircular_rr_groups',
            'WHERE'  => [
                'groups_id' => $groups_id,
                'enabled'   => 1
            ],
            'LIMIT'  => 1
        ]);

        $config = null;

        foreach ($config_result as $row) {
            $config = $row;
            break;
        }

        if ($config === null) {
            return $ticket;
        }

        $lock_name = 'filacircular_group_' . $groups_id;

        $lock_result = $DB->doQuery("
            SELECT GET_LOCK('$lock_name', 5) AS lock_result
        ");

        $lock = $lock_result->fetch_assoc();

        if ((int) $lock['lock_result'] !== 1) {
            return $ticket;
        }

        $next_user_id = $config['next_user_id'] !== null
            ? (int) $config['next_user_id']
            : null;

        $eligible_result = $DB->request([
            'SELECT' => [
                'glpi_plugin_filacircular_group_users.users_id'
            ],
            'FROM' => 'glpi_plugin_filacircular_group_users',
            'INNER JOIN' => [
                'glpi_groups_users' => [
                    'FKEY' => [
                        'glpi_plugin_filacircular_group_users' => 'users_id',
                        'glpi_groups_users' => 'users_id'
                    ]
                ]
            ],
            'WHERE' => [
                'glpi_plugin_filacircular_group_users.groups_id' => $groups_id,
                'glpi_plugin_filacircular_group_users.is_active' => 1,
                'glpi_groups_users.groups_id' => $groups_id
            ],
            'ORDER' => 'glpi_plugin_filacircular_group_users.users_id ASC'
        ]);

        $eligible_users = [];

        foreach ($eligible_result as $row) {
            $eligible_users[] = (int) $row['users_id'];
        }

        if ($eligible_users === []) {
            $DB->doQuery("
                SELECT RELEASE_LOCK('$lock_name')
            ");

            return $ticket;
        }

        $selected_user_id = null;

        if ($next_user_id !== null) {
            foreach ($eligible_users as $user_id) {
                if ($user_id >= $next_user_id) {
                    $selected_user_id = $user_id;
                    break;
                }
            }
        }

        if ($selected_user_id === null) {
            $selected_user_id = $eligible_users[0];
        }

        $ticket->input['_users_id_assign'] = [$selected_user_id];

        self::$assignments[spl_object_id($ticket)] = [
            'groups_id' => $groups_id,
            'users_id' => $selected_user_id,
            'lock_name' => $lock_name
        ];

        return $ticket;
    }

    public static function itemAdd($ticket)
    {
        global $DB;

        $key = spl_object_id($ticket);

        if (!isset(self::$assignments[$key])) {
            return $ticket;
        }

        $assignment = self::$assignments[$key];

        $groups_id = $assignment['groups_id'];
        $users_id = $assignment['users_id'];
        $lock_name = $assignment['lock_name'];

        $eligible_result = $DB->request([
            'SELECT' => [
                'glpi_plugin_filacircular_group_users.users_id'
            ],
            'FROM' => 'glpi_plugin_filacircular_group_users',
            'INNER JOIN' => [
                'glpi_groups_users' => [
                    'FKEY' => [
                        'glpi_plugin_filacircular_group_users' => 'users_id',
                        'glpi_groups_users' => 'users_id'
                    ]
                ]
            ],
            'WHERE' => [
                'glpi_plugin_filacircular_group_users.groups_id' => $groups_id,
                'glpi_plugin_filacircular_group_users.is_active' => 1,
                'glpi_groups_users.groups_id' => $groups_id
            ],
            'ORDER' => 'glpi_plugin_filacircular_group_users.users_id ASC'
        ]);

        $eligible_users = [];

        foreach ($eligible_result as $row) {
            $eligible_users[] = (int) $row['users_id'];
        }

        if ($eligible_users === []) {
            $DB->doQuery("
                SELECT RELEASE_LOCK('$lock_name')
            ");

            unset(self::$assignments[$key]);

            return $ticket;
        }

        $next_user_id = $eligible_users[0];

        foreach ($eligible_users as $index => $eligible_user_id) {
            if ($eligible_user_id === $users_id) {
                $next_index = ($index + 1) % count($eligible_users);
                $next_user_id = $eligible_users[$next_index];
                break;
            }
        }

        $DB->doQuery("
            UPDATE glpi_plugin_filacircular_rr_groups
            SET next_user_id = $next_user_id
            WHERE groups_id = $groups_id
              AND enabled = 1
        ");

        $DB->doQuery("
            SELECT RELEASE_LOCK('$lock_name')
        ");

        unset(self::$assignments[$key]);

        return $ticket;
    }
}