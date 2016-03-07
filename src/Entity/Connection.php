<?php

namespace Mini\Entity;

use Mini\Container;
use Mini\Entity\RawValue;
use Mini\Entity\Behaviors\SqlBuilderAware;
use PDO;

class Connection extends PDO
{
    use SqlBuilderAware;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
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
        $dns = $driver.':dbname='.$database.';host='.$host;

        parent::__construct( $dns, $user, $pass );
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if (isset($data['exec'])) {
            foreach ($data['exec'] as $sql) {
                parent::exec($sql);
            }
        }
    }
}
