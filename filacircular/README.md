# FilaCircular

Plugin para GLPI 11 destinado ao gerenciamento da participação de técnicos em grupos e à distribuição circular de chamados entre os técnicos elegíveis da Prefeitura de Cachoeirinha.

## Ambiente

- GLPI: 11.0.9
- Plugin: FilaCircular 1.0.0
- Banco de dados: MySQL 8.4
- Execução: Docker
- Namespace: `GlpiPlugin\Filacircular`

## Objetivo

A FilaCircular controla a participação de usuários em grupos e a distribuição de chamados entre os participantes ativos.

A participação na FilaCircular é específica por grupo.

Um usuário pode participar de zero, um ou vários grupos.

A participação de um usuário em um grupo pode estar:

- ativa — elegível para receber chamados;
- inativa — permanece associado ao grupo, mas não participa da distribuição.

A associação nativa do usuário ao grupo continua sendo controlada pelo GLPI. A FilaCircular mantém suas próprias informações para determinar a elegibilidade do usuário no rodízio.

## Estrutura

```text
filacircular/
├── ajax/
│   └── check_group_removal.php
├── front/
│   └── filacircular.form.php
├── inc/
│   └── groupuser.class.php
├── js/
│   └── groupremoval.js
├── src/
│   ├── Assignment.php
│   ├── FilaCircular.php
│   ├── GroupCoordinator.php
│   ├── GroupRemoval.php
│   └── GroupUser.php
└── setup.php
```

## Grupos

A configuração da FilaCircular é realizada individualmente por grupo.

Cada grupo possui sua própria configuração de:

- ativação da FilaCircular;
- participantes;
- situação de participação de cada técnico;
- Coordenadores;
- permissão para gerenciamento de Coordenadores;
- e-mail de emergência;
- controle da próxima posição do rodízio.

Um técnico pode participar de zero, um ou vários grupos simultaneamente.

A participação do técnico em cada grupo é independente.

## Coordenadores

A FilaCircular possui o conceito próprio de Coordenador.

O Coordenador da FilaCircular não é o mesmo conceito de Gerente/Manager nativo do GLPI.

Um grupo pode possuir um ou mais Coordenadores.

Cada Coordenador atua somente nos grupos em que foi configurado como Coordenador.

A condição de Coordenador é independente da participação no rodízio.

A configuração **"Coordenadores podem gerenciar coordenadores"** é específica de cada grupo.

Portanto, um grupo pode permitir que seus Coordenadores gerenciem outros Coordenadores enquanto outro grupo pode não permitir essa operação.

## Ausência de Coordenador

Um grupo deve possuir pelo menos um Coordenador da FilaCircular para que existam usuários responsáveis pelas atividades administrativas específicas da FilaCircular.

Quando um grupo não possui nenhum Coordenador da FilaCircular, a tela de configuração informa que nenhum Coordenador técnico está definido e orienta que é necessário definir pelo menos um Coordenador.

Entre as atividades que dependem da existência de um Coordenador estão:

- gerenciamento dos participantes do grupo;
- atribuição direta de um chamado a um usuário;
- redistribuição de um chamado entre os técnicos ativos disponíveis no grupo.

## Remoção do último Coordenador

Quando um usuário está sendo removido de um grupo e é o último Coordenador da FilaCircular naquele grupo, o sistema apresenta uma confirmação antes de permitir a remoção.

A confirmação informa que é necessário existir um Coordenador para executar as atividades próprias da FilaCircular.

A remoção continua sendo permitida após a confirmação.

Recomenda-se que, antes de remover o último Coordenador, outro técnico seja promovido a Coordenador do grupo.

Quando o usuário é efetivamente removido da associação nativa com o grupo, sua condição de Coordenador da FilaCircular naquele grupo também é removida automaticamente.

## Participantes e associação ao grupo

A associação nativa do usuário ao grupo é controlada pelo próprio GLPI.

A FilaCircular mantém uma tabela própria para controlar a participação no rodízio.

Quando um usuário é incluído em um grupo nativo do GLPI:

- sua participação na FilaCircular é criada, caso ainda não exista;
- ou é reativada, caso já exista;
- a participação é criada/reativada como ativa.

Quando um usuário é removido da associação nativa com um grupo:

- sua participação na FilaCircular naquele grupo é removida;
- sua condição de Coordenador naquele grupo também é removida.

A remoção da associação nativa do usuário ao grupo continua sendo uma operação do GLPI.

## Ativação e desativação de participantes

A participação no rodízio pode ser desativada individualmente por grupo.

Quando um participante é desativado:

- ele continua associado ao grupo nativo do GLPI;
- permanece cadastrado como participante da FilaCircular;
- deixa de ser elegível para receber novos chamados através da distribuição circular.

A participação pode ser reativada posteriormente.

A situação de participação é independente entre os grupos.

Um técnico pode, por exemplo:

- estar ativo na FilaCircular no Grupo A;
- estar inativo na FilaCircular no Grupo B;
- e não participar da FilaCircular no Grupo C.

## Mínimo de participantes ativos

Um grupo com a FilaCircular ativa deve possuir pelo menos um participante ativo.

A FilaCircular impede a desativação do último participante ativo através da configuração própria do plugin.

A remoção da associação nativa ao grupo é uma operação diferente.

Quando a remoção nativa resultar em zero participantes ativos, aplica-se a regra específica de **Remoção do último participante ativo**.

## Distribuição

A distribuição utiliza o princípio de fila circular (Round-Robin).

Quando um chamado é direcionado a um grupo com a FilaCircular ativa, o plugin seleciona o próximo técnico elegível da fila.

O princípio utilizado é:

> o próximo chamado deve ser direcionado ao técnico elegível que está há mais tempo sem receber um chamado.

Somente participam da distribuição os técnicos que:

- pertencem ao grupo nativo do GLPI;
- possuem participação ativa na FilaCircular naquele grupo.

A distribuição é específica por grupo.

O controle da próxima posição da fila é mantido pelo plugin.

O processo de distribuição utiliza proteção contra concorrência para evitar que dois chamados simultâneos selecionem o mesmo próximo participante.

## Atribuição de chamados

A FilaCircular integra-se ao processo de criação de chamados do GLPI.

Quando um chamado é direcionado a um grupo configurado para utilizar a FilaCircular, o plugin determina o técnico elegível que deverá receber o chamado.

A lógica de distribuição permanece no plugin.

O GLPI continua responsável pelo processamento normal do chamado e pelo relacionamento do chamado com o grupo e o técnico.

## Remoção do último participante ativo

Quando um usuário está sendo removido de um grupo e é o último participante ativo da FilaCircular naquele grupo, o sistema apresenta uma confirmação antes de permitir a remoção.

A confirmação informa que o grupo ficará sem participantes ativos para a distribuição e atendimento pela FilaCircular.

O usuário pode:

- cancelar a operação, impedindo a remoção;
- confirmar a operação, permitindo que a remoção nativa do GLPI prossiga.

A verificação da condição de último participante ativo é realizada pelo backend.

O JavaScript apenas intercepta o envio da operação nativa, consulta o backend e apresenta a confirmação quando necessário.

A operação nativa do GLPI continua sendo responsável pela remoção efetiva da associação do usuário com o grupo.

## Notificação de ausência de participante ativo

Quando uma operação provocar a transição de um grupo de pelo menos 1 participante ativo para 0 participantes ativos, e a FilaCircular estiver ativa para esse grupo, será gerada uma notificação pelo mecanismo nativo de notificações do GLPI.

A notificação será enviada para:

- todos os Coordenadores cadastrados para o grupo;
- o e-mail de emergência configurado para o grupo, caso exista.

A mensagem deverá informar:

