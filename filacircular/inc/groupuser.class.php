<?php

class PluginFilaCircularGroupUser
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
        global $DB;

        $groups_id = (int) $groupUser->fields['groups_id'];
        $users_id  = (int) $groupUser->fields['users_id'];

        $table = 'glpi_plugin_filacircular_group_users';

        $DB->doQuery("
            DELETE FROM `$table`
            WHERE `groups_id` = $groups_id
              AND `users_id` = $users_id
        ");

        return $groupUser;
    }
}