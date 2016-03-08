<?php

namespace Mini\Entity;

use Mini\Entity\Entity;

class DataMapper
{
    /**
     * @var Mini\Entity\Connection[]
     */
    public static $connectionMap = [];

    /**
     * @return Mini\Entity\Connection
     */
    protected function getConnection($connectionName)
    {
        if (! isset(self::$connectionMap[$connectionName])) {
            self::$connectionMap[$connectionName] = app()
                ->get('Mini\Entity\ConnectionManager')
                ->getConnection($connectionName);
        }

        return self::$connectionMap[$connectionName];
    }

    /**
     * Saves an entity
     */
    public function save(Entity $entity)
    {
        if (isset($entity->fields[$entity->idAttribute])) {
            $this->update($entity);
        } else {
            $this->create($entity);
        }
        return $entity;
    }

    /**
     * Insert entity to database
     */
    protected function create(Entity $entity)
    {
        $connection = $this->getConnection($entity->connection);
        $fields = $entity->fields;
        if ($entity->useTimeStamps) {
            $fields['created_at'] = new RawValue('NOW()');
        }
        $connection->insert($entity->table, $fields);
        $entity->fields[$entity->idAttribute] = $connection->lastInsertId();
    }

    /**
     * Update entity from database
     */
    protected function update(Entity $entity)
    {
        $updates = $entity->fields;
        unset($updates[$entity->idAttribute]);
        $where = [ $entity->idAttribute => $entity->{$entity->idAttribute} ];
        if ($entity->useTimeStamps) {
            $updates['updated_at'] = new RawValue('NOW()');
        }
        $this->getConnection($entity->connection)->update($entity->table, $updates, $where);
    }

    /**
     * Delete entity from database
     */
    public function delete(Entity $entity)
    {
        $connection = $this->getConnection($entity->connection);
        $updates = $entity->fields;
        $where = [ $entity->idAttribute => $entity->{$entity->idAttribute} ];
        $this->deleteByFilters($entity, $where);
    }

    /**
     * Delete entity from database
     */
    public function deleteByFilters(Entity $entity, array $where)
    {
        $connection = $this->getConnection($entity->connection);
        $updates = $entity->fields;

        if ($entity->useSoftDeletes) {
            $connection->update($entity->table, [
                'deleted_at' => new RawValue('NOW()')
            ], $where);
        } else {
            $connection->delete($entity->table, $where);
        }
    }
}
