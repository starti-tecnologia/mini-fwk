<?php

namespace Mini\Services;

use Dotenv\Dotenv;

class Model extends \PDO
{

    public function __construct()
    {
        $env = new Dotenv(__DIR__, 'env');
        print_r($env);

        $engine = 'mysql';
        $host = '127.0.0.1';
        $database = 'adm_finance';
        $user = 'root';
        $pass = 'root';
        $dns = $engine.':dbname='.$database.";host=".$host;

        parent::__construct( $dns, $user, $pass );
    }

}