<?php

namespace Mini\Entity\Mongo;

use Mini\Entity\Mongo\Behaviors\MongoQueryAware;
use MongoDB\Driver\Manager as MongoDBManager;

class Connection
{

    /**
     * @var MongoDBManager
     */
    private $mongodb;

    /**
     * @var
     */
    private $database;

    /**
     * Connection constructor.
     * @param $data
     * @param $name
     */
    function __construct($data, $name)
    {
        $dns = sprintf(
            "mongodb://%s:%s",
            $data['host'],
            $data['port']
        );

        $this->database = $data['database'];
        $this->mongodb = new MongoDBManager($dns);
    }

    /**
     * @return \MongoDB\Driver\Manager MongoDBManager
     */
    public function getDb() {
        return $this->mongodb;
    }

    /**
     * @return mixed
     */
    public function getDbName() {
        return $this->database;
    }

}