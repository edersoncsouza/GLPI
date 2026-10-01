<?php

namespace GlpiPlugin\Filacircular;

use Notification;
use NotificationTarget;

class NotificationTargetFilaCircular extends NotificationTarget
{
    private const TARGET_RECIPIENTS = 1000;

    public function getEvents()
    {
        return [
            'last_active_removed'      => 'Último técnico ativo removido do grupo',
            'last_coordinator_removed' => 'Último Coordenador removido do grupo',
        ];
    }

    public function getTags()
    {
        $tags_all = [
            'filacircular.group'    => 'Grupo',
            'filacircular.user'     => 'Técnico removido',
            'filacircular.datetime' => 'Data e hora',
        ];

        foreach ($tags_all as $tag => $label) {
            $this->addTagToList([
                'tag'   => $tag,
                'label' => $label,
                'value' => true,
            ]);
        }

        asort($this->tag_descriptions);
    }

    public function addAdditionalTargets($event = '')
    {
        error_log('FilaCircular addAdditionalTargets executado: ' . $event);

        $this->addTarget(
            self::TARGET_RECIPIENTS,
            'Coordenadores e e-mail de emergência'
        );

        error_log(
            'FilaCircular targets: ' . print_r($this->notification_targets, true)
        );
    }

    public function addSpecificTargets($data, $options)
    {
        if (
            $data['type'] !== Notification::USER_TYPE
            || (int) $data['items_id'] !== self::TARGET_RECIPIENTS
        ) {
            return;
        }

        global $DB, $CFG_GLPI;

        if (
            !isset($options['groups_id'])
            || empty($options['groups_id'])
        ) {
            return;
        }

        $groups_id = (int) $options['groups_id'];

        if ($this->raiseevent !== 'last_coordinator_removed') {
            $result = $DB->request([
                'FROM' => 'glpi_plugin_filacircular_group_coordinators',
                'WHERE' => [
                    'groups_id' => $groups_id
                ]
            ]);

            $user = new \User();

            foreach ($result as $row) {
                $users_id = (int) $row['users_id'];

                if ($user->getFromDB($users_id)) {
                    $this->addToRecipientsList([
                        'language' => $user->getField('language'),
                        'users_id' => $user->getField('id'),
                    ]);
                }
            }
        }

        $result = $DB->request([
            'SELECT' => 'emergency_email',
            'FROM'   => 'glpi_plugin_filacircular_rr_groups',
            'WHERE'  => [
                'groups_id' => $groups_id
            ],
            'LIMIT' => 1
        ]);

        foreach ($result as $row) {
            $email = trim($row['emergency_email'] ?? '');

            if ($email !== '') {
                $this->addToRecipientsList([
                    'email'    => $email,
                    'name'     => 'E-mail de emergência',
                    'language' => $CFG_GLPI['language'],
                    'usertype' => \NotificationTarget::ANONYMOUS_USER,
                ]);
            }

            break;
        }
    }

    public function addDataForTemplate($event, $options = [])
    {
        $this->data['##filacircular.group##'] = '';
        $this->data['##filacircular.user##'] = '';
        $this->data['##filacircular.datetime##'] = '';

        if (!isset($options['groups_id']) || !isset($options['users_id'])) {
            return;
        }

        $group = new \Group();
        $user = new \User();

        $groups_id = (int) $options['groups_id'];
        $users_id = (int) $options['users_id'];

        if ($group->getFromDB($groups_id)) {
            $this->data['##filacircular.group##'] = $group->getName();
        }

        if ($user->getFromDB($users_id)) {
            $this->data['##filacircular.user##'] = $user->getName();
        }

        $datetime = $options['date_time'] ?? '';

        $this->data['##filacircular.datetime##'] = $datetime;
    }
}