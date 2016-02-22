<?php

namespace Mini\Console\Command;

use Mini\Entity\Migration\DatabaseTableParser;
use Mini\Entity\Migration\EntityTableParser;
use Commando\Command as Commando;
use Mini\Container;

class MakeMigrationCommand extends AbstractCommand
{
    /**
     * \Mini\Entity\Connection
     */
    private $connection;

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

        $commando->option('connection')
            ->describedAs('Connection used on migration')
            ->defaultsTo('default');
    }

    public function run(Commando $commando)
    {
        $this->force = $commando['force'];
        $this->connection = app()->get('Mini\Entity\ConnectionManager')->getConnection($commando['connection']);

        $c = new \Colors\Color();
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
            'ConnectionPlaceholder' => $commando['connection']
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
        $entityTableParser = new EntityTableParser;
        $databaseTableParser = new DatabaseTableParser;
        $databaseTableParser->setConnection($this->connection);

        $entityTables = $entityTableParser->parse($this->connection->name);
        $databaseTables = $databaseTableParser->parse();

        $upDiff = $this->processTablesDiff($entityTables, $databaseTables, 'up');
        $downDiff = $this->processTablesDiff($databaseTables, $entityTables, 'down');

        if (! $upDiff) {
            return null;
        }

        return [
            '/* UpMethodPlaceholder */' => $upDiff,
            '/* DownMethodPlaceholder */' => $downDiff,
        ];
    }

    public function processTablesDiff(array $sourceTables, array $destTables, $direction)
    {
        $operations = [];

        if ($direction == 'down') {
            $sourceTables = array_reverse($sourceTables, true);
            $destTables = array_reverse($destTables, true);
        }

        $createTables = array_diff(array_keys($sourceTables), array_keys($destTables));
        $dropTables = array_diff(array_keys($destTables), array_keys($sourceTables));
        $modifyTables = array_intersect(array_keys($sourceTables), array_keys($destTables));

        if (! $this->force) {
            foreach ($sourceTables as $table) {
                $table->validateColumns();
            }
        }

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
