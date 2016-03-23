<?php

use Mini\Entity\Definition\DefinitionParser;

class DefinitionParserTest extends PHPUnit_Framework_TestCase
{
    public function testIsParsing()
    {
        require_once __TEST_DIRECTORY__ . '/stubs/EntityStub.php';

        $entity = new EntityStub;
        $parser = new DefinitionParser;
        $definition = $parser->parse($entity->definition);

        $this->assertEquals(
            [
                'string' => ['100', 'unusedparameter'],
                'unique' => []
            ],
            $definition['password']
        );
    }

    public function testIsEmptyValues()
    {
        $entity = new EntityStub;
        $parser = new DefinitionParser;
        $definition = $parser->parse([
            'field' => 'string:20|default:'
        ]);

        $this->assertEquals(
            [
                'string' => [20],
                'default' => ['']
            ],
            $definition['field']
        );
    }
}
