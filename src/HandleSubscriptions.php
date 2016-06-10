<?php

namespace Artesaos\Caixeiro;

use Artesaos\Caixeiro\Builders\SubscriptionBuilder;

trait HandleSubscriptions
{
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