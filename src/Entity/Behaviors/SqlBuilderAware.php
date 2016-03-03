<?php

namespace Mini\Entity\Behaviors;

use Mini\Entity\RawValue;

trait SqlBuilderAware
{
    public function select($query, $params = []) {
        $sth = $this->prepare($query);
        $sth->execute($params);

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function selectOne($query, $params = []) {
        $sth = $this->prepare($query);
        $sth->execute($params);

        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

    public function insert($table, $fields)
    {
        $template = 'INSERT INTO %s (%s) VALUES (%s)';
        $columns = array_keys($fields);
        $values = [];
        $bindings = [];

        foreach ($fields as $key => $value) {
            if ($value instanceof RawValue) {
                $values[] = $value->value;
            } else {
                $values[] = '?';
                $bindings[] = $value;
            }
        }

        $stm = $this->prepare(sprintf($template, $table, implode(', ', $columns), implode(', ', $values)));
        $stm->execute($bindings);
    }

    public function update($table, array $fields, array $filter)
    {
        $template = 'UPDATE %s SET %s WHERE %s';
        $updates = [];
        $wheres = [];
        $bindings = [];
        $count = 0;

        foreach ($fields as $key => $value) {
            if ($value instanceof RawValue) {
                $updates[] = $key . ' = ' . $value->value;
            } else {
                $updates[] = $key . ' = ?';
                $bindings[] = $value;
            }
        }

        foreach ($filter as $key => $value) {
            if ($value instanceof RawValue) {
                $wheres[] = $key . ' = ' . $value->value;
            } else {
                $wheres[] = $key . ' = ?';
                $bindings[] = $value;
            }
        }

        $stm = $this->prepare(sprintf($template, $table, implode(', ', $updates), implode(' AND ', $wheres)));
        $stm->execute($bindings);
    }

    public function delete($table, array $filters)
    {
        $template = 'DELETE FROM %s WHERE %s';
        $wheres = [];
        $bindings = [];

        foreach ($filters as $key => $value) {
            if ($value instanceof RawValue) {
                $wheres[] = $key . ' = ' . $value->value;
            } else {
                $wheres[] = $key . ' = ?';
                $bindings[] = $value;
            }
        }

        $stm = $this->prepare(sprintf($template, $table, implode(' AND ', $wheres)));
        $stm->execute($bindings);
    }
}
