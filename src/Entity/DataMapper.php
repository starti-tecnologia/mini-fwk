<?php

namespace Mini\Entity;

use Mini\Entity\Entity;

class DataMapper
{
    public static $generatedFields = [
        'created_at', 'updated_at', 'deleted_at'
    ];

    /**
     * @return Mini\Entity\Connection
     */
    protected function getConnection($connectionName)
    {
        return app()
            ->get('Mini\Entity\ConnectionManager')
            ->getConnection($connectionName);
    }

    protected function onBeforeSave(Entity $entity)
    {
        // Hook for inherited classes
    }

    protected function onAfterSave(Entity $entity)
    {
        // Hook for inherited classes
    }

    /**
     * Saves an entity
     */
    public function save(Entity $entity)
    {
        $this->onBeforeSave($entity);

        if (isset($entity->fields[$entity->idAttribute])) {
            $this->update($entity);
        } else {
            $this->create($entity);
        }

        $this->onAfterSave($entity);

        return $entity;
    }

    protected function onBeforeCreate(Entity $entity)
    {
        // Hook for inherited classes
    }

    protected function onAfterCreate(Entity $entity)
    {
        // Hook for inherited classes
    }

    /**
     * Insert entity to database
     */
    protected function create(Entity $entity)
    {
        $this->onBeforeCreate($entity);
        $connection = $this->getConnection($entity->connection);
        $fields = array_only($entity->fields, array_merge(array_keys($entity->definition), self::$generatedFields));
        if ($entity->useTimeStamps) {
            $fields['created_at'] = new RawValue('NOW()');
        }
        $connection->insert($entity->table, $fields);
        $entity->fields[$entity->idAttribute] = $connection->lastInsertId();
        $this->onAfterCreate($entity);
    }

    /**
     * Insert entity to database
     */
    public function createOrUpdate(Entity $entity, array $ignoredUpdates = [])
    {
        $this->onBeforeCreate($entity);
        $connection = $this->getConnection($entity->connection);
        $fields = array_only($entity->fields, array_merge(array_keys($entity->definition), self::$generatedFields));
        if ($entity->useTimeStamps) {
            $fields['created_at'] = new RawValue('NOW()');
        }
        $connection->insertOrUpdate($entity->table, $fields, array_merge(['created_at'], $ignoredUpdates));
        $entity->fields[$entity->idAttribute] = $connection->lastInsertId();
        $this->onAfterCreate($entity);
    }

    protected function onBeforeUpdate(Entity $entity)
    {
        // Hook for inherited classes
    }

    protected function onAfterUpdate(Entity $entity)
    {
        // Hook for inherited classes
    }

    /**
     * Update entity from database
     */
    protected function update(Entity $entity)
    {
        $this->onBeforeUpdate($entity);
        $updates = array_only($entity->fields, array_merge(array_keys($entity->definition), self::$generatedFields));
        unset($updates[$entity->idAttribute]);
        $where = [ $entity->idAttribute => $entity->{$entity->idAttribute} ];
        if ($entity->useTimeStamps) {
            $updates['updated_at'] = new RawValue('NOW()');
        }
        $this->getConnection($entity->connection)->update($entity->table, $updates, $where);
        $this->onAfterUpdate($entity);
    }

    /**
     * Delete entity from database
     */
    public function delete(Entity $entity)
    {
        $connection = $this->getConnection($entity->connection);
        $where = [ $entity->idAttribute => $entity->{$entity->idAttribute} ];
        $this->deleteByFilters($entity, $where);
    }

    /**
     * Delete multiple entities from database
     *
     * Example:
     *     $mapper->deleteByFilters(new Entity, ['guid' => 'something']);
     *
     * @param Entity $entity A entity example, there is no need for a entity loaded from the database
     * @param array $where Filter updated rows
     */
    public function deleteByFilters(Entity $entity, array $where)
    {
        $connection = $this->getConnection($entity->connection);

        if ($entity->useSoftDeletes) {
            $connection->update($entity->table, [
                'deleted_at' => new RawValue('NOW()')
            ], $where);
        } else {
            $connection->delete($entity->table, $where);
        }
    }

    /**
     * Update multiple entities from database
     *
     * Example:
     *     $mapper->updateByFilters(new Entity, ['is_draft' => 0], ['guid' => 'something']);
     *
     * @param Entity $entity A entity example, there is no need for a entity loaded from the database
     * @param array $updates Values to be updated
     * @param array $where Filter updated rows
     */
    public function updateByFilters(Entity $entity, array $updates, array $where)
    {
        $connection = $this->getConnection($entity->connection);

        if ($entity->useTimeStamps) {
            $updates['updated_at'] = new RawValue('NOW()');
        }

        $connection->update($entity->table, $updates, $where);
    }
}
