$(document).on('submit', 'form[action="/front/massiveaction.php"]', function (event) {
    const form = this;

    if (
        $(form).find('input[name="action"][value="purge"]').length === 0 ||
        $(form).find('input[name="processor"][value="MassiveAction"]').length === 0
    ) {
        return;
    }

    const groupUserInput = $(form).find('input[name^="items[Group_User]"]').first();

    if (groupUserInput.length === 0) {
        return;
    }

    const groupUserId = groupUserInput.val();

    event.preventDefault();

    $.ajax({
        url: '/plugins/filacircular/ajax/check_group_removal.php',
        method: 'POST',
        dataType: 'json',
        data: {
            group_user_id: groupUserId
        }
    }).done(function (response) {
        if (response.last_active_participant) {
            const confirmed = window.confirm(
                'Este é o último usuário ativo no grupo ' + response.group_name +
                '. Tem certeza que deseja remover?\n\n' +
                'OBS: Caso seja removido as demandas deste grupo ficarão paradas e sem atendimento. ' +
                'Recomenda-se a ativação de outros usuários do grupo ' + response.group_name +
                ' ou a inclusão de outros usuários neste grupo ' + response.group_name + '.'
            );

            if (!confirmed) {
                $(form).closest('.modal').find('[data-bs-dismiss="modal"]').first().trigger('click');
                return;
            }
        }

        form.submit();
    }).fail(function (xhr) {
        console.error(
            'FilaCircular - erro ao verificar a remoção:',
            xhr.status,
            xhr.responseText
        );

        form.submit();
    });
});