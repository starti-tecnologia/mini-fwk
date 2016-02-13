<?php

namespace Mini\Entity;

use Mini\Exceptions\MiniException;

/**
 * Class Mapper
 * @package Mini\Entity
 */
class Mapper extends Model
{
    /**
     * @var
     */
    private $fields;

    /**
     * @var
     */
    private $entity;

    /**
     * @param $fields
     */
    protected function fill(Entity $entity, $fields)
    {
        $this->entity = $entity;
        $this->fields = $fields;
    }

    /**
     * @throws MiniException
     */
    private function generateQuery() {
        if (!isset($this->fields))
            throw new MiniException("Fields not declared.");

        $idAttribute = isset($this->entity->idAttribute) && $this->entity->idAttribute !== "" ? $this->entity->idAttribute : 'id';

        if (isset($this->fields[$idAttribute])) {
            $query = "";

            $id = $this->fields[$idAttribute];
            unset($this->fields[$idAttribute]);

            $update = [];
            foreach ($this->fields as $field => $value) {
                $update[] = sprintf(
                    "%s = '%s'",
                    $field,
                    utf8_encode($value)
                );
            }
            $query = sprintf(
                "UPDATE %s SET %s WHERE id = %d",
                $this->entity->table,
                implode(",", $update),
                intval($id)
            );

        } else {
            $insert_fields = $insert_values = [];
            foreach ($this->fields as $field => $value) {
                $insert_fields[] = $field;
                $insert_values[] = utf8_encode($value);
            }
            $query = sprintf(
                "INSERT INTO %s (%s) VALUES ('%s')",
                $this->entity->table,
                implode(",", $insert_fields),
                implode("','", $insert_values)
            );
        }
        return $query;
    }

    /**
     *
     */
    protected function save() {
        $query = $this->generateQuery();
        $stmt = $this->prepare($query);
        $stmt->execute();

        $this->fields['id'] = $this->lastInsertId();
        return $this->fields;
    }
}