<?php

namespace Mini\Console\Command;

use Commando\Command as Commando;
use Mini\Container;

class MakeMigrationCommand extends AbstractCommand
{
    public function getName()
    {
        return 'make:migration';
    }

    public function getDescription()
    {
        return 'Create a empty migration file';
    }

    public function setUp(Commando $commando)
    {

    }

    public function run(Commando $commando)
    {
        $kernel = app()->get('Mini\Kernel');
        $path = $kernel->getMigrationsPath();
        $name = 'Migration' . date('YmdHis');
        $file = $path . DIRECTORY_SEPARATOR .  $name . '.php';
        $template = file_get_contents(__DIR__ . '/Templates/MigrationTemplate.php');

        if (! is_dir($path)) {
            mkdir($path);
        }

        $replaces = [
            'ClassNamePlaceholder' => $name,
            '/* UpMethodPlaceholder */' => '// this method is auto-generated, please modify it to your needs',
            '/* DownMethodPlaceholder */' => '// this method is auto-generated, please modify it to your needs',
        ];

        file_put_contents(
            $file,
            str_replace(array_keys($replaces), array_values($replaces), $template)
        );

        echo 'Migration file created at ' . $file . PHP_EOL;
    }
}
