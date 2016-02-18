<?php

namespace Mini\Entity\Migration;

use Mini\Entity\Entity;
use Mini\Entity\Definition\DefinitionParser;
use Mini\Entity\Migration\Table;
use Mini\Entity\Migration\TableItem;

class EntityTableParser
{
    private $modifiers = ['unsigned', 'required', 'default', 'autoincrement'];

    private $types = [
        'char', 'string', 'text', 'mediumtext', 'longtext', 'biginteger', 'integer', 'mediuminteger', 'tinyinteger',
        'smallinteger', 'float', 'double', 'decimal', 'boolean', 'date', 'datetime',
        'datetimetz', 'time', 'timetz', 'timestamp', 'timestamptz', 'binary', 'uuid', 'pk', 'email'
    ];

    private $constraints = ['belongsTo', 'belongsToOne', 'unique'];

    public function __construct()
    {
        $this->definitionParser = new DefinitionParser;
    }

    public function parse()
    {
        $result = [];
        $kernel = app()->get('Mini\Kernel');
        $pattern = $kernel->getEntitiesPath() . DIRECTORY_SEPARATOR . '*.php';
        $files = glob($pattern);

        foreach ($files as $file) {
            $matches = null;
            preg_match('@([^/]+).php$@', $file, $matches);
            $className = $matches[1];

            $matches = null;
            $contents = file_get_contents($file);
            preg_match('/namespace ([^ ]+\Models)/', str_replace("\n", '', $contents), $matches);
            $namespace = $matches[1];

            require_once $file;

            $fullClassName = $namespace . '\\' . $className;
            $entity = new $fullClassName;

            if ($entity instanceof Entity) {
                $result[$entity->table] = $this->parseEntity($entity);
            }
        }

        return $result;
    }

    public function parseEntity(Entity $entity)
    {
        $definition = $this->definitionParser->parse($entity);

        $table = new Table;
        $table->name = $entity->table;

        foreach ($definition as $key => $tags) {
            $column = new TableItem(
                TableItem::TYPE_COLUMN,
                $key
            );
            $table->items[$key] = $column;

            foreach ($tags as $tagName => $tagParameters) {
                $this->processTag($table, $column, [$tagName, $tagParameters]);
            }
        }

        return $table;
    }

    public function processTag(Table $table, TableItem $column, array $tag)
    {
        $tagName = $tag[0];
        $tagParameters = $tag[1];
        $methodName = null;

        if (in_array($tagName, $this->modifiers)) {
            $methodName = 'process' . ucfirst($tagName) . 'Modifier';
        } elseif (in_array($tagName, $this->types)) {
            $methodName = 'process' . ucfirst($tagName) . 'Type';
        } elseif (in_array($tagName, $this->constraints)) {
            $methodName = 'process' . ucfirst($tagName) . 'Constraint';
        }

        if ($methodName) {
            $this->{$methodName}($table, $column, $tagParameters);
        }
    }

    /**
     * Types
     */
    public function processIntegerType(Table $table, TableItem $column, array $tagParameters)
    {
        $length = isset($tagParameters[0]) ? $tagParameters[0] : 11;
        $column->sql .= $column->name . ' int(' . $length . ')';
    }

    public function processStringType(Table $table, TableItem $column, array $tagParameters)
    {
        $length = isset($tagParameters[0]) ? $tagParameters[0] : 255;
        $column->sql .= $column->name . ' varchar(' . $length . ')';
    }

    public function processUuidType(Table $table, TableItem $column, array $tagParameters)
    {
        $this->processStringType($table, $column, [36]);
    }

    public function processEmailType(Table $table, TableItem $column, array $tagParameters)
    {
        $this->processStringType($table, $column, [255]);
    }

    public function processPkType(Table $table, TableItem $column, array $tagParameters)
    {
        $column->sql .= $column->name . ' int(11) unsigned not null primary key auto_increment';
    }

    /**
     * Modifiers
     */
    public function processDefaultModifier(Table $table, TableItem $column, array $tagParameters)
    {
        $column->sql .= ' default ' . $tagParameters[0];
    }

    public function processAutoincrementModifier(Table $table, TableItem $column, array $tagParameters)
    {
        $column->sql .= ' auto_increment';
    }

    public function processUnsignedModifier(Table $table, TableItem $column, array $tagParameters)
    {
        $column->sql .= ' unsigned';
    }

    public function processRequiredModifier(Table $table, TableItem $column, array $tagParameters)
    {
        $column->sql .= ' not null';
    }

    public function processUniqueConstraint(Table $table, TableItem $column, array $tagParameters)
    {
        $keyName = $table->name . '_' . $column->name . '_unique';

        $table->items[$keyName] = new TableItem(
            TableItem::TYPE_CONSTRAINT,
            $keyName,
            'CREATE UNIQUE INDEX ' . $keyName . ' ON ' . $table->name . ' (' . $column->name . ')'
        );
    }

    public function processBelongsToConstraint(Table $table, TableItem $column, array $tagParameters)
    {
        $this->processUnsignedModifier($table, $column, $tagParameters);

        $otherTableName = $tagParameters[0];
        $keyName = $table->name . '_' . $column->name . '_fk';

        $table->items[$keyName] = new TableItem(
            TableItem::TYPE_CONSTRAINT,
            $keyName,
            'ALTER TABLE ' . $table->name . ' ADD CONSTRAINT ' . $keyName . ' FOREIGN KEY (' . $column->name . ') REFERENCES ' . $otherTableName . ' (id)'
        );
    }
}