- o grupo que ficou sem participantes ativos;
- o técnico que foi removido;
- a data e hora da ocorrência;
- que o grupo está sem participantes ativos;
- que os chamados destinados ao grupo ficarão sem atendimento pela FilaCircular até que um participante ativo esteja disponível novamente.

Se o grupo já estiver com 0 participantes ativos antes da operação, nenhuma nova notificação será gerada.

## E-mail de emergência

Cada grupo pode possuir um único e-mail de emergência.

O e-mail de emergência é configurado na área administrativa da FilaCircular.

Somente usuários com permissão de administrador do GLPI podem visualizá-lo e alterá-lo.

O e-mail de emergência é utilizado como destinatário adicional das notificações de ausência de participantes ativos.

## Mecanismo de notificações

As notificações específicas da FilaCircular utilizam o mecanismo nativo de notificações do GLPI.

O plugin utiliza:

- `NotificationEvent`;
- `NotificationTarget`;
- `NotificationTemplate`;
- traduções do template;
- fila de notificações do GLPI.

O plugin não realiza envio de e-mail diretamente.

O evento de ausência de participantes ativos é disparado pelo plugin através do evento:

`last_active_removed`

O plugin fornece os dados necessários para que o mecanismo de notificações determine:

- grupo;
- técnico removido;
- data e hora;
- Coordenadores do grupo;
- e-mail de emergência configurado.

### Configuração automática

As configurações específicas de notificações necessárias ao funcionamento da FilaCircular são responsabilidade do próprio plugin.

A instalação ou atualização do plugin deve criar ou atualizar os recursos necessários para que a notificação esteja operacional.

O administrador não deve precisar configurar manualmente, para o funcionamento da FilaCircular:

- Notifications;
- Notification Templates;
- traduções dos templates;
- destinatários específicos do plugin.

A responsabilidade do GLPI pela fila e pelo envio permanece preservada.

## Automatic Actions

A FilaCircular não possui atualmente uma tarefa própria de processamento em segundo plano que exija uma Automatic Action específica do plugin.

As notificações geradas pelo FilaCircular são colocadas na fila nativa de notificações do GLPI.

O processamento dessa fila é realizado pelo mecanismo de Automatic Actions do próprio GLPI, através da ação nativa `queuednotification`.

No ambiente Docker utilizado no projeto, as Automatic Actions são executadas pelo scheduler externo do GLPI em modo CLI.

O FilaCircular não deve exigir configuração manual de Automatic Actions específica para seu funcionamento.

## SMTP

A configuração do servidor SMTP é uma configuração global de infraestrutura do GLPI e não pertence à responsabilidade do plugin FilaCircular.

Portanto, o administrador deve configurar o SMTP do GLPI antes de utilizar notificações por e-mail.

Essa configuração é realizada no próprio GLPI.

A configuração do SMTP deve contemplar:

- servidor SMTP;
- porta;
- segurança/TLS;
- autenticação;
- usuário;
- senha;
- endereço de origem;
- demais parâmetros necessários ao servidor de e-mail da infraestrutura.

O FilaCircular não armazena nem configura diretamente essas informações.

Depois que o SMTP do GLPI estiver configurado e funcionando, as notificações do FilaCircular utilizarão automaticamente a infraestrutura nativa de envio do GLPI.

## Responsabilidades do plugin e do ambiente GLPI

### Responsabilidade do FilaCircular

O plugin é responsável por:

- criar e manter suas próprias tabelas;
- controlar participantes;
- controlar a situação ativa/inativa dos participantes;
- controlar Coordenadores;
- controlar a permissão de gerenciamento de Coordenadores por grupo;
- controlar o e-mail de emergência;
- realizar a distribuição Round-Robin;
- integrar-se à criação de chamados;
- validar a remoção do último participante ativo;
- validar a remoção do último Coordenador;
- gerar eventos de notificação;
- fornecer os destinatários das notificações;
- criar e manter os recursos específicos de notificação necessários ao seu funcionamento.

### Responsabilidade do GLPI/infraestrutura

