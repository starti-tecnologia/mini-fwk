<?php

namespace Mini\Entity;

abstract class Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    public $table;

    /**
     * @var string
     */
    public $connection = 'default';

    /**
     * @var boolean
     */
    public $useSoftDeletes = false;

    /**
     * @var boolean
     */
    public $useTimeStamps = false;

    /**
     * @var array
     */
    public $fillable = [];

    /**
     * @var array
     */
    public $fields = [];

    /**
     * @var array
     */
    public $definition = [];

    /**
     * @var string
     */
    public $idAttribute = 'id';

    /**
     * Shared instance used in protected function instance
     * 
     * @var self
     */
    private static $instance = null;

    /**
     * @var array $data
     */
    public function fill($data)
    {
        foreach ($data as $key => $value) {
            if ($this->fillable === null || in_array($key, $this->fillable)) {
                $this->fields[$key] = $value;
            }
        }
    }

    /**
     * Sets a entity attribute
     */
    public function __set($key, $value)
    {
        $this->fields[$key] = $value;
    }

    /**
     * Gets a entity attribute
     */
    public function __get($key)
    {
        return $this->fields[$key];
    }

    /**
     * Serialize to json
     */
    public function jsonSerialize()
    {
        return $this->fields;
    }

    protected static function getInstance()
    {
        if (! self::$instance) {
            self::$instance = new static;
        }

        return self::$instance;
    }

    public static function query()
    {
        $instance = self::getInstance();

        $query = (new Query)
            ->table($instance->table)
            ->connection($instance->connection)
            ->className(static::class);

        if ($instance->useSoftDeletes) {
            $query->whereIsNull($instance->table . '.deleted_at');
        }

        return $query;
    }
}
