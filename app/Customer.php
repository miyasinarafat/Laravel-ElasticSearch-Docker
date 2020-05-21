<?php

namespace App;

use Elasticquent\ElasticquentTrait;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use ElasticquentTrait;

    protected $table = 'customers';

    public function getIndexName()
    {
        return 'customer_index';
    }
}
