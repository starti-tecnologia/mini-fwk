<?php

namespace Mini\Console\Command;

use Mini\Entity\Migration\DatabaseTableParser;
use Mini\Entity\Migration\EntityTableParser;
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
        $commando->option('diff')
            ->aka('d')
            ->describedAs('Make a diff migration from the current entities definition')
            ->boolean();
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
            'ClassNamePlaceholder' => $name
        ];

        if ($commando['diff']) {
            $replaces = array_merge($replaces, $this->makeDiffMigration());
        } else {
            $replaces = array_merge($replaces, $this->makeEmptyMigration());
        }

        file_put_contents(
            $file,
            str_replace(array_keys($replaces), array_values($replaces), $template)
        );

        echo 'Migration file created at ' . $file . PHP_EOL;
    }

    public function makeEmptyMigration()
    {
        return [
            '/* UpMethodPlaceholder */' => '// this method is auto-generated, please modify it to your needs',
            '/* DownMethodPlaceholder */' => '// this method is auto-generated, please modify it to your needs',
        ];
    }

    public function makeDiffMigration()
    {
        $entityTables = (new EntityTableParser)->parse();
        $databaseTables = (new DatabaseTableParser)->parse();

        return [
            '/* UpMethodPlaceholder */' => $this->processTablesDiff($entityTables, $databaseTables),
            '/* DownMethodPlaceholder */' => $this->processTablesDiff($databaseTables, $entityTables),
        ];
    }

    public function processTablesDiff(array $sourceTables, array $destTables)
    {
        $operations = [];

        $createTables = array_diff(array_keys($sourceTables), array_keys($destTables));
        $dropTables = array_diff(array_keys($destTables), array_keys($sourceTables));
        $modifyTables = array_intersect(array_keys($sourceTables), array_keys($destTables));

        foreach ($createTables as $name) {
            $table = $sourceTables[$name];
            $operations = array_merge(
                $operations,
                explode(';', $table->makeCreateSql())
            );
        }

        foreach ($dropTables as $name) {
            $table = $destTables[$name];
            $operations = array_merge(
                $operations,
                explode(';', $table->makeDropSql())
            );
        }

        foreach ($modifyTables as $name) {
            $sourceTable = $sourceTables[$name];
            $destTable = $destTables[$name];

            $operations = array_merge(
                $sourceTable->makeAddOperations($destTable),
                $sourceTable->makeDropOperations($destTable),
                $sourceTable->makeModifyOperations($destTable)
            );
        }

        $calls =  array_map(function ($operation) {
            return '$this->addSql(\'' . addslashes($operation) . '\');';
        }, $operations);

        return implode("\n        ", $calls);
    }
}
