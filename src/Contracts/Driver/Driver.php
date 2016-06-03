<?php

namespace Artesaos\Caixeiro\Contracts\Driver;

use Artesaos\Caixeiro\CustomerBuilder;
use Artesaos\Caixeiro\SubscriptionBuilder;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface Driver.
 */
interface Driver
{
    /**
     *
     */
    public function setup();

    /**
     * @return array
     */
    public function bindings();

    /**
     * @param $billable
     *
     * @return bool
     */
    public function cancelSubscription($billable);

    /**
     * @param $billable
     *
     * @return bool
     */
    public function suspendSubscription($billable);

    /**
     * @param $billable
     *
     * @return bool
     */
    public function activateSubscription($billable);

    /**
     * @param Model           $billable
     * @param CustomerBuilder $builder
     *
     * @return bool
     */
    public function prepareCustomer(Model $billable, CustomerBuilder $builder);

    /**
     * @param Model               $billable
     * @param SubscriptionBuilder $builder
     *
     * @return mixed
     */
    public function createSubscription(Model $billable, SubscriptionBuilder $builder);

    /**
     * @param Model $billable
     *
     * @return mixed
     */
    public function updateCustomerDetails(Model $billable);
}
