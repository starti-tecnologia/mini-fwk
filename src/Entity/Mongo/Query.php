<?php

namespace Mini\Entity\Mongo;

use Mini\Exceptions\QueryException;
use MongoDB\Driver\Cursor;

class Query
{
    /**
     * @param array
     */
    private $namespace = null;

    /**
     * @param array
     */
    private $manager = null;

    /**
     * @param array
     */
    private $filter = [];

    /**
     * @param array
     */
    private $options = [];

    /**
     * @param array
     */
    private $ignoredOnCount = [];

    public function __construct($namespace, $manager)
    {
        $this->namespace = $namespace;
        $this->manager = $manager;
    }

    /**
     * @return self
     */
    public function filter($key, $value)
    {
        $this->filter[$key] = $value;
        return $this;
    }

    /**
     * @return self
     */
    public function projection(array $projection)
    {
        $this->options['projection'] = $projection;
        return $this;
    }

    /**
     * @return self
     */
    public function sort($sort)
    {
        $this->options['sort'] = $sort;
        return $this;
    }

    /**
     * @return self
     */
    public function limit($limit)
    {
        $this->options['limit'] = $limit;
        return $this;
    }

    /**
     * @return self
     */
    public function skip($skip)
    {
        $this->options['skip'] = $skip;
        return $this;
    }

    /**
     * @param Cursor $cursor
     * @return array
     */
    private static function toArray(Cursor $cursor) {
        return json_decode(json_encode(iterator_to_array($cursor)), true);
    }

    /**
     * @return array
     */
    public function listArray()
    {
        $query = new \MongoDB\Driver\Query($this->filter, $this->options);
        $cursor = $this->manager->executeQuery($this->namespace, $query);
        return $this->toArray($cursor);
    }

    /**
     * @return array
     */
    public function getArray()
    {
        $items = $this->limit(1)->listArray();
        return isset($items[0]) ? $items[0] : null;
    }

    /**
     * @return array
     */
    public function getArrayOrFail()
    {
        $item = $this->getArray();

        if (! $item) {
            throw new QueryException('No query results for entity ' . $this->namespace);
        }

        return $item;
    }

    /**
     * @return self
     */
    public function ignoreOnCount($field)
    {
        $this->ignoredOnCount[$field] = true;
        return $this;
    }

    /**
     * @return integer
     */
    public function count()
    {
        list($db, $collection) = explode('.', $this->namespace);
        $query = [];
        foreach ($this->filter as $key => $value) {
            if (isset($this->ignoredOnCount[$key])) {
                continue;
            }

            $query[$key] = $value;
        }
        $options = ['count' => $collection, 'query' => $query];
        $result = $this->manager->executeCommand($db, new \MongoDB\Driver\Command($options));
        return $result->toArray()[0]->n;
    }
}
