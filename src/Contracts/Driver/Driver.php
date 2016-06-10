<?php

namespace Artesaos\Caixeiro\Contracts\Driver;

use Artesaos\Caixeiro\Builders\CustomerBuilder;
use Artesaos\Caixeiro\Builders\SubscriptionBuilder;
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
     * @param CustomerBuilder $builder
     *
     * @return bool
     */
    public function prepareCustomer(CustomerBuilder $builder);

    /**
     * @param SubscriptionBuilder $builder
     *
     * @return mixed
     */
    public function createSubscription(SubscriptionBuilder $builder);

    /**
     * @param CustomerBuilder $billable
     * @return mixed
     */
    public function updateCustomer(CustomerBuilder $billable);
}
