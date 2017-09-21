<?php

use Mini\Entity\DataMapper;
use Mini\Entity\Entity;

class DataMapperStub extends DataMapper
{
    public $entityClass = 'EntityStub';

    public $calledHooks = [];

    protected function onBeforeSave(Entity $entity)
    {
        $this->calledHooks[] = 'before_save';
    }

    protected function onAfterSave(Entity $entity)
    {
        $this->calledHooks[] = 'after_save';
    }

    protected function onBeforeCreate(Entity $entity)
    {
        $this->calledHooks[] = 'before_create';
    }

    protected function onAfterCreate(Entity $entity)
    {
        $this->calledHooks[] = 'after_create';
    }

    protected function onBeforeDelete(Entity $entity)
    {
        $this->calledHooks[] = 'before_delete';
    }

    protected function onAfterDelete(Entity $entity)
    {
        $this->calledHooks[] = 'after_delete';
    }
}
