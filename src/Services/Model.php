<?php

namespace Mini\Services;

use Dotenv\Dotenv;

class Model extends \PDO
{

    public function __construct()
    {
        $env = new Dotenv(getcwd(), '../.env');
        $env->load();
        $env->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS']);

        $engine = 'mysql';
        $host = getenv('DB_HOST');
        $database = getenv('DB_NAME');
        $user = getenv('DB_USER');
        $pass = getenv('DB_PASS');
        $dns = $engine.':dbname='.$database.";host=".$host;

        parent::__construct( $dns, $user, $pass );
    }

}