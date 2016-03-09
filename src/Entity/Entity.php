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
     * @var array
     */
    public $relations = [];

    /**
     * Stores cached relations for use in getRelation
     *
     * @var array
     */
    private $relationCache = [];

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
     * Attach a related entity to current entity. This will set the foreign
     * and make the attached entity available in the object
     * 
     * @param string $relationName
     * @param Entity $relationInstance
     * @return void
     */
    public function setRelation($relationName, Entity $relationInstance)
    {
        $id = $relationInstance->{$relationInstance->idAttribute};
        $this->relationCache[$relationName] = $relationInstance;
        $this->fields[$this->relations[$relationName]['field']] = $id;
    }

    /**
     * Returns a relation entity if available
     * 
     * @param string $relationName
     * @return Entity|void
     */
    public function getRelation($relationName)
    {
        if (isset($this->relationCache[$relationName])) {
            return $this->relationCache[$relationName];
        } else if (isset($this->relations[$relationName])) {
            $relation = $this->relations[$relationName];

            if (empty($this->fields[$relation['field']])) {
                return null;
            }

            $relationInstance = new $relation['class']();

            foreach ($this->fields as $key => $value) {
                $prefix = $relationName . '_';

                if (strpos($key, $prefix) === 0) {
                    $relationKey = str_replace($prefix, '', $key);
                    $relationInstance->fields[$relationKey] = $value;
                }
            }

            $this->relationCache[$relationName] = $relationInstance;

            return $this->relationCache[$relationName];
        }
    }

    /**
     * Serialize to json
     */
    public function jsonSerialize()
    {
        return $this->fields;
    }
}
