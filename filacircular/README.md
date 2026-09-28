# FilaCircular

Plugin para GLPI 11 destinado ao gerenciamento de distribuição de chamados entre técnicos de grupos da Prefeitura de Cachoeirinha.

## Ambiente

- GLPI: 11.0.3
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

## Estrutura

```text
filacircular/
├── ajax/
│   └── check_group_removal.php
├── front/
│   └── filacircular.form.php
├── inc/
│   └── groupuser.class.php
├── public/
│   └── js/
│       └── groupremoval.js
├── src/
│   ├── Assignment.php
│   ├── FilaCircular.php
│   ├── GroupCoordinator.php
│   ├── GroupRemoval.php
│   └── GroupUser.php
└── setup.php
````

## Coordenadores

A FilaCircular possui o conceito próprio de Coordenador.

O Coordenador da FilaCircular não é o mesmo conceito de Gerente/Manager nativo do GLPI.

Um grupo pode possuir um ou mais Coordenadores.

Cada Coordenador atua somente nos grupos em que foi configurado como Coordenador.

A configuração **"Coordenadores podem gerenciar coordenadores"** é específica de cada grupo.

## Remoção do último Coordenador

Quando um usuário está sendo removido de um grupo e é o último Coordenador da FilaCircular naquele grupo, o sistema apresenta uma confirmação antes de permitir a remoção.

A confirmação informa que é necessário existir um Coordenador para executar as atividades próprias da FilaCircular.

Sem um Coordenador, não haverá um usuário capaz de:

- gerenciar os participantes do grupo;
- atribuir diretamente um chamado a um usuário;
- refazer a distribuição de um chamado entre os técnicos ativos disponíveis no grupo.

Recomenda-se que, antes de remover o último Coordenador, outro Técnico ativo seja promovido a Coordenador do grupo.

A remoção continua sendo permitida após a confirmação.

### Ausência de Coordenador

Quando um grupo não possui nenhum Coordenador da FilaCircular, a tela de configuração informa que nenhum Coordenador técnico está definido e orienta que é necessário definir pelo menos um Coordenador para que seja possível gerenciar os participantes, atribuir chamados diretamente e refazer a distribuição de chamados entre os técnicos ativos.


## Distribuição

A distribuição utiliza o princípio de fila circular (Round-Robin).

O próximo chamado deve ser direcionado ao técnico elegível que está há mais tempo sem receber um chamado.

A participação no rodízio pode ser desativada individualmente por grupo, sem remover o usuário da associação com o grupo.

## Participantes e associação ao grupo

A associação nativa do usuário ao grupo é controlada pelo próprio GLPI.

A FilaCircular mantém uma tabela própria para controlar a participação no rodízio.

Quando um usuário é incluído em um grupo, sua participação na FilaCircular é criada ou reativada como ativa.

A remoção do usuário do grupo nativo do GLPI continua sendo permitida.

Quando um usuário é removido da associação nativa com um grupo, sua participação como Coordenador da FilaCircular naquele grupo também é removida automaticamente.

## Remoção do último participante ativo

Quando um usuário está sendo removido de um grupo e é o último participante ativo da FilaCircular naquele grupo, o sistema apresenta uma confirmação antes de permitir a remoção.

A confirmação informa que as demandas do grupo ficarão sem distribuição e sem atendimento.

O usuário pode:

- cancelar a operação, impedindo a remoção;
- confirmar a operação, permitindo que a remoção nativa do GLPI prossiga.

A verificação é realizada pelo backend.

O JavaScript apenas intercepta o envio da operação nativa, consulta o backend e apresenta a confirmação quando necessário.

### E-mail de emergência

Cada grupo pode possuir um único e-mail de emergência.

O e-mail de emergência é configurado na área administrativa da FilaCircular e somente usuários com permissão de administrador do GLPI podem visualizá-lo e alterá-lo.

Quando um grupo ficar sem participante ativo, a notificação será enviada para todos os Coordenadores do grupo e para o e-mail de emergência configurado para aquele grupo.

## Estado atual

### Implementado e testado

- Estrutura inicial do plugin.
- Cadastro/configuração da FilaCircular por grupo.
- Participação de usuários por grupo.
- Ativação e desativação de participantes.
- Coordenadores por grupo.
- Permissão específica por grupo para gerenciamento de coordenadores.
- Distribuição Round-Robin.
- Interceptação da remoção nativa de `Group_User`.
- Identificação do último participante ativo.
- Confirmação antes da remoção do último participante ativo.
- Cancelamento da remoção.
- Continuação da remoção após confirmação.
- Identificação do nome do grupo na mensagem de confirmação.

### Próximos ajustes

- Fazer o cancelamento retornar diretamente à tela anterior, em vez de permanecer na janela de ações.
- Substituir o `window.confirm()` por uma confirmação visual integrada ao padrão do GLPI.
- Definir e implementar a regra para o Coordenador quando o usuário é removido do grupo nativo.
- Implementar posteriormente a notificação aos Coordenadores e ao e-mail de emergência quando um grupo ficar sem participante ativo.

## Observação

O comportamento e as regras de negócio da FilaCircular devem ser definidos antes da implementação. Alterações de código devem ser feitas de forma incremental e testadas individualmente.