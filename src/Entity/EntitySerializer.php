<?php

namespace Mini\Entity;

class EntitySerializer
{
    private $transformCache = [];

    private static $currentInstance;

    public static function instance()
    {
        if (! self::$currentInstance) {
            self::$currentInstance = new self;
        }

        return self::$currentInstance;
    }

    public function serialize(Entity $entity = null)
    {
        if (! $entity) {
            return null;
        }

        $className = get_class($entity);

        if (! isset($this->transformCache[$className])) {
            $format = $this->makeDefaultFormat($entity);
            $fn = $this->makeTransformFunction($format);
            $this->transformCache[$className] = $fn;
        } else {
            $fn = $this->transformCache[$className];
        }

        return $fn($entity->fields);
    }

    private function processFormatDefinition(Entity $instance, $rawKey, array $format)
    {
        $definition = isset($instance->definition[$rawKey]) ? $instance->definition[$rawKey] : null;

        if ($definition === null) {
            return $format;
        }

        if (strpos($definition, 'integer') !== false || strpos($definition, 'pk') !== false) {
            $format['integer'] = true;
        } elseif (strpos($definition, 'float') !== false ||
            strpos($definition, 'double') !== false ||
            strpos($definition, 'decimal') !== false) {
            $format['float'] = true;
        } elseif (strpos($definition, 'boolean') !== false) {
            $format['boolean'] = true;
        } elseif (strpos($definition, 'datetime') !== false || strpos($definition, 'timestamp') !== false) {
            $format['datetime'] = true;
        } elseif (strpos($definition, 'date') !== false) {
            $format['date'] = true;
        }

        return $format;
    }

    private function makeDefaultFormat(Entity $entity)
    {
        $relations = $entity->relations;

        $format = [];
        $formatByKey = [];

        foreach (array_keys($entity->fields) as $field) {
            if ($entity->visible && ! in_array($field, $entity->visible)) {
                continue;
            }

            $isRelation = false;

            foreach ($relations as $relationName => $relationOptions) {
                $prefix = $relationName . '_';

                if (strpos($field, $prefix) !== 0) {
                    continue;
                }

                $isRelation = true;
                $fieldWithoutPrefix = substr($field, strlen($prefix));

                if (! isset($formatByKey[$relationName])) {
                    $currentFormat = [
                        'key' => $relationName,
                        'object' => true,
                        'prefix' => $relationName . '_',
                        'child' => []
                    ];
                    $formatByKey[$relationName] = &$currentFormat;
                    $format[] = &$currentFormat;
                } else {
                    $currentFormat = &$formatByKey[$formatKey];
                }

                if (! isset($relations[$relationName]['instance'])) {
                    $relations[$relationName]['instance'] = new $relations[$relationName]['class'];
                }

                $relationInstance = $relations[$relationName]['instance'];
                $visible = $relationInstance->visible;

                if (isset($visible[0]) && ! in_array($fieldWithoutPrefix, $visible)) {
                    continue;
                }

                $currentFormat['child'][] = $this->processFormatDefinition(
                    $relationInstance,
                    $fieldWithoutPrefix,
                    [
                        'key' => $fieldWithoutPrefix
                    ]
                );
            }

            if ($isRelation) {
                continue;
            }

            $prefix = null;

            if ($entity->prefixAsObject) {
                foreach ($entity->prefixAsObject as $availablePrefix) {
                    if (strpos($field, $availablePrefix) === 0) {
                        $prefix = $availablePrefix;
                    }
                }
            }

            if ($prefix) {
                if (! isset($formatByKey[$prefix])) {
                    $currentFormat = [
                        'key' => $prefix,
                        'object' => true,
                        'prefix' => $prefix . '_',
                        'child' => []
                    ];
                    $formatByKey[$prefix] = &$currentFormat;
                    $format[] = &$currentFormat;
                } else {
                    $currentFormat = &$formatByKey[$prefix];
                }

                $fieldWithoutPrefix = substr($field, strlen($prefix) + 1);

                $currentFormat['child'][] = $this->processFormatDefinition(
                    $entity,
                    $field,
                    [
                        'key' => $fieldWithoutPrefix
                    ]
                );

            } else {
                $format[] = $this->processFormatDefinition(
                    $entity,
                    $field,
                    [
                        'key' => $field
                    ]
                );
            }
        }

        return $format;
    }

    private function makeTransformFunctions($format)
    {
        $functions = [];

        foreach ($format as $field) {
            $key = $field['key'];
            $prefix = isset($field['prefix']) ? $field['prefix'] : '';
            $isObject = isset($field['object']) ? $field['object'] : false;

            if (! $isObject) {
                $transformFunction = function (&$object, $row) use ($key, $field, $prefix) {
                    $value = $row[$prefix . $key];

                    if (isset($field['integer'])) {
                        $value = intval($value);
                    } elseif (isset($field['boolean'])) {
                        $value = !! $value;
                    } elseif (isset($field['float'])) {
                        $value = floatval($value);
                    } elseif (isset($field['date'])) {
                        $value = $value ? DateTime::createFromFormat('Y-m-d H:i:s', $value)->format('Y-m-d') : null;
                    } elseif (isset($field['datetime'])) {
                        $value = $value ? DateTime::createFromFormat('Y-m-d H:i:s', $value)->format('c') : null;
                    }

                    $object[$key] = $value;
                };
            } elseif ($isObject && isset($field['child'])) {
                //var_dump($field);die;
                $innerFunctions = $this->makeTransformFunctions(array_map(
                    function (&$innerField) use ($prefix) {
                        if ($prefix) {
                            $innerField['prefix'] = $prefix;
                        }

                        return $innerField;
                    },
                    $field['child']
                ));


                $transformFunction = function (&$object, $row) use ($key, $field, $innerFunctions) {
                    $innerObject = [];

                    foreach ($innerFunctions as $fn) {
                        $fn($innerObject, $row);
                    }

                    if (count(array_filter(array_values($innerObject))) || isset($field['required'])) {
                        $object[$key] = $innerObject;
                    }
                };
            }

            $functions[] = $transformFunction;
        }

        return $functions;
    }

    private function makeTransformFunction($format)
    {
        $functions = $this->makeTransformFunctions($format);

        return function ($row) use ($functions) {
            $object = [];
            foreach ($functions as $fn) {
                $fn($object, $row);
            }
            return $object;
        };
    }
}