Permanecem como responsabilidades externas ao plugin:

- instalação do GLPI;
- banco de dados;
- Docker;
- configuração do SMTP;
- DNS;
- HTTPS/certificados;
- infraestrutura de rede;
- execução do scheduler/cron do GLPI.

O objetivo é que toda configuração que pertença especificamente à FilaCircular seja realizada pelo próprio plugin, evitando que o administrador precise repetir configurações manualmente após sua instalação.

## Estado atual

### Implementado e testado

- Estrutura inicial do plugin.
- Cadastro/configuração da FilaCircular por grupo.
- Participação de usuários por grupo.
- Criação automática da participação ao entrar no grupo.
- Reativação automática da participação quando aplicável.
- Ativação e desativação de participantes.
- Participação independente por grupo.
- Coordenadores por grupo.
- Múltiplos Coordenadores por grupo.
- Coordenador independente da participação no rodízio.
- Permissão específica por grupo para gerenciamento de Coordenadores.
- Distribuição Round-Robin.
- Controle da próxima posição da fila.
- Proteção contra concorrência durante a distribuição.
- Integração da distribuição com a criação de chamados.
- Interceptação da remoção nativa de `Group_User`.
- Identificação do último participante ativo.
- Confirmação antes da remoção do último participante ativo.
- Cancelamento da remoção.
- Continuação da remoção após confirmação.
- Identificação do nome do grupo na mensagem de confirmação.
- Identificação do último Coordenador.
- Confirmação antes da remoção do último Coordenador.
- Remoção automática da condição de Coordenador quando o usuário é removido do grupo nativo.
- Configuração de e-mail de emergência por grupo.
- Geração do evento `last_active_removed`.
- `NotificationTargetFilaCircular`.
- Destinação da notificação aos Coordenadores.
- Destinação da notificação ao e-mail de emergência.
- Utilização da fila nativa de notificações do GLPI.
- Envio real de notificação por e-mail testado.
- Processamento da fila de notificações pelo `queuednotification`.
- Execução das Automatic Actions em modo CLI no ambiente Docker.

## Próximos ajustes

Os próximos ajustes devem continuar sendo definidos antes da implementação.

A instalação do plugin já cria automaticamente os recursos específicos de notificação necessários à FilaCircular.

Durante uma atualização do FilaCircular, o plugin deverá verificar e corrigir automaticamente os recursos necessários de:

- `Notification`;
- `NotificationTemplate`;
- traduções dos templates;
- relações entre notificações e templates;
- destinatários específicos da FilaCircular.

Esse processo deverá:

- evitar a criação de recursos duplicados;
- preservar configurações já existentes;
- corrigir recursos ausentes ou incompletos;
- permitir a evolução dos recursos de notificação entre versões do plugin.

Entre os pontos de evolução estão:

- implementar o mecanismo de atualização do plugin;
- garantir a preservação e atualização automática dos recursos de notificação durante upgrades;
- revisar a experiência visual das confirmações das operações nativas;
- continuar os testes de instalação limpa e atualização do plugin;
- validar o comportamento completo da FilaCircular em um ambiente novo, sem configurações manuais específicas do plugin.

## Princípios de desenvolvimento

O comportamento e as regras de negócio da FilaCircular devem ser definidos antes da implementação.

A documentação deste projeto é a fonte de verdade das regras de negócio.

Alterações de código devem ser feitas de forma incremental:

1. definir a regra;
2. atualizar a documentação;
3. implementar uma alteração;
4. testar a alteração;
5. somente então realizar a próxima alteração.

Não devem ser inferidos comportamentos de negócio que não estejam definidos na documentação.

Configurações que pertencem à responsabilidade do plugin devem ser automatizadas pelo próprio plugin sempre que houver suporte técnico para isso.

Configurações que pertencem à infraestrutura ou à configuração global do GLPI devem permanecer externas ao plugin e ser documentadas com instruções claras para o administrador.

