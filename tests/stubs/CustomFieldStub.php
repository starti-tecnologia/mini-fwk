<?php

use Mini\Entity\Entity;
use Mini\Entity\Behaviors\QueryAware;

class CustomFieldStub extends Entity
{
    use QueryAware;

    public $table = 'users';

    public $definition = [
        'id' => 'pk',
        'name' => 'string:100'
    ];

    /**
     * @var boolean
     */
    public $useSoftDeletes = true;

    /**
     * @var boolean
     */
    public $useTimeStamps = true;

    /**
     * @var string
     */
    public $createdAttribute = 'data_cadastro';

    /**
     * @var string
     */
    public $updatedAttribute = 'data_atualizacao';

    /**
     * Set the updated attribute to be set when creating
     *
     * @var string
     */
    public $updatedAttributeRequired = true;

    /**
     * @var string
     */
    public $deletedAttribute = 'inativo';

    /**
     * @var string
     */
    public $deletedType = 'integer';
}
