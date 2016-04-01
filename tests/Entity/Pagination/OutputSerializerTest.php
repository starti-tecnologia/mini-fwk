<?php

use Mini\Entity\Pagination\OutputSerializer;

class OutputSerializerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->outputSerializer = new OutputSerializer;
    }

    public function testGetTagsIsReturningTags()
    {
        $tags = $this->outputSerializer->getTags('photos|array|explode:$');

        $this->assertEquals(
            ['array' => '', 'explode' => '$'],
            $tags
        );
    }

    public function testSerializeIsWorkingWithSimpleObjects()
    {
        $result = $this->outputSerializer->serialize(
            [
                'rows' => [
                    [
                        'name' => 'Jonh Doe',
                        'is_male' => 1,
                        'u_name' => 'Jonh Done Goldenberg'
                    ]
                ],
                'total' => 1,
            ],
            [
                'format' => [
                    'name',
                    'is_male' => 1,
                    'user|object|prefix:u_' => [
                        'name'
                    ]
                ],
                'page' => 1,
                'perPage' => 10
            ]
        );

        $this->assertEquals(
            [
                'meta' => [
                    'pagination' => [
                        'count' => 1,
                        'current_page' => 1,
                        'per_page' => 10,
                        'total' => 1,
                        'total_pages' => 1
                    ]
                ],
                'data' => [
                    [
                        'name' => 'Jonh Doe',
                        'is_male' => 1,
                        'user' => [
                            'name' => 'Jonh Done Goldenberg'
                        ]
                    ]
                ]
            ],
            $result
        );
    }


    public function testSerializeIsConvertingToCamelCase()
    {
        putenv('CONVERT_CAMEL_CASE=1');

        $result = $this->outputSerializer->serialize(
            [
                'rows' => [
                    [
                        'name' => 'Jonh Doe',
                        'is_male' => 1
                    ]
                ],
                'total' => 1
            ],
            [
                'format' => [
                    'name',
                    'is_male' => 1
                ],
                'page' => 1,
                'perPage' => 10
            ]
        );

        $this->assertEquals(
            [
                'meta' => [
                    'pagination' => [
                        'count' => 1,
                        'current_page' => 1,
                        'per_page' => 10,
                        'total' => 1,
                        'total_pages' => 1
                    ]
                ],
                'data' => [
                    [
                        'name' => 'Jonh Doe',
                        'isMale' => 1
                    ]
                ]
            ],
            $result
        );

        putenv('CONVERT_CAMEL_CASE=0');
    }
}
