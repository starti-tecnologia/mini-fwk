<?php

use Mini\Entity\Migration\EntityTableParser;
use Mini\Validation\Validator;

class EntityTableParserTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        require_once __TEST_DIRECTORY__ . '/stubs/EntityStub.php';
        require_once __TEST_DIRECTORY__ . '/stubs/FieldOrderEntityStub.php';
        require_once __TEST_DIRECTORY__ . '/stubs/EmptyDefaultEntityStub.php';
        require_once __TEST_DIRECTORY__ . '/stubs/EntityIndexStub.php';

        app()->register('Mini\Validation\Validator', function () {
            return new Validator;
        });
    }

    /**
     * TODO: Test if a column can't have more than one type
     */
    public function testIsParsingEntity()
    {
        $entity = new EntityStub;
        $parser = new EntityTableParser;
        $table = $parser->parseEntity($entity);

        $sql = implode(
            '',
            [
                'CREATE TABLE users ( ',
                    'id int(11) unsigned not null primary key auto_increment,',
                    'guid varchar(36),',
                    'email varchar(255),',
                    'name varchar(100),',
                    'password varchar(100),',
                    'customer_id int(11) unsigned',
                ' ) ENGINE=InnoDB COMMENT \'MINI_FWK_ENTITY\';',
                'CREATE UNIQUE INDEX users_guid_unique ON users (guid);',
                'CREATE UNIQUE INDEX users_email_unique ON users (email);',
                'CREATE UNIQUE INDEX users_password_unique ON users (password);',
                'ALTER TABLE users ADD CONSTRAINT users_customer_id_fk FOREIGN KEY (customer_id) REFERENCES customers (id)',
            ]
        );

        $this->assertEquals($sql, $table->makeCreateSql());
    }

    public function testIsKeepingTheOrderOfTheFields()
    {
        $entity = new FieldOrderEntityStub;
        $parser = new EntityTableParser;
        $table = $parser->parseEntity($entity);

        $sql = implode(
            '',
            [
                'CREATE TABLE users ( ',
                    'id int(11) unsigned not null primary key auto_increment,',
                    'field0 int(11) unsigned,',
                    'field1 varchar(255),',
                    'field2 int(11) unsigned,',
                    'field3 varchar(255),',
                    'field4 int(11) unsigned,',
                    'field5 varchar(255),',
                    'field6 int(11) unsigned,',
                    'field7 varchar(255),',
                    'field8 int(11) unsigned,',
                    'field9 varchar(255)',
                ' ) ENGINE=InnoDB COMMENT \'MINI_FWK_ENTITY\';',
                'ALTER TABLE users ADD CONSTRAINT users_field0_fk FOREIGN KEY (field0) REFERENCES table (id);',
                'ALTER TABLE users ADD CONSTRAINT users_field2_fk FOREIGN KEY (field2) REFERENCES table (id);',
                'ALTER TABLE users ADD CONSTRAINT users_field4_fk FOREIGN KEY (field4) REFERENCES table (id);',
                'ALTER TABLE users ADD CONSTRAINT users_field6_fk FOREIGN KEY (field6) REFERENCES table (id);',
                'ALTER TABLE users ADD CONSTRAINT users_field8_fk FOREIGN KEY (field8) REFERENCES table (id)',
            ]
        );

        $this->assertEquals($sql, $table->makeCreateSql());
    }

    public function testIsAllowingEmptyDefaults()
    {
        $entity = new EmptyDefaultEntityStub;
        $parser = new EntityTableParser;
        $table = $parser->parseEntity($entity);

        $sql = implode(
            '',
            [
                'CREATE TABLE users ( ',
                    'id int(11) unsigned not null primary key auto_increment,',
                    'field varchar(20) default \'\'',
                ' ) ENGINE=InnoDB COMMENT \'MINI_FWK_ENTITY\''
            ]
        );

        $this->assertEquals($sql, $table->makeCreateSql());
    }

    public function testIsParsingEntityIndexes()
    {
        $entity = new EntityIndexStub;
        $parser = new EntityTableParser;
        $table = $parser->parseEntity($entity);

        $sql = implode(
            '',
            [
                'CREATE TABLE users ( ',
                    'id int(11) unsigned not null primary key auto_increment,',
                    'name varchar(255),',
                    'title varchar(255)',
                ' ) ENGINE=InnoDB COMMENT \'MINI_FWK_ENTITY\';',
                'CREATE INDEX name ON users (name);',
                'CREATE UNIQUE INDEX name_title ON users (name,title)',
            ]
        );

        $this->assertEquals($sql, $table->makeCreateSql());
    }
}
