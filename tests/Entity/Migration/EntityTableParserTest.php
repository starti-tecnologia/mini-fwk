<?php

use Mini\Entity\Migration\EntityTableParser;

class EntityTableParserTest extends PHPUnit_Framework_TestCase
{
    /**
     * TODO: Test if a column can't have more than one type
     */
    public function testIsParsingEntity()
    {
        require_once __TEST_DIRECTORY__ . '/stubs/EntityStub.php';

        $entity = new EntityStub;
        $parser = new EntityTableParser;
        $table = $parser->parseEntity($entity);

        $sql = implode(
            ' ',
            [
                'CREATE TABLE users (',
                    'id INTEGER(11) UNSIGNED PRIMARY KEY,',
                    'guid VARCHAR(36),',
                    'email VARCHAR(255),',
                    'name VARCHAR(100),',
                    'password VARCHAR(100),',
                    'customer_id INTEGER(11) UNSIGNED',
                ');',
                'CREATE UNIQUE INDEX users_guid_unique ON users (guid);',
                'CREATE UNIQUE INDEX users_email_unique ON users (email);',
                'CREATE UNIQUE INDEX users_password_unique ON users (password);',
                'ALTER TABLE users ADD CONSTRAINT users_customer_id_fk FOREIGN KEY (customer_id) REFERENCES customers (id);',
            ]
        );

        $this->assertEquals($sql, $table->getCreateSql());
    }
}
