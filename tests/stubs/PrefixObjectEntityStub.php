<?php

use Mini\Entity\Entity;
use Mini\Entity\Behaviors\QueryAware;

class PrefixObjectEntityStub extends Entity
{
    use QueryAware;

    public $table = 'posts';

    public $definition = [
        'id' => 'pk',
        'name' => 'string|required',
        'max_users_quantity' => 'integer',
        'address_geolocalization' => 'string:200',
        'address_street_name' => 'string:200',
        'address_number' => 'string:50'
    ];

    public $fillable = [
        'name',
        'max_users_quantity',
        'address_street_name',
        'address_number',
    ];

    public $prefixAsObject = ['address'];
}
