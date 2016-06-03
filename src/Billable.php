<?php

namespace Artesaos\Caixeiro;

use Artesaos\Caixeiro\Contracts\Driver\Driver;

trait Billable
{
    /**
     * @var Driver
     */
    protected $caixeiroDriver;

    /**
     * Billable constructor.
     * 
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->caixeiroDriver = app('caixeiro.driver');
    }

    /**
     * @return CustomerBuilder
     */
    public function prepareCustomer()
    {
        return new CustomerBuilder($this);
    }

    public function updateCustomerDetails()
    {
        return $this->caixeiroDriver->updateCustomerDetails($this);
    }

    public function newSubscription($planCode)
    {
        return new SubscriptionBuilder($this, $planCode);
    }

    public function suspendSubscription()
    {
        return $this->caixeiroDriver->suspendSubscription($this);
    }

    public function activateSubscription()
    {
        return $this->caixeiroDriver->activateSubscription($this);
    }

    public function cancelSubscription()
    {
        return $this->caixeiroDriver->cancelSubscription($this);
    }
}
