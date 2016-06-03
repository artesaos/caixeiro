# TRABALHO EM PROGRESSO - Artesaos Caixeiro

**Caixiero** é uma solução de Pagamentos Recorrentes (Assinaturas) desenvolvida para a comunidade brasileira que tem regras especiais de pagamento e gateways de pagamentos extremamente diferentes.

A ideia desse projeto é oferecer uma API simplificada, onde seja fácil a implementação de pagamentos recorrentes com o seu gateway de preferencia.

Planejamento de Suporte aos Gateways:

| Gateway   | Status | Observações |
|---|---|---|
| **MoIP.com.br** | **Em Andamento** | Suporte Integral |
| **Pagar.me** | Planejado | - |
| **Iugu.com** | Planejado | - |

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

// Os seguinte campos são usados para passar informações
// Para a API do MoIP, então podem tanto ser criados na
// Tabela quanto via accessor, caso seus campos já existam
// com outro nome que não os da convenção abaixo
$table->string('document')->nullable();
$table->date('birthday')->nullable();
$table->string('phone_area_code')->nullable();
$table->string('phone_number')->nullable();
$table->string('full_name')->nullable();
$table->string('address_street')->nullable();
$table->string('address_number')->nullable();
$table->string('address_complement')->nullable();
$table->string('address_district')->nullable();
$table->string('address_city')->nullable();
$table->string('address_state')->nullable();
$table->string('address_country')->nullable()->default('BRA');
$table->string('address_zip')->nullable();

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

#### Criando um Cliente
Antes de criar uma assinatura, precisamos criar um cliente junto ao gateway, passar um cartão de crétito não é obrigatório caso deseje criar posteriormente uma assinatura via boleto bancário.

```php

// Com Cartão de Crédito
$user->prepareCustomer()
	->withCreditCard('Diego Hernandes', '4111111111111111', 12, 18)
	->save();
	
// Sem Cartão de Crédito
$user->prepareCustomer()->save();	

```

#### Atualizando um cliente
A qualquer momento, caso o cliente tenha atualizado o endereço ou ainda o telefone, é interessante atualizarmos as informações junto ao gateway de pagamento, para fazer isso, basta executar:

```php

$user->updateCustomerDetails();

```

#### Alterando o Cartão de um Cliente

@todo


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









