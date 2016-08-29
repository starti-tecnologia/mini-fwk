<?php

namespace Mini\Entity\Pagination;

use DateTime;

/**
 * Convert the response for paginator processor into a suitable api response
 *
 * Format:
 *     [
 *         'name',
 *         'user|prefix:u_|object' => [
 *             'name',
 *             'age|integer'
 *         ]
 *     ]
 *
 * Data:
 *
 *     [
 *         'name' => 'Patient name',
 *         'u_name' => 'User name',
 *         'u_age' => '12'
 *     ]
 *
 * Output:
 *
 *     [
 *         'name' => 'Patient name',
 *         'user' => [
 *             'name' => 'User name',
 *             'age' => '12'
 *         ]
 *     ]
 */

class OutputSerializer
{
    public function getTags($keyFormat)
    {
        $tags = [];

        foreach (explode('|', $keyFormat) as $i => $rawTag) {
            if ($i === 0) {
                continue;
            }

            list($tagName, $tagValue) = explode(':', $rawTag . ':');

            $tags[$tagName] = $tagValue;
        }

        return $tags;
    }

    public function createTransformFunctions($format)
    {
        $functions = [];

        foreach ($format as $keyFormat => $innerFormat) {
            if (is_numeric($keyFormat)) {
                $keyFormat = $innerFormat;
                $innerFormat = null;
            }

            $key = explode('|', $keyFormat)[0];
            $tags = $this->getTags($keyFormat);
            $prefix = array_key_exists('prefix', $tags) ? $tags['prefix'] : '';
            $isArray = array_key_exists('array', $tags);
            $isObject = array_key_exists('object', $tags);
            $isCallable = is_callable($innerFormat);

            if ($isCallable) {
                $transformerFunction = function (&$object, $row) use ($key, $innerFormat) {
                    $object[$key] = $innerFormat($row, $object);
                };
            } elseif (! $isObject && ! $isArray) {
                $transformerFunction = function (&$object, $row) use ($key, $tags, $prefix) {
                    $value = $row[$prefix . $key];

                    if (array_key_exists('integer', $tags)) {
                        $value = ! is_null($value) ? intval($value) : null;
                    } elseif (array_key_exists('boolean', $tags)) {
                        $value = ! is_null($value) ? !! $value : null;
                    } elseif (array_key_exists('float', $tags)) {
                        $value = ! is_null($value) ? floatval($value) : null;
                    } elseif (array_key_exists('date', $tags)) {
                        $df = $value && strlen($value) == 10 ? 'Y-m-d' : 'Y-m-d H:i:s';
                        $value = $value ? DateTime::createFromFormat($df, $value)->format('Y-m-d') : null;
                    } elseif (array_key_exists('datetime', $tags)) {
                        $df = $value && strlen($value) == 10 ? 'Y-m-d' : 'Y-m-d H:i:s';
                        $value = $value ? DateTime::createFromFormat($df, $value)->format('Y-m-d H:i:s') : null;
                    }

                    if (env('CONVERT_CAMEL_CASE')) {
                        $key = camel_case($key);
                    }

                    $object[$key] = $value;
                };
            } elseif ($isObject) {
                $innerFunctions = $this->createTransformFunctions(array_map(
                    function ($innerKeyFormat) use ($prefix) {
                        return $prefix && is_string($innerKeyFormat) ?
                            $innerKeyFormat . '|prefix:'.$prefix :
                            $innerKeyFormat;
                    },
                    $innerFormat
                ));

                $transformerFunction = function (&$object, $row) use ($key, $tags, $innerFunctions) {
                    $innerObject = [];

                    foreach ($innerFunctions as $fn) {
                        $fn($innerObject, $row);
                    }

                    if (count(array_filter(array_values($innerObject))) || array_key_exists('required', $tags)) {
                        $object[$key] = $innerObject;
                    }
                };
            }

            $functions[] = $transformerFunction;
        }

        return $functions;
    }

    public function serialize(array $result, array $options)
    {
        $format = $options['format'];
        $functions = $this->createTransformFunctions($format);

        $currentPageKey = 'current_page';
        $perPageKey = 'per_page';
        $totalPageKey = 'total_pages';

        if (env('CONVERT_CAMEL_CASE')) {
            $currentPageKey = 'currentPage';
            $perPageKey = 'perPage';
            $totalPageKey = 'totalPages';
        }

        $output = [
            'meta' => [
                'pagination' => [
                    'count' => count($result['rows']),
                    $currentPageKey => $options['page'],
                    $perPageKey => $options['perPage'],
                    'total' => $result['total'],
                    $totalPageKey => ceil($result['total'] / $options['perPage'])
                ]
            ],
            'data' => array_map(function ($row) use ($functions) {
                $object = [];
                foreach ($functions as $fn) {
                    $fn($object, $row);
                }
                return $object;
            }, $result['rows'])
        ];

        if (! empty($options['postProcess'])) {
            $output = $options['postProcess']($output);
        }

        return $output;
    }
}
