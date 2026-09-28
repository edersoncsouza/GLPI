<?php

use function Safe\json_encode;

header('Content-Type: application/json; charset=UTF-8');
Html::header_nocache();

$group_user_id = isset($_POST['group_user_id'])
    ? (int) $_POST['group_user_id']
    : 0;

$last_active_participant = false;
$group_name = '';

if ($group_user_id > 0) {
    $group_user = new \Group_User();

    if ($group_user->getFromDB($group_user_id)) {
        $groups_id = (int) $group_user->fields['groups_id'];
        $users_id  = (int) $group_user->fields['users_id'];

        $group = new \Group();

        if ($group->getFromDB($groups_id)) {
            $group_name = $group->fields['name'];
        }

        global $DB;

        $result = $DB->request([
            'FROM' => 'glpi_plugin_filacircular_rr_groups',
            'WHERE' => [
                'groups_id' => $groups_id,
                'enabled'   => 1
            ],
            'LIMIT' => 1
        ]);

        $enabled = false;

        foreach ($result as $row) {
            $enabled = true;
            break;
        }

        if ($enabled) {
            $last_active_participant =
                \GlpiPlugin\Filacircular\GroupRemoval::isLastActiveParticipant(
                    $groups_id,
                    $users_id
                );
        }
    }
}

echo json_encode([
    'success' => true,
    'last_active_participant' => $last_active_participant,
    'group_name' => $group_name
]);
