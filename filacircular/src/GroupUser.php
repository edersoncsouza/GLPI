<?php
namespace GlpiPlugin\Filacircular;

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
        global $DB;

        $groups_id = (int) $groupUser->fields['groups_id'];
        $users_id  = (int) $groupUser->fields['users_id'];

        $table = 'glpi_plugin_filacircular_group_users';

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

        if ($is_active === 0) {
            $result = $DB->request([
                'FROM' => 'glpi_plugin_filacircular_group_users',
                'WHERE' => [
                    'groups_id' => $groups_id,
                    'is_active' => 1
                ]
            ]);

            $active_count = 0;

            foreach ($result as $row) {
                $active_count++;
            }

            if ($active_count <= 1) {
                return false;
            }
        }

        $table = 'glpi_plugin_filacircular_group_users';

        $DB->doQuery("
            UPDATE `$table`
            SET `is_active` = $is_active
            WHERE `groups_id` = $groups_id
              AND `users_id` = $users_id
        ");

        return true;
    }
}