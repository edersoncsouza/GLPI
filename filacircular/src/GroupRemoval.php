<?php

namespace GlpiPlugin\Filacircular;

class GroupRemoval
{
    public static function isLastActiveParticipant($groups_id, $users_id)
    {
        global $DB;

        $groups_id = (int) $groups_id;
        $users_id  = (int) $users_id;

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

            if ((int) $row['users_id'] !== $users_id) {
                return false;
            }
        }

        return $active_count === 1;
    }
}