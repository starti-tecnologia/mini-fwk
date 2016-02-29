<?php

namespace Mini\Entity\Definition;

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
     * @param array $input
     * @return array
     */
    public function parse(array $input)
    {
        $definition = [];

        foreach ($input as $key => $params) {
            $definition[$key] = [];

            foreach (explode('|', $params) as $rawTag) {
                $pieces = explode(':', $rawTag);
                $definition[$key][array_shift($pieces)] = $pieces;
            }
        }

        return $definition;
    }
}
