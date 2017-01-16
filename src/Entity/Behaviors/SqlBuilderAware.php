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

    public function filterBinding($value)
    {
        return $value === false ? 0 : ($value === true ? 1 : $value);
    }

    private function handleCreate($operator, $table, $fields)
    {
        $template = $operator . ' INTO %s (%s) VALUES (%s)';
        $columns = array_keys($fields);
        $values = [];
        $bindings = [];

        foreach ($fields as $key => $value) {
            if ($value instanceof RawValue) {
                $values[] = $value->value;
            } else {
                $values[] = '?';
                $bindings[] = $this->filterBinding($value);
            }
        }

        $stm = $this->prepare(sprintf($template, $table, implode(', ', $columns), implode(', ', $values)));
        $stm->execute($bindings);
    }

    public function insert($table, $fields)
    {
        $this->handleCreate('INSERT', $table, $fields);
    }

    public function replace($table, $fields)
    {
        $this->handleCreate('REPLACE', $table, $fields);
    }

    public function insertOrUpdate($table, $fields, array $ignoredUpdates = [], array $extraUpdates = [])
    {
        $template = 'INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s';
        $insertColumns = array_keys($fields);
        $insertValues = [];
        $updates = [];
        $bindings = [];

        foreach ($fields as $key => $value) {
            if ($value instanceof RawValue) {
                $insertValues[] = $value->value;
            } else {
                $insertValues[] = '?';
                $bindings[] = $this->filterBinding($value);
            }
        }

        foreach (array_merge($fields, $extraUpdates) as $key => $value) {
            if (in_array($key, $ignoredUpdates)) {
                continue;
            }

            if ($value instanceof RawValue) {
                $updates[] = $key . ' = ' . $value->value;
            } else {
                $updates[] = $key . ' = ?';
                $bindings[] = $this->filterBinding($value);
            }
        }

        $stm = $this->prepare(
            sprintf(
                $template,
                $table,
                implode(', ', $insertColumns),
                implode(', ', $insertValues),
                implode(', ', $updates)
            )
        );

        $stm->execute($bindings);
    }

    public function update($table, array $fields, array $filter)
    {
        $template = 'UPDATE %s SET %s WHERE %s';
        $updates = [];
        $wheres = [];
        $bindings = [];

        foreach ($fields as $key => $value) {
            if ($value instanceof RawValue) {
                $updates[] = $key . ' = ' . $value->value;
            } else {
                $updates[] = $key . ' = ?';
                $bindings[] = $this->filterBinding($value);
            }
        }

        foreach ($filter as $key => $value) {
            if ($value instanceof RawValue) {
                $wheres[] = $key . ' = ' . $value->value;
            } else {
                $wheres[] = $key . ' = ?';
                $bindings[] = $this->filterBinding($value);
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
                $bindings[] = $this->filterBinding($value);
            }
        }

        $stm = $this->prepare(sprintf($template, $table, implode(' AND ', $wheres)));
        $stm->execute($bindings);
    }
}
