<?php

namespace Mini\Entity;

use Mini\Container;

class Connection extends \PDO
{
    public $name;

    public $database;

    public function __construct($data, $name)
    {
        $this->name = $name;
        $driver = $data['driver'];
        $host = $data['host'];
        $database = $data['database'];
        $user = $data['user'];
        $pass = $data['pass'];

        $this->database = $database;
        $dns = $driver.':dbname='.$database.";host=".$host;

        parent::__construct( $dns, $user, $pass );

        if (isset($data['exec'])) {
            foreach ($data['exec'] as $sql) {
                parent::exec($sql);
            }
        }
    }

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
}
