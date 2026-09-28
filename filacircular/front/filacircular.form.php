<?php

use GlpiPlugin\Filacircular\FilaCircular;

Session::checkRight('group', READ);

$groups_id = isset($_POST['groups_id']) ? (int) $_POST['groups_id'] : 0;

if ($groups_id <= 0) {
    Html::back();
}

$group = new Group();

if (!$group->getFromDB($groups_id)) {
    Html::back();
}

if (isset($_POST['activate'])) {
    Session::checkRight('config', UPDATE);

    FilaCircular::activate($groups_id);

    Html::redirect($group->getFormURLWithID($groups_id));
}

if (isset($_POST['set_coordinator_management'])) {
    Session::checkRight('config', UPDATE);

    $allow = isset($_POST['allow_coordinator_management'])
        ? (int) $_POST['allow_coordinator_management']
        : 0;

    FilaCircular::setCoordinatorManagement(
        $groups_id,
        $allow
    );

    Html::redirect($group->getFormURLWithID($groups_id));
}

if (isset($_POST['deactivate'])) {
    Session::checkRight('config', UPDATE);

    FilaCircular::deactivate($groups_id);

    Html::redirect($group->getFormURLWithID($groups_id));
}

if (isset($_POST['set_participation'])) {
    $users_id = isset($_POST['users_id'])
        ? (int) $_POST['users_id']
        : 0;

    $is_active = isset($_POST['is_active'])
        ? (int) $_POST['is_active']
        : 0;

    $current_user_id = (int) Session::getLoginUserID();

    if (!\GlpiPlugin\Filacircular\GroupCoordinator::canManageParticipants(
        $groups_id,
        $current_user_id
    )) {
        Session::addMessageAfterRedirect(
            'Você não tem permissão para alterar a participação deste grupo.',
            false,
            ERROR
        );

        Html::redirect($group->getFormURLWithID($groups_id));
    }

    if ($users_id > 0) {
        $updated = \GlpiPlugin\Filacircular\GroupUser::setActive(
            $groups_id,
            $users_id,
            $is_active
        );

        if ($updated) {
            Session::addMessageAfterRedirect(
                $is_active
                    ? 'Participação ativada com sucesso.'
                    : 'Participação desativada com sucesso.',
                false,
                INFO
            );
        } else {
            Session::addMessageAfterRedirect(
                'Não foi possível alterar a participação deste técnico.',
                false,
                ERROR
            );
        }
    }

    Html::redirect($group->getFormURLWithID($groups_id));
}

if (isset($_POST['add_coordinator'])) {
    
    $users_id = isset($_POST['users_id'])
        ? (int) $_POST['users_id']
        : 0;

    $current_user_id = (int) Session::getLoginUserID();

    if (!\GlpiPlugin\Filacircular\GroupCoordinator::canManage(
        $groups_id,
        $current_user_id
    )) {
        Session::addMessageAfterRedirect(
            'Você não tem permissão para gerenciar os coordenadores deste grupo.',
            false,
            ERROR
        );

        Html::redirect($group->getFormURLWithID($groups_id));
    }

    if ($users_id > 0) {
        $added = \GlpiPlugin\Filacircular\GroupCoordinator::add(
            $groups_id,
            $users_id
        );

        if ($added) {
            Session::addMessageAfterRedirect(
                'Coordenador adicionado com sucesso.',
                false,
                INFO
            );
        } else {
            Session::addMessageAfterRedirect(
                'O usuário selecionado não pertence a este grupo.',
                false,
                ERROR
            );
        }
    }

    Html::redirect($group->getFormURLWithID($groups_id));
}

if (isset($_POST['remove_coordinator'])) {
    
    $users_id = isset($_POST['users_id'])
        ? (int) $_POST['users_id']
        : 0;

    $current_user_id = (int) Session::getLoginUserID();

    if (!\GlpiPlugin\Filacircular\GroupCoordinator::canManage(
        $groups_id,
        $current_user_id
    )) {
        Session::addMessageAfterRedirect(
            'Você não tem permissão para gerenciar os coordenadores deste grupo.',
            false,
            ERROR
        );

        Html::redirect($group->getFormURLWithID($groups_id));
    }

    if ($users_id > 0) {
        $removed = \GlpiPlugin\Filacircular\GroupCoordinator::remove(
            $groups_id,
            $users_id
        );

        if ($removed) {
            Session::addMessageAfterRedirect(
                'Coordenador removido com sucesso.',
                false,
                INFO
            );
        } else {
            Session::addMessageAfterRedirect(
                'Não é possível remover o último coordenador do grupo.',
                false,
                ERROR
            );
        }
    }

    Html::redirect($group->getFormURLWithID($groups_id));
}

Html::back();
