<?php

namespace Artesaos\Caixeiro;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $table = 'subscriptions';
    
    public function billable()
    {
        $billableModel = app('caixeiro.model');
        $billableModelClass = get_class($billableModel);
        
        return $this->belongsTo($billableModelClass, 'billable_id');
        
    }
}