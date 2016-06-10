<?php

namespace Artesaos\Caixeiro;

use Artesaos\Caixeiro\Contracts\Driver\Driver;

trait Billable
{
    use HandleCustomers;
    use HandleSubscriptions;
    
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
    
    public function subscription()
    {
        return $this->hasOne(Subscription::class, 'billable_id');
    }
}
