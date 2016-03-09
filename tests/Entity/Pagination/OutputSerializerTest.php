<?php

use Mini\Entity\Pagination\OutputSerializer;

class OutputSerializerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->searchOutputSerializer = new OutputSerializer;
    }

    public function testGetTagsIsReturningTags()
    {
        $tags = $this->searchOutputSerializer->getTags('photos|array|explode:$');

        $this->assertEquals(
            ['array' => '', 'explode' => '$'],
            $tags
        );
    }

    public function testSerializeIsWorkingWithSimpleObjects()
    {
        $result = $this->searchOutputSerializer->serialize(
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
                        'currentPage' => 1,
                        'perPage' => 10,
                        'total' => 1,
                        'totalPages' => 1
                    ]
                ],
                'data' => [
                    [
                        'name' => 'Jonh Doe',
                        'isMale' => 1,
                        'user' => [
                            'name' => 'Jonh Done Goldenberg'
                        ]
                    ]
                ]
            ],
            $result
        );
    }
}
