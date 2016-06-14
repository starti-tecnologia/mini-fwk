<?php

namespace App\Commands;

use Mini\Console\Command\AbstractCommand;
use Commando\Command as Commando;

class ClassNamePlaceholder extends AbstractCommand
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'CommandNamePlaceholder';
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'CommandDescriptionPlaceholder';
    }

    /**
     * @param Commando $commando
     */
    public function setUp(Commando $commando)
    {
        /**
         * Example:
         *
         * $commando->option('name')
         *     ->describedAs('Command name, example: "script:invoice:process"')
         *     ->defaultsTo('');
         */
    }

    /**
     * @param Commando $commando
     */
    public function run(Commando $commando)
    {
        /**
         * Example:
         *
         * echo $commando['name'];
         */
    }
}
