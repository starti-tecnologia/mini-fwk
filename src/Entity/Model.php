<?php

namespace Mini\Entity;

use Dotenv\Dotenv;
use Mini\Container;

class Model extends \PDO
{
    public $database;

    public function __construct()
    {
        $kernel = app()->get('Mini\Kernel');

        $env = new Dotenv($kernel->getBasePath());
        $env->load();
        $env->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS']);

        $engine = 'mysql';
        $host = getenv('DB_HOST');
        $database = getenv('DB_NAME');
        $user = getenv('DB_USER');
        $pass = getenv('DB_PASS');
        $dns = $engine.':dbname='.$database.";host=".$host;
        $this->database = $database;

        parent::__construct( $dns, $user, $pass );

        parent::exec("SET CHARACTER SET utf8");

    }

    public function select($query) {
        $sth = $this->prepare($query);
        $sth->execute();

        $result = $sth->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }
}
