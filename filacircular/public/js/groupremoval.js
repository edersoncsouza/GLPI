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

        const submitForm = function () {
            const formData = new FormData(form);

            const submitFormElement = document.createElement('form');

            submitFormElement.method = 'post';
            submitFormElement.action = form.getAttribute('action');
            submitFormElement.style.display = 'none';

            for (const [name, value] of formData.entries()) {
                const input = document.createElement('input');

                input.type = 'hidden';
                input.name = name;
                input.value = value;

                submitFormElement.appendChild(input);
            }

            document.body.appendChild(submitFormElement);

            HTMLFormElement.prototype.submit.call(submitFormElement);
        };

        if (response.last_active_participant) {
            glpi_confirm({
                title: 'Confirmação',
                message:
                    'Este é o último usuário ativo no grupo ' + response.group_name +
                    '. Tem certeza que deseja remover?' +
                    '<br><br>' +
                    'OBS: Caso seja removido as demandas deste grupo ficarão paradas e sem atendimento. ' +
                    'Recomenda-se a ativação de outros usuários do grupo ' + response.group_name +
                    ' ou a inclusão de outros usuários neste grupo ' + response.group_name + '.',
                confirm_callback: function () {
                    submitForm();
                },
                cancel_callback: function () {
                    $(form).closest('.modal').find('[data-bs-dismiss="modal"]').first().trigger('click');
                }
            });

            return;
        }

        if (response.last_coordinator) {
            glpi_confirm({
                title: 'Confirmação',
                message:
                    'Este é o último Coordenador do grupo ' + response.group_name +
                    '. Tem certeza que deseja remover?' +
                    '<br><br>' +
                    'OBS: É necessário que exista pelo menos um Coordenador no grupo. ' +
                    'Sem um Coordenador, não haverá um usuário capaz de gerenciar os participantes do grupo, ' +
                    'atribuir diretamente um chamado a um usuário ou refazer a distribuição de um chamado ' +
                    'entre os técnicos ativos disponíveis. Recomenda-se que, antes de remover este usuário, ' +
                    'outro Técnico ativo seja promovido a Coordenador do grupo ' + response.group_name + '.',
                confirm_callback: function () {
                    submitForm();
                },
                cancel_callback: function () {
                    $(form).closest('.modal').find('[data-bs-dismiss="modal"]').first().trigger('click');
                }
            });

            return;
        }

        submitForm();
    }).fail(function (xhr) {
        console.error(
            'FilaCircular - erro ao verificar a remoção:',
            xhr.status,
            xhr.responseText
        );

        const formData = new FormData(form);

        const submitFormElement = document.createElement('form');

        submitFormElement.method = 'post';
        submitFormElement.action = form.getAttribute('action');
        submitFormElement.style.display = 'none';

        for (const [name, value] of formData.entries()) {
            const input = document.createElement('input');

            input.type = 'hidden';
            input.name = name;
            input.value = value;
            submitFormElement.appendChild(input);
        }

        document.body.appendChild(submitFormElement);

        HTMLFormElement.prototype.submit.call(submitFormElement);
    });
});