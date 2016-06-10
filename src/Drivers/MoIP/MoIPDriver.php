<?php

namespace Artesaos\Caixeiro\Drivers\MoIP;

use Artesaos\Caixeiro\Contracts\Driver\Driver;
use Artesaos\Caixeiro\Builders\CustomerBuilder;
use Artesaos\Caixeiro\Exceptions\CaixeiroException;
use Artesaos\Caixeiro\Builders\SubscriptionBuilder;
use Artesaos\MoIPSubscriptions\MoIPSubscriptions;
use Artesaos\MoIPSubscriptions\Resources\Customer;
use Artesaos\MoIPSubscriptions\Resources\Subscription as SubscriptionResource;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class MoIPDriver implements Driver
{
    /**
     * 
     */
    public function setup()
    {
        $apiToken = config('services.moip.token');
        $apiKey = config('services.moip.key');
        $production = config('services.moip.production');

        MoIPSubscriptions::setCredentials($apiToken, $apiKey, $production);
    }

    /**
     * @return array
     */
    public function bindings()
    {
        return [];
    }

    /**
     * @param $billable
     *
     * @return bool
     */
    public function cancelSubscription($billable)
    {
        $subscription = $this->findSubscription($billable);

        if ($subscription->hasErrors()) {
            $errors = $subscription->getErrors()->all();
            throw new CaixeiroException($errors[0]);
        }

        return $subscription->cancel();
    }

    /**
     * @param $billable
     *
     * @return bool
     */
    public function suspendSubscription($billable)
    {
        $subscription = $this->findSubscription($billable);

        if ($subscription->hasErrors()) {
            $errors = $subscription->getErrors()->all();
            throw new CaixeiroException($errors[0]);
        }

        return $subscription->suspend();
    }

    /**
     * @param $billable
     *
     * @return bool
     */
    public function activateSubscription($billable)
    {
        $subscription = $this->findSubscription($billable);

        if ($subscription->hasErrors()) {
            $errors = $subscription->getErrors()->all();
            throw new CaixeiroException($errors[0]);
        }

        return $subscription->activate();
    }

    /**
     * @param $billable
     *
     * @return Subscription
     */
    protected function findSubscription($billable)
    {
        /** @var Subscription $subscription */
        $subscription = SubscriptionResource::find($billable->subscription_id);

        return $subscription;
    }

    /**
     * @param CustomerBuilder $builder
     *
     * @return bool
     */
    public function prepareCustomer(CustomerBuilder $builder)
    {
        $billable = $builder->getBillable();
        
        if (!$billable->customer_id) {

            $id = md5(mt_rand(100000, 999999).microtime(true));

            $customer = new Customer();

            $customer = $this->fillCustomerFromBuilder($customer, $builder, $id);

            $customer->save();

            if ($customer->hasErrors()) {
                $errors = $customer->getErrors()->all();
                throw new CaixeiroException(json_encode($errors));
            }

            $this->saveCustomerInformation($billable, $id);

            return true;
        }

        return false;
    }

    protected function saveCustomerInformation(Model $billable, $customer_id)
    {
        $customer = Customer::find($customer_id);

        if ($customer) {
            $billable->customer_id = $customer->code;
            $billing_info = $customer->billing_info;
            if (is_array($billing_info) && array_key_exists('credit_cards', $billing_info)) {
                if (isset($billing_info['credit_cards'][0])) {
                    $billable->card_brand = $billing_info['credit_cards'][0]['brand'];
                    $billable->card_last_four = $billing_info['credit_cards'][0]['last_four_digits'];
                }
            }

            $billable->save();

            return true;
        }

        return false;
    }

    protected function fillCustomerFromBuilder(Customer $customer, CustomerBuilder $builder, $id = null)
    {
        if ($id) {
            $customer->code = $id;
        }

        if ($builder->getName()) {
            $customer->fullname = $builder->getName();
        }

        if ($builder->getEmail()) {
            $customer->email = $builder->getEmail();
        }

        if ($builder->getDocument()) {
            $customer->cpf = $builder->getDocument();
        }

        if ($builder->phoneNumberPresent()) {
            // phone number
            $phoneNumber = $builder->getPhoneNumber();
            $customer->phone_area_code = $phoneNumber['area'];
            $customer->phone_number = $phoneNumber['number'];
        }

        if ($builder->getBirthday()) {
            // birthday
            $birthday = Carbon::createFromFormat('Y-m-d', $builder->getBirthday());
            $customer->birthdate_day = $birthday->format('d');
            $customer->birthdate_month = $birthday->format('m');
            $customer->birthdate_year = $birthday->format('Y');
        }

        if ($builder->addressPresent()) {
            $address = $builder->getAddress();
            $address['zipcode'] = $address['zip'];
            unset($address['zip']);

            $customer->address = $address;
        }

        if ($builder->cardPresent() && $id) {
            $customer->billing_info = [
                'credit_card' => $builder->getCardData(),
            ];
        }

        return $customer;
    }

    /**
     * @param CustomerBuilder $builder
     * @return bool
     */
    public function updateCustomer(CustomerBuilder $builder)
    {
        $billable = $builder->getBillable();

        /** @var Customer $customer */
        $customer = Customer::find($billable->customer_id);

        if ($customer) {
            /** @var Customer $customer */
            $customer = $this->fillCustomerFromBuilder($customer, $builder);
        }

        $customer->update();

        if ($customer->hasErrors()) {
            $errors = $customer->getErrors()->all();
            throw new CaixeiroException(json_encode($errors));
        }

        if ($builder->cardPresent()) {
            $cardData = $builder->getCardData();
            $customer->updateCreditCard(
                $cardData['holder_name'],
                $cardData['number'],
                $cardData['expiration_month'],
                $cardData['expiration_year']
            );
        }

        $this->saveCustomerInformation($billable, $billable->customer_id);

        return true;
    }

    public function createSubscription(SubscriptionBuilder $builder)
    {
        $billable = $builder->getBillable();
        
        $subscription = new SubscriptionResource();

        $subscription->code = 'subs-'.$billable->id;

        $subscription->plan = [
            'code' => $builder->getPlanName(),
        ];

        $subscription->customer = [
            'code' => $billable->customer_id,
        ];

        if ($builder->hasCustomAmount()) {
            $subscription->amount = $builder->getCustomAmount();
        }

        if ($builder->hasCoupon()) {
            $subscription->coupon = [
                'code' => $builder->getCouponCode(),
            ];
        }

        if ($builder->shouldUseBankSlip()) {
            $subscription->payment_method = 'BOLETO';
        }

        $subscription->save();

        if ($subscription->hasErrors()) {
            $errors = $subscription->getErrors()->all();
            throw new CaixeiroException(json_encode($errors));
        }

        $billable->subscription_id = $subscription->code;
        $billable->save();

        return true;
    }

    /**
     * @param Model $billable
     *
     * @return SubscriptionResource|null
     */
    protected function cachedSubscription(Model $billable)
    {
        $cacheStore = app('cache');

        $subscription_id = $billable->subscription_id;

        if ($subscription_id) {
            if (!$cacheStore->has($subscription_id)) {
                $subscription = SubscriptionResource::find($subscription_id);
                if ($subscription) {
                    $cacheStore->put($subscription_id, $subscription, 10);
                }
            }

            return $cacheStore->get($subscription_id, null);
        }

        return;
    }

    public function active(Model $billable)
    {
        $subscription = $this->cachedSubscription($billable);

        if (!$subscription) {
            throw new CaixeiroException('No Subscription Found');
        }

        if ($subscription->status == 'ACTIVE' || $subscription->status == 'TRIAL') {
            return true;
        }

        return false;
    }
}
