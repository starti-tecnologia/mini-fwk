<?php

namespace Mini\Helpers\Fake;

class FakeStatement
{
    private $context;

    public function __construct(array $context)
    {
        $this->context = $context;
    }

    public function execute($parameters = [])
    {
        $this->context['parameters'] = $parameters;

        $this->context['manager']->log[] = [
            $this->context['connection'],
            $this->context['sql'],
            $this->context['parameters']
        ];
    }

    private function getResults()
    {
        $rows = [];
        foreach ($this->context['fixtures'] as $pattern => $results) {
            $sql = $this->makeSql($this->context['sql'], $this->context['parameters']);
            if (preg_match($pattern, $sql)) {
              $rows = $results;
	      break;
            }
        }
        return $rows;
    }

    public function fetch()
    {
        if (stristr($this->context['sql'], 'FAKE_CONNECTION_EMPTY_TABLE')) {
            return null;
        }
        $results = $this->getResults();
        return count($results) ? $results[0] : null;
    }

    public function fetchObject($className)
    {
        if (stristr($this->context['sql'], 'FAKE_CONNECTION_EMPTY_TABLE')) {
            return null;
        }
        $instance = new $className;
        $results = $this->getResults();
        $row = $results && $results[0] ? $results[0] : null;
        if (! $row) {
            return null;
        }
        foreach ($row as $key => $value) {
            $instance->$key = $value;
        }
        return $instance;
    }

    public function fetchAll($fethStyle=null, $className=null)
    {
        if (stristr($this->context['sql'], 'FAKE_CONNECTION_EMPTY_TABLE')) {
            return [];
        }

        $rows = $this->getResults();

        $results = [];

        foreach ($rows as $row) {
            if ($fethStyle === \PDO::FETCH_ASSOC) {
                $results[] = $row;
            } else if ($fethStyle == \PDO::FETCH_CLASS) {
                $instance = new $className;
                foreach ($row as $key => $value) {
                    $instance->$key = $value;
                }
                $results[] = $instance;
            }
        }

        return $results;
    }

    public function fetchColumn()
    {
        if (stristr($this->context['sql'], 'FAKE_CONNECTION_EMPTY_TABLE')) {
            return null;
        }
        $results = $this->getResults();
        $row = $results && $results[0] ? $results[0] : null;
        if ($row) {
            $keys = array_keys($row);
            return $row[$keys[0]];
        }
    }

    /**
     * Escape sql params
     *
     * @param  string $sql
     * @param  array  $params
     * @return string
     */
    private function makeSql($sql, $params = array())
    {
        if (! is_array($params)) {
            return $sql;
        }
        if (count($params) && array_keys($params) !== range(0, count($params) - 1)) {
            $keys = array_map('strlen', array_keys($params));
            array_multisort($keys, SORT_DESC, $params);
        }
        foreach ($params as $key => $value) {
            $value = is_null($value)
                ? 'NULL'
                : (
                    is_numeric($value)
                        ? addcslashes($value, '\'')
                        : "'" . addcslashes($value, '\'') . "'"
                );
            if (is_numeric($key)) {
                $sql = preg_replace('/\?/', $value, $sql, 1);
            } else {
                $sql = str_replace(':' . $key, $value, $sql);
            }
        }
        return $sql;
    }
}
