<?php

namespace Mini\Entity\Migration;

use Mini\Entity\Entity;
use Mini\Entity\Definition\DefinitionParser;
use Mini\Entity\Migration\Table;
use Mini\Entity\Migration\TableItem;

class EntityTableParser
{
    private $types = [
        // Custom
        'pk',
        'email',
        'uuid',

        // Text
        'char',
        'string',
        'text',

        // Numeric and binary
        'integer',
        'float',
        'double',
        'decimal',
        'boolean',
        'binary',

        // Date
        'date',
        'datetime',
        'time',
        'timestamp'
    ];

    private $modifiers = [
        // Don't change this order to be compatible with database table parser
        'unsigned',
        'required',
        'default'
    ];

    private $constraints = [
        'belongsTo',
        'unique'
    ];

    public function __construct()
    {
        $this->definitionParser = new DefinitionParser;
        $this->tableSorter = new TableSorter;
        $this->tagOrder = array_merge($this->types, $this->modifiers, $this->constraints);
    }

    public function parse($connectionName)
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

            if ($entity->connection != $connectionName) {
                continue;
            }

            if ($entity instanceof Entity) {
                $result[$entity->table] = $this->parseEntity($entity);
            }
        }

        return $this->tableSorter->sort($result);
    }

    public function parseEntity(Entity $entity)
    {
        $definition = $this->definitionParser->parse($entity);

        $table = new Table;
        $table->name = $entity->table;

        foreach ($definition as $key => $tags) {
            uksort($tags, function ($tagA, $tagB) {
                $a = array_search($tagA, $this->tagOrder);
                $b = array_search($tagB, $this->tagOrder);

                return $a == $b ? 0 : ($a > $b ? 1 : -1);
            });

            $column = new TableItem(
                TableItem::TYPE_COLUMN,
                $key
            );
            $table->items[$key] = $column;

            foreach ($tags as $tagName => $tagParameters) {
                $this->processTag($table, $column, [$tagName, $tagParameters]);
            }
        }

        uasort($table->items, function ($a, $b) {
            return $a->type == $b->type ? 0 : ($a->type > $b->type ? 1 : -1);
        });

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
    public function processCharType(Table $table, TableItem $column, array $tagParameters)
    {
        $column->sql = $column->name . " char({$tagParameters[0]})";
    }

    public function processStringType(Table $table, TableItem $column, array $tagParameters)
    {
        $length = !empty($tagParameters[0]) ? $tagParameters[0] : 255;
        $column->sql = $column->name . ' varchar(' . $length . ')';
    }

    public function processTextType(Table $table, TableItem $column, array $tagParameters)
    {
        $length = !empty($tagParameters[0]) ? intval($tagParameters[0]) : 65535;

        if ($length <= 255) {
            $type = 'tinytext(255)';
        } elseif ($length <= 65535) {
            $type = 'text';
        } elseif ($length <= 16777215) {
            $type = 'mediumtext';
        } elseif ($length <= 4294967295) {
            $type = 'longtext';
        } else {
            throw new Exception('Unsupported text length: ' . $length);
        }

        $column->sql = $column->name . ' ' . $type;
    }

    public function processIntegerType(Table $table, TableItem $column, array $tagParameters)
    {
        $length = !empty($tagParameters[0]) ? $tagParameters[0] : 11;

        if ($length <= 4) {
            $type = 'tinyint';
        } elseif ($length <= 6) {
            $type = 'smallint';
        } elseif ($length <= 11) {
            $type = 'int';
        } elseif ($length <= 20) {
            $type = 'bigint';
        } else {
            throw new Exception('Unsupported integer length: ' . $length);
        }

        $column->sql = $column->name . ' ' . $type . '(' . $length . ')';
    }

    public function processFloatType(Table $table, TableItem $column, array $tagParameters)
    {
        $column->sql = $column->name . ' float';
    }

    public function processDoubleType(Table $table, TableItem $column, array $tagParameters)
    {
        $column->sql = $column->name . ' double';
    }

    public function processDecimalType(Table $table, TableItem $column, array $tagParameters)
    {
        $pieces = explode(',', $tagParameters[0]);
        $maximumNumberOfDigits = $pieces[0];
        $numberOfDigitsToTheRight = $pieces[1];

        $column->sql = $column->name . ' decimal(' . $maximumNumberOfDigits . ',' . $numberOfDigitsToTheRight . ')';
    }

    public function processBooleanType(Table $table, TableItem $column, array $tagParameters)
    {
        $column->sql = $column->name . ' tinyint(1)';
    }

    public function processDateType(Table $table, TableItem $column, array $tagParameters)
    {
        $column->sql = $column->name . ' date';
    }

    public function processDatetimeType(Table $table, TableItem $column, array $tagParameters)
    {
        $column->sql = $column->name . ' datetime';
    }


    public function processTimeType(Table $table, TableItem $column, array $tagParameters)
    {
        $column->sql = $column->name . ' time';
    }

    public function processTimestampType(Table $table, TableItem $column, array $tagParameters)
    {
        $column->sql = $column->name . ' timestamp';
    }

    public function processBinaryType(Table $table, TableItem $column, array $tagParameters)
    {
        $column->sql = $column->name . ' blob';
    }

    public function processUuidType(Table $table, TableItem $column, array $tagParameters)
    {
        $this->processStringType($table, $column, [36]);
    }

    public function processPkType(Table $table, TableItem $column, array $tagParameters)
    {
        $column->sql = $column->name . ' int(11) unsigned not null primary key auto_increment';
    }

    public function processEmailType(Table $table, TableItem $column, array $tagParameters)
    {
        $this->processStringType($table, $column, [255]);
    }

    /**
     * Modifiers
     */
    public function processDefaultModifier(Table $table, TableItem $column, array $tagParameters)
    {
        $default = $tagParameters[0];

        if (! is_numeric($default)) {
            $default = '\'' . $default . '\'';
        }

        $column->sql .= ' default ' . $default;
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