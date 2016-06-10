# TRABALHO EM PROGRESSO - Artesaos Caixeiro

**Caixiero** é uma solução de Pagamentos Recorrentes (Assinaturas) desenvolvida para a comunidade brasileira que tem regras especiais de pagamento e gateways de pagamentos extremamente diferentes.

A ideia desse projeto é oferecer uma API simplificada, onde seja fácil a implementação de pagamentos recorrentes com o seu gateway de preferencia.

Planejamento de Suporte aos Gateways:

| Gateway   | Status | Observações |
|---|---|---|
| **MoIP.com.br** | **Em Andamento** | Suporte Integral |
| **Iugu.com** | Planejado | * Não Suporta Cupons |
| **Pagar.me** | Planejado | - |


## Instalação

A Instalação do Caixeiro é simples, basta usar o composer para adicionar o Caixeiro a seu projeto Laravel.

```
composer require artesaos/caixeiro
```

Também é necessário adicionar a dependência de seu gateway de pagamentos.

##### MoIP

```
composer require artesaos/moip-subscriptions
```

## Configuração

Cada driver terá seus campos um pouco distintos, pois alguns não necessitam alguns dados, porem os mesmos são opcionais, vamos abordar cada driver separadamente para que seja possível melhor aproveitamento.

### MoIP

#### Migrações
Geralmente, o Model que representa um assinante em seu sistema será o model `User` porem algumas aplicações podem ter as assinaturas baseadas no `Tenant` ou `Empresa`, adicione os seguinte campos a migração que cria a tabela que representa a entidade que representará o assinante:

Infelizmente o MoIP Requer muitas informações de forma obrigatória, e como os dados da conta podem ser diferentes dos dados do cartão, criaremos campos separados para armazenar essas informações.

```php

// Os seguintes campos são de uso do Caixeiro
$table->string('customer_id')->nullable();
$table->string('subscription_id')->nullable();
$table->string('card_brand')->nullable();
$table->string('card_last_four')->nullable();
$table->string('plan_code')->nullable();
$table->string('plan_name')->nullable();
$table->integer('amount')->nullable();
$table->string('status')->nullable();
$table->date('expires_at')->nullable();
$table->date('trial_expires_at')->nullable();
$table->text('subscription_metadata')->nullable();
```


#### Ambiente
Para que o Caixeiro inicie corretamente o cliente do MoIP e reconheça o modelo adequado, você deve adicionar ao seu arquivo **`config/services.php`** as seguintes configurações.

```php

'caixeiro' => [
    'model' => env('CAIXEIRO_MODEL', App\User::class),
    'driver' => env('CAIXEIRO_DRIVER', 'moip'),
],
    
'moip'  =>  [
    'token' =>  env('MOIP_API_TOKEN', null),
    'key' =>  env('MOIP_API_KEY', null),
    'production' => env('MOIP_PRODUCTION', false),
],

```

Veja que no exemplo, as informações estão sendo buscadas no arquivo .env da sua aplicação, o que pode ser feito da seguinte forma:

```ini
CAIXEIRO_MODEL=App\User
CAIXEIRO_DRIVER=moip
MOIP_API_TOKEN=ABCDEFGHIJKLMNOPQRSTUVYXWZ
MOIP_API_KEY=ABCDEFGHIJKLMNOPQRSTUVYXWZ0123456789
MOIP_PRODUCTION=false
```

#### Service Provider
É claro, como é de praxe em pacotes para Laravel, você precisa registar o service provider do Caixeiro:

```php
Artesaos\Caixeiro\CaixeiroServiceProvider::class,
```

#### Configurando o Model

Após setar as chaves e demais informações, você deve utilizar a **trait** `Artesaos\Caixeiro\Billable` em seu model que representa o assinante (Geralmente `User`)

```php
<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Artesaos\Caixeiro\Billable;

class User extends Authenticatable
{
   use Billable;
}
```

## Uso

### Aviso
O Gerenciamento dos **dados do usuário**, tais como endereço, CPF, telefone e data de nascimento ficam a cargo da sua aplicação, nem todos os drivers requerem essa informação, mas você fica responsável por atualizar essas informações.

### Planos e Cupons
Para simplificar, não criamos interfaces para gerenciamento de planos ou cupons, justamente pelo motivo de que tais informações são facilmente configuráveis no painel do seu gateway.

A API do Caixeiro oferecerá opções de utilização dos mesmos, não de gerenciamento.

### Clientes

#### Criando ou Alterando um Cliente
Antes de criar uma assinatura, precisamos criar um cliente junto ao gateway, passar um cartão de crétito não é obrigatório caso deseje criar posteriormente uma assinatura via boleto bancário.

```php

/**
 * A variável $user simboliza o cenário comum onde
 * a assinatura é ligada a um usuáro.
 * Qualquer model que use a Trait Billable
 * pode ser usada.
 * /

$user->customerData()
	// Nome Completo
    ->withName($user->name)
	// Email
    ->withEmail($user->email)
	// Nascimento
    ->withBirthday('1990-02-11')
	// Rua, Numero, Complemento
	// Bairro, Cidade, Estado
	// Pais, CEP
    ->withAddress(
        'Rua Linux Torvals', '42', 'A',
        'LinuxLandia', 'São Paulo', 'São Paulo',
        'BRA', '12345-000')
    // CPF
    ->withDocument('12345678909')
	// Telefone
    ->withPhoneNumber('11', '988887777')
	// Caso a API Use Dados do Cartão de Crédito Diretamente
    ->withCreditCard(
        'Linus Torvalds',
        '4111111111111111',
        '12', '17', '789'
    )
    // Caso a deseje passar um TOKEN gerado via
    // JS ao inves dos dados do cartão
    // nesse caso o método acima não deve ser usado.
    ->withCreditCardToken('7bcbb4ae59be06caf9966470c68f250e')
    // Finalmente cria o assinante
    ->save();

```

Para alteração, a API é a mesma, todos os métodos disponíveis na criação também podem ser usados na alteração (cada gateway tem suas regras de atualização, um manual de uso mais detalhado será criado em breve) porem deve se chamar o método `update()` após setar os dados.

```php

$user->customerData()
	// Alterando o Telefone
    ->withPhoneNumber('33', '544443333')
	// Trocando o cartão de crédito
    ->withCreditCard(
        'Linus Torvalds',
        '4222222222222222',
        '02', '22', '743'
    )
    // e finalmente o update
    ->update();

```


### Assinaturas

A crianção de assinaturas é bem simples, tenha em mente que a etapa anterior de preparo do cliente é obrigatória antes da criação da assinatura.

#### Criando uma assinatura

```php

// Criando uma assinatura no plano 'plano-basico'
// e setando o método de pagamento como boleto bancário
$user->newSubscription('plano-basico')
	->withBankSlip()
	->create();

// Caso queira sobrescrever o valor da assinatura para 120 reais
$user->newSubscription('plano-basico')
	->withCustomAmount(12000)
	->crete();
	
// Caso o cliente tenha informado um cupon de desconto
$user->newSubscription('plano-basico')
	->withCoupon('LARAVEL50')
	->create();
	
// As opções também podem ser combinadas:
$user->newSubscription('plano-basico')
	->withCustomAmount(12000)
	->withCoupon('LARAVEL50')
	->withBankSlip()
	->create();

```

#### Suspendendo, Ativando e Cancelando Assinaturas

```php

// Suspender
$user->suspendSubscription();

// (Re)ativar 
$user->activateSubscription();

// Cancelar
$user->cancelSubscription();

```









