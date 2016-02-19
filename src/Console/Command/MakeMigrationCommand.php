<?php

namespace Mini\Console\Command;

use Mini\Entity\Migration\DatabaseTableParser;
use Mini\Entity\Migration\EntityTableParser;
use Commando\Command as Commando;
use Mini\Container;

class MakeMigrationCommand extends AbstractCommand
{
    private $force = false;

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

        $commando->option('force')
            ->aka('f')
            ->describedAs('Ignore validations')
            ->boolean();
    }

    public function run(Commando $commando)
    {
        $c = new \Colors\Color();
        $this->force = $commando['force'];

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
            $generatedReplaces = $this->makeDiffMigration();
        } else {
            $generatedReplaces = $this->makeEmptyMigration();
        }

        if ($generatedReplaces != null) {
            $replaces = array_merge($replaces, $generatedReplaces);

            file_put_contents(
                $file,
                str_replace(array_keys($replaces), array_values($replaces), $template)
            );

            echo $c('Migration file created at ' . $file)->green() . PHP_EOL;
        } else {
            echo $c('No changes detected')->yellow() . PHP_EOL;
        }
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

        $upDiff = $this->processTablesDiff($entityTables, $databaseTables);
        $downDiff = $this->processTablesDiff($databaseTables, $entityTables);

        if (! $upDiff) {
            return null;
        }

        return [
            '/* UpMethodPlaceholder */' => $upDiff,
            '/* DownMethodPlaceholder */' => $downDiff,
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

            $addOperations = $sourceTable->makeAddOperations($destTable);
            $dropOperations = $sourceTable->makeDropOperations($destTable);
            $modifyOperations = $sourceTable->makeModifyOperations($destTable);

            if (! $this->force) {
                foreach ($modifyOperations as $modifyOperation) {
                    $sourceTable->validateModifyOperation($modifyOperation);
                }
            }

            $operations = array_merge(
                $operations,
                $addOperations,
                $dropOperations,
                $modifyOperations
            );
        }

        $calls =  array_map(function ($operation) {
            return '$this->addSql(\'' . addslashes($operation) . '\');';
        }, $operations);

        return implode(PHP_EOL . str_repeat(' ', 8), $calls);
    }
}
