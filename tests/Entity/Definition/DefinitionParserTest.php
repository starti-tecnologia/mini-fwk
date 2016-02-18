<?php

use Mini\Entity\Definition\DefinitionParser;

class DefinitionParserTest extends PHPUnit_Framework_TestCase
{
    public function testIsParsing()
    {
        require_once __TEST_DIRECTORY__ . '/stubs/EntityStub.php';

        $entity = new EntityStub;
        $parser = new DefinitionParser;
        $definition = $parser->parse($entity);

        $this->assertEquals(
            [
                'string' => ['100', 'unusedparameter'],
                'unique' => []
            ],
            $definition['password']
        );
    }
}
