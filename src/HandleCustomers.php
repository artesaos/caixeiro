<?php

namespace Artesaos\Caixeiro;

use Artesaos\Caixeiro\Builders\CustomerBuilder;

trait HandleCustomers
{
    /**
     * @return CustomerBuilder
     */
    public function customerData()
    {
        return new CustomerBuilder($this);
    }
}