<?php

namespace Artesaos\Caixeiro\Drivers\MoIP;

use Artesaos\Caixeiro\Contracts\Driver\Driver;
use Artesaos\Caixeiro\CustomerBuilder;
use Artesaos\Caixeiro\Exceptions\CaixeiroException;
use Artesaos\Caixeiro\SubscriptionBuilder;
use Artesaos\MoIPSubscriptions\MoIPSubscriptions;
use Artesaos\MoIPSubscriptions\Resources\Customer;
use Artesaos\MoIPSubscriptions\Resources\Subscription;
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
        $subscription = Subscription::find($billable->subscription_id);

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
            $customer = new Customer();

            $customer->code = 'customer-'.$billable->id;
            $customer->email = $billable->email;
            $customer->fullname = $billable->full_name;
            $customer->cpf = $billable->document;
            $customer->phone_area_code = $billable->phone_area_code;
            $customer->phone_number = $billable->phone_number;

            $bithday = Carbon::createFromFormat('Y-m-d', $billable->birthday);

            $customer->birthdate_day = $bithday->format('d');
            $customer->birthdate_month = $bithday->format('m');
            $customer->birthdate_year = $bithday->format('Y');

            $customer->address = [
                'street' => $billable->address_street,
                'number' => $billable->address_number,
                'complement' => $billable->address_complement,
                'district' => $billable->address_district,
                'city' => $billable->address_city,
                'state' => $billable->address_state,
                'country' => $billable->address_country,
                'zipcode' => $billable->address_zip,
            ];

            if ($builder->cardPresent()) {
                $customer->billing_info = [
                    'credit_card' => $builder->getCardData(),
                ];
            }

            $customer->save();

            if ($customer->hasErrors()) {
                $errors = $customer->getErrors()->all();
                throw new CaixeiroException(json_encode($errors));
            }

            $customer = Customer::find('customer-'.$billable->id);

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
        }

        return false;
    }

    /**
     * @param Model $billable
     *
     * @return bool
     */
    public function updateCustomerDetails(Model $billable)
    {
        $customer = Customer::find($billable->customer_id);

        $customer->email = $billable->email;
        $customer->fullname = $billable->full_name;
        $customer->phone_area_code = $billable->phone_area_code;
        $customer->phone_number = $billable->phone_number;

        $bithday = Carbon::createFromFormat('Y-m-d', $billable->birthday);

        $customer->birthdate_day = $bithday->format('d');
        $customer->birthdate_month = $bithday->format('m');
        $customer->birthdate_year = $bithday->format('Y');

        $customer->address = [
            'street' => $billable->address_street,
            'number' => $billable->address_number,
            'complement' => $billable->address_complement,
            'district' => $billable->address_district,
            'city' => $billable->address_city,
            'state' => $billable->address_state,
            'country' => $billable->address_country,
            'zipcode' => $billable->address_zip,
        ];

        $customer->update();

        if ($customer->hasErrors()) {
            $errors = $customer->getErrors()->all();
            throw new CaixeiroException(json_encode($errors));
        }

        return true;
    }

    public function createSubscription(Model $billable, SubscriptionBuilder $builder)
    {
        $subscription = new Subscription();

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
     * @return Subscription|null
     */
    protected function cachedSubscription(Model $billable)
    {
        $cacheStore = app('cache');

        $subscription_id = $billable->subscription_id;

        if ($subscription_id) {
            if (!$cacheStore->has($subscription_id)) {
                $subscription = Subscription::find($subscription_id);
                if ($subscription) {
                    $cacheStore->put($subscription_id, $subscription, 10);
                }
            }

            return $cacheStore->get($subscription_id, null);
        }

        return null;
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
