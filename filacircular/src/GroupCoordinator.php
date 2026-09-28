<?php

namespace GlpiPlugin\Filacircular;

class GroupCoordinator
{

    public static function canManage($groups_id, $users_id)
    {
        global $DB;

        $groups_id = (int) $groups_id;
        $users_id  = (int) $users_id;

        if (\Session::haveRight('config', UPDATE)) {
            return true;
        }

        $config = $DB->request([
            'FROM' => 'glpi_plugin_filacircular_rr_groups',
            'WHERE' => [
                'groups_id' => $groups_id,
                'allow_coordinator_management' => 1
            ],
            'LIMIT' => 1
        ]);

        foreach ($config as $row) {
            return self::isCoordinator($groups_id, $users_id);
        }

        return false;
    }

    public static function add($groups_id, $users_id)
    {
        global $DB;

        $groups_id = (int) $groups_id;
        $users_id  = (int) $users_id;

        $membership = $DB->request([
            'FROM' => 'glpi_groups_users',
            'WHERE' => [
                'groups_id' => $groups_id,
                'users_id'  => $users_id
            ],
            'LIMIT' => 1
        ]);

        $is_member = false;

        foreach ($membership as $row) {
            $is_member = true;
            break;
        }

        if (!$is_member) {
            return false;
        }

        $table = 'glpi_plugin_filacircular_group_coordinators';

        $existing = $DB->request([
            'FROM' => $table,
            'WHERE' => [
                'groups_id' => $groups_id,
                'users_id'  => $users_id
            ],
            'LIMIT' => 1
        ]);

        foreach ($existing as $row) {
            return false;
        }

        $DB->doQuery("
            INSERT INTO `$table`
                (`groups_id`, `users_id`)
            VALUES
                ($groups_id, $users_id)
        ");

        return true;
    }

    public static function remove($groups_id, $users_id)
    {
        global $DB;

        $groups_id = (int) $groups_id;
        $users_id  = (int) $users_id;

        $table = 'glpi_plugin_filacircular_group_coordinators';

        $result = $DB->request([
            'FROM' => $table,
            'WHERE' => [
                'groups_id' => $groups_id
            ]
        ]);

        $coordinator_count = 0;

        foreach ($result as $row) {
            $coordinator_count++;
        }

        if ($coordinator_count <= 1) {
            return false;
        }

        $DB->doQuery("
            DELETE FROM `$table`
            WHERE `groups_id` = $groups_id
              AND `users_id` = $users_id
        ");

        return true;
    }

    public static function isCoordinator($groups_id, $users_id)
    {
        global $DB;

        $groups_id = (int) $groups_id;
        $users_id  = (int) $users_id;

        $table = 'glpi_plugin_filacircular_group_coordinators';

        $result = $DB->request([
            'FROM' => $table,
            'WHERE' => [
                'groups_id' => $groups_id,
                'users_id'  => $users_id
            ],
            'LIMIT' => 1
        ]);

        foreach ($result as $row) {
            return true;
        }

        return false;
    }

    public static function canManageParticipants($groups_id, $users_id)
    {
        if (\Session::haveRight('config', UPDATE)) {
            return true;
        }

        return self::isCoordinator($groups_id, $users_id);
    }
}