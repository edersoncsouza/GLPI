<?php

namespace GlpiPlugin\Filacircular;

use CommonGLPI;
use Session;

class FilaCircular extends CommonGLPI
{
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$withtemplate && $item instanceof \Group) {
            return self::createTabEntry('FilaCircular', 0, $item::class);
        }

        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        global $DB;

        if (!$item instanceof \Group) {
            return false;
        }

        $groups_id = (int) $item->getID();
        $table = 'glpi_plugin_filacircular_rr_groups';

        $enabled = false;
        $allow_coordinator_management = false;

        $result = $DB->request([
            'FROM'   => $table,
            'WHERE'  => ['groups_id' => $groups_id],
            'LIMIT'  => 1
        ]);

        foreach ($result as $row) {
            $enabled = ((int) $row['enabled'] === 1);
            $allow_coordinator_management = ((int) $row['allow_coordinator_management'] === 1);
        }

        echo '<div class="p-3">';
        echo '<h3>FilaCircular</h3>';

        echo '<h4>Configuração da FilaCircular</h4>';

        echo '<div class="mb-3">';
        echo '<strong>Status:</strong> ';
        echo $enabled ? 'Ativa' : 'Inativa';
        echo '</div>';

        if (Session::haveRight('config', UPDATE)) {
            echo '<form method="post" action="' . htmlescape(self::getFormURL()) . '">';

            echo \Html::hidden('groups_id', ['value' => $groups_id]);

            if ($enabled) {
                echo '<button type="submit" name="deactivate" class="btn btn-primary">';
                echo 'Desativar FilaCircular';
                echo '</button>';
            } else {
                echo '<button type="submit" name="activate" class="btn btn-primary">';
                echo 'Ativar FilaCircular';
                echo '</button>';
            }

            \Html::closeForm();
        }

        echo '<hr>';

        echo '<h4>Configuração dos Coordenadores</h4>';

        if (Session::haveRight('config', UPDATE)) {
            echo '<form method="post" action="' . htmlescape(self::getFormURL()) . '">';

            echo \Html::hidden('groups_id', ['value' => $groups_id]);

            echo '<div class="mb-2">';
            echo '<strong>Permitir que coordenadores gerenciem coordenadores:</strong>';
            echo '</div>';

            echo '<div class="form-check">';
            echo '<input class="form-check-input" type="radio" name="allow_coordinator_management" value="1" id="allow_coordinator_management_yes"';
            echo $allow_coordinator_management ? ' checked' : '';
            echo '>';
            echo '<label class="form-check-label" for="allow_coordinator_management_yes">';
            echo 'Sim';
            echo '</label>';
            echo '</div>';

            echo '<div class="form-check">';
            echo '<input class="form-check-input" type="radio" name="allow_coordinator_management" value="0" id="allow_coordinator_management_no"';
            echo !$allow_coordinator_management ? ' checked' : '';
            echo '>';
            echo '<label class="form-check-label" for="allow_coordinator_management_no">';
            echo 'Não';
            echo '</label>';
            echo '</div>';

            echo '<button type="submit" name="set_coordinator_management" class="btn btn-primary mt-2">';
            echo 'Salvar configuração';
            echo '</button>';

            \Html::closeForm();
        }

        echo '<hr>';

        $group_users = \Group_User::getGroupUsers($groups_id);

        echo '<h4>Coordenadores</h4>';

        $coordinators = $DB->request([
            'SELECT' => [
                'glpi_users.id',
                'glpi_users.name'
            ],
            'FROM' => 'glpi_plugin_filacircular_group_coordinators',
            'INNER JOIN' => [
                'glpi_users' => [
                    'FKEY' => [
                        'glpi_plugin_filacircular_group_coordinators' => 'users_id',
                        'glpi_users' => 'id'
                    ]
                ]
            ],
            'WHERE' => [
                'glpi_plugin_filacircular_group_coordinators.groups_id' => $groups_id
            ],
            'ORDER' => 'glpi_users.name ASC'
        ]);

        echo '<ul class="list-unstyled mb-0">';

        $has_coordinators = false;

        foreach ($coordinators as $coordinator) {
            $has_coordinators = true;

            echo '<li class="mb-2">';

            echo '<div class="d-flex align-items-center gap-2">';
            echo '<span>';
            echo htmlescape($coordinator['name']);
            echo '</span>';

            if (\GlpiPlugin\Filacircular\GroupCoordinator::canManage(
                $groups_id,
                (int) Session::getLoginUserID()
            )) {
                echo '<form method="post" action="' . htmlescape(self::getFormURL()) . '" class="ms-3">';

                echo \Html::hidden('groups_id', ['value' => $groups_id]);
                echo \Html::hidden('users_id', ['value' => $coordinator['id']]);

                echo '<button type="submit" name="remove_coordinator" class="btn btn-sm btn-outline-danger">';
                echo 'Remover';
                echo '</button>';

                \Html::closeForm();
            }

            echo '</div>';
            echo '</li>';
        }

        if (!$has_coordinators) {
            echo '<li class="alert alert-warning">';
            echo '<strong>Nenhum coordenador técnico definido.</strong>';
            echo '<br>';
            echo 'É necessário ter pelo menos um Coordenador técnico para gerenciar os participantes, atribuir chamados diretamente e refazer a distribuição de chamados entre os técnicos ativos.';
            echo '</li>';
        }

        echo '</ul>';

        if (\GlpiPlugin\Filacircular\GroupCoordinator::canManage(
            $groups_id,
            (int) Session::getLoginUserID()
        )) {
            echo '<form method="post" action="' . htmlescape(self::getFormURL()) . '" class="mt-3">';

            echo \Html::hidden('groups_id', ['value' => $groups_id]);

            echo '<div class="mb-2">';
            echo '<strong>Adicionar coordenador:</strong>';
            echo '</div>';

            echo '<select name="users_id" class="form-select mb-2">';

            $coordinator_ids = [];

            foreach ($coordinators as $coordinator) {
                $coordinator_ids[] = (int) $coordinator['id'];
            }

            foreach ($group_users as $group_user) {
                $users_id = (int) $group_user['id'];
                $username = $group_user['name'];

                if (in_array($users_id, $coordinator_ids, true)) {
                    continue;
                }

                echo '<option value="' . $users_id . '">';
                echo htmlescape($username);
                echo '</option>';
            }

            echo '</select>';

            echo '<button type="submit" name="add_coordinator" class="btn btn-primary">';
            echo 'Adicionar coordenador';
            echo '</button>';

            \Html::closeForm();
        }

        echo '<hr>';


        echo '<h4>Técnicos participantes</h4>';

        echo '<table class="table table-hover">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Técnico</th>';
        echo '<th>Participação na FilaCircular</th>';
        echo '<th>Ação</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        $can_manage_participants = GroupCoordinator::canManageParticipants(
            $groups_id,
            (int) Session::getLoginUserID()
        );

        foreach ($group_users as $group_user) {
            $users_id = (int) $group_user['id'];
            $username = $group_user['name'];

            $is_active = false;

            $participation = $DB->request([
                'FROM'   => 'glpi_plugin_filacircular_group_users',
                'WHERE'  => [
                    'groups_id' => $groups_id,
                    'users_id'  => $users_id
                ],
                'LIMIT' => 1
            ]);

            foreach ($participation as $row) {
                $is_active = ((int) $row['is_active'] === 1);
            }

            echo '<tr>';
            echo '<td>' . htmlescape($username) . '</td>';
            echo '<td>';
            echo $is_active ? 'Ativo' : 'Inativo';
            echo '</td>';
            echo '<td>';

            if ($can_manage_participants) {
                echo '<form method="post" action="' . htmlescape(self::getFormURL()) . '" class="d-inline">';

                echo \Html::hidden('groups_id', ['value' => $groups_id]);
                echo \Html::hidden('users_id', ['value' => $users_id]);
                echo \Html::hidden('is_active', [
                    'value' => $is_active ? 0 : 1
                ]);

                echo '<button type="submit" name="set_participation" class="btn btn-sm btn-outline-primary">';
                echo $is_active ? 'Desativar' : 'Ativar';
                echo '</button>';

                \Html::closeForm();
            }

            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';

        echo '</div>';

        return true;
    }

    public static function setCoordinatorManagement($groups_id, $allow)
    {
        global $DB;

        $groups_id = (int) $groups_id;
        $allow = (int) $allow;

        $table = 'glpi_plugin_filacircular_rr_groups';

        $DB->doQuery("
            INSERT INTO `$table`
                (`groups_id`, `enabled`, `allow_coordinator_management`)
            VALUES
                ($groups_id, 0, $allow)
            ON DUPLICATE KEY UPDATE
                `allow_coordinator_management` = $allow
        ");

        Session::addMessageAfterRedirect(
            'Configuração de gerenciamento de coordenadores atualizada.',
            false,
            INFO
        );

        return true;
    }

    public static function activate($groups_id)
    {
        global $DB;

        $groups_id = (int) $groups_id;

        $result = $DB->request([
            'SELECT' => [
                'glpi_groups_users.users_id'
            ],
            'FROM' => 'glpi_groups_users',
            'INNER JOIN' => [
                'glpi_plugin_filacircular_group_users' => [
                    'FKEY' => [
                        'glpi_groups_users' => 'users_id',
                        'glpi_plugin_filacircular_group_users' => 'users_id'
                    ]
                ]
            ],
            'WHERE' => [
                'glpi_groups_users.groups_id' => $groups_id,
                'glpi_plugin_filacircular_group_users.groups_id' => $groups_id,
                'glpi_plugin_filacircular_group_users.is_active' => 1
            ],
            'ORDER' => 'glpi_groups_users.users_id ASC'
        ]);

        $next_user_id = null;

        foreach ($result as $row) {
            $next_user_id = (int) $row['users_id'];
            break;
        }

        if ($next_user_id === null) {
            Session::addMessageAfterRedirect(
                'NÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â£o ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â© possÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­vel ativar a FilaCircular: nÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â£o hÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¡ tÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â©cnicos elegÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â­veis neste grupo.',
                false,
                ERROR
            );

            return false;
        }

        $table = 'glpi_plugin_filacircular_rr_groups';

        $DB->doQuery("
            INSERT INTO `$table`
                (`groups_id`, `enabled`, `next_user_id`)
            VALUES
                ($groups_id, 1, $next_user_id)
            ON DUPLICATE KEY UPDATE
                `enabled` = 1,
                `next_user_id` = $next_user_id
        ");

        Session::addMessageAfterRedirect(
            'FilaCircular ativada com sucesso.',
            false,
            INFO
        );

        return true;
    }

    public static function deactivate($groups_id)
    {
        global $DB;

        $groups_id = (int) $groups_id;
        $table = 'glpi_plugin_filacircular_rr_groups';

        $DB->doQuery("
            INSERT INTO `$table`
                (`groups_id`, `enabled`, `next_user_id`)
            VALUES
                ($groups_id, 0, NULL)
            ON DUPLICATE KEY UPDATE
                `enabled` = 0,
                `next_user_id` = NULL
        ");

        Session::addMessageAfterRedirect(
            'FilaCircular desativada com sucesso.',
            false,
            INFO
        );

        return true;
    }
}