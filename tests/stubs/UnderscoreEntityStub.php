<?php

use Mini\Entity\Entity;
use Mini\Entity\Behaviors\QueryAware;

class UnderscoreEntityStub extends Entity
{
    use QueryAware;

    public $table = 'users';

    public $definition = [
        'id' => 'pk',
        'name' => 'string',
        'government_establishment_id' => 'string',
        'business_name' => 'string|required',
        'is_anvisa' => 'boolean|default:0|required',
        'color' => 'string:7|required|default:#E05455',
        'address_street_name' => 'string:200',
        'address_latitude' => 'double',
    ];

    public $fillable = [
        '*'
    ];
}
