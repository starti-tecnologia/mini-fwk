<?php

namespace Mini\Entity\Definition;

use Mini\Entity\Entity;

class DefinitionParser
{
    /**
     * Converts a entity definition into a array that maps keys, tags and tags attributes. For example:
     *
     * [
     *     'first_name' => [
     *         'string' => [100],
     *         'unique' => []
     *     ]
     * ]
     *
     * @param Entity $entity
     * @return array
     */
    public function parse(Entity $entity)
    {
        $definition = [];

        foreach ($entity->definition as $key => $params) {
            $definition[$key] = [];

            foreach (explode('|', $params) as $rawTag) {
                $pieces = explode(':', $rawTag);
                $definition[$key][array_shift($pieces)] = $pieces;
            }
        }

        return $definition;
    }
}
