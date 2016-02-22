<?php

namespace Mini\Entity\Migration;

class TableSorter {
    public function sort($tableMap)
    {
        $sorter = new TopologicalSorter;

        foreach ($tableMap as $name => $table) {
            $name = $table->name;
            $dependencies = [];

            foreach ($tableMap as $other) {
                if ($other->name !== $table->name && $table->hasReference($other)) {
                    $dependencies[] = $other->name;
                }
            }

            $sorter->add($name, $dependencies);
        }

        $resultMap = [];

        foreach ($sorter->sort() as $source) {
            $resultMap[$source->name] = $tableMap[$source->name];
        }

        return $resultMap;
    }
}
