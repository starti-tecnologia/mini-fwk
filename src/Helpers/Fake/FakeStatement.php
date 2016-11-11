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
        $this->context['manager']->log[] = [
            $this->context['connection'],
            $this->context['sql'],
            $parameters
        ];
    }

    private function getResults()
    {
        $rows = [
            ['lala' => 'hi'],
            ['lala' => 'good day']
        ];
        foreach ($this->context['fixtures'] as $pattern => $results) {
            if (preg_match($pattern, $this->context['sql'])) {
              $rows = $results;
            }
        }
        return $rows;
    }

    public function fetch()
    {
        if (stristr($this->context['sql'], 'FAKE_CONNECTION_EMPTY_TABLE')) {
            return null;
        }

        return $this->getResults()[0];
    }

    public function fetchObject($className)
    {
        if (stristr($this->context['sql'], 'FAKE_CONNECTION_EMPTY_TABLE')) {
            return null;
        }
        $instance = new $className;
        $row = $this->getResults()[0] ? $this->getResults()[0] : null;
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
        $row = $this->getResults()[0] ? $this->getResults()[0] : null;
        if ($row) {
            $keys = array_keys($row);
            return $row[$keys[0]];
        }
    }
}
