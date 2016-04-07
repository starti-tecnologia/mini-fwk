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
     * Fields that are filled and validated
     *
     * @var array
     */
    public $fillable = [];

    /**
     * Fields that are serialized with json_encode
     *
     * @var array
     */
    public $visible = [];

    /**
     * All current entity fields and values
     *
     * @var array
     */
    public $fields = [];

    /**
     * Fields that get prefixed as object on fill and jsonSerialize;
     *
     * Example: Turn {address_name:'', address_number:''} into {address: {name:'', number:''}}
     *
     * @var array
     */
    public $prefixAsObject = null;

    /**
     * Enable customization on serialization format
     *
     * @var array
     */
    public $format = null;

    /**
     * Definition used to database storage and validation
     *
     * @var array
     */
    public $definition = [];

    /**
     * Define relations to be used with setRelation and getRelation
     *
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
     * Update fields with user input. See fillable attribute
     *
     * @var array $data
     */
    public function fill($data)
    {
        if ($this->prefixAsObject) {
            foreach ($this->prefixAsObject as $prefix) {
                if (! isset($data[$prefix])) {
                    continue;
                }

                foreach ($data[$prefix] as $key => $value) {
                    $data[$prefix . '_' . $key] = $value;
                }

                unset($data[$prefix]);
            }
        }

        $allowEverything = isset($this->fillable[0]) && $this->fillable[0] === '*';

        foreach ($data as $key => $value) {
            if ($allowEverything || in_array($key, $this->fillable)) {
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
        if (isset($this->fields[$key])) {
            return $this->fields[$key];
        } else if (isset($this->definition[$key])) {
            return null;
        } else {
            throw new \Exception('Unknown field ' .  $key);
        }
    }

    /**
     * Attach a related entity to current entity. This will set the foreign
     * and make the attached entity available in the object
     * 
     * @param string $relationName
     * @param Entity $relationInstance
     * @return void
     */
    public function setRelation($relationName, Entity $relationInstance = null)
    {
        if ($relationInstance) {
            $id = $relationInstance->fields[$relationInstance->idAttribute];
            $this->relationCache[$relationName] = $relationInstance;
            $this->fields[$this->relations[$relationName]['field']] = $id;
        } else {
            $this->fields[$this->relations[$relationName]['field']] = null;
        }
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
     * Return fields that will be serialized with json
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return EntitySerializer::instance()->serialize($this);
    }
}
