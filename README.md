# Mini-Fwk

Mini-fwk is a PHP framework for that focus on performance. It has the following features:

* MVC Structure
* MySQL ORM with support to query building
* MongoDB ODM with support to query building
* Data migrations and seeds
* Console commands
* Background job queues
* Application hooks and dependency container
* Helper functions

OBS: This documentation does not cover all the framework features. Feel free to add more examples or sections.

### Installation

Run the following command to clone the example project and install dependencies

```sh
$ git clone git@187.87.153.124:git-starti/mini-fwk-project.git myproject
$ cd myproject
$ rm -Rf .git
$ cp .env.example .env
```

### MVC Structure

##### Controllers

Controllers are the entrypoint for HTTP Requests. Special annotations are used to define urls and middlewares for controller methods. Store controllers at folder `src/Controllers` following the example:

```php
<?php

namespace App\Controllers;

use Mini\Helpers\Request;

class ExampleController
{
    /**
     * @Get("/example")
     * @Middleware("permission:SOME_PERMISSION")
     */
    public function index()
    {
        $data = Request::instance()->get('data');
        response()->json(['data' => $data]);
    }
}
```

##### Routing

By the default, the files relatad to url routing are stored in `src/routes`. As the controllers can define routes with annotations you won't need to edit them. To generate routes from your controller annotations run the following command inside your project directory:

```sh
$ ./console route:scan
```

##### Request and Response

You can read and write JSON with `Mini\Helpers\Request` and `Mini\Helpers\Response`. See the following examples:

```php
<?php

namespace App\Controllers;

class RequestExampleController
{
    /**
     * @Get("/request-example")
     */
    public function index()
    {
        $req = Mini\Helpers\Request::instance();
        echo $req->get('data.name'); // Get the key from the JSON input, $_REQUEST or $_FILES using *dots* to represent nested arrays

        (new Mini\Helpers\Response)->json(['data'=>['token'=>'ab']], 200); // Output {data: {token: 'ab'}}

        response()->json(['data'=>['token'=>'ab']], 200); // Use a helper to do the same
    }
}
```

##### Middlewares

Middlewares are useful for executing some logic before the controller method. Store middlewares at folder `src/Middlewares` and then update `src/routers/middlewares.php` following the example:

```php
<?php
// src/routers/middlewares.php

return [
    'permission' => App\Middlewares\PermissionMiddleware::class
];
```

```php
<?php
// src/Middlewares/PermissionMiddleware.php

namespace App\Middlewares;

class PermissionMiddleware
{
    public function handler($permission)
    {
        $auth = app()->get('App/Auth'); // Use the dependency container to store Auth class
        $token = $auth->getAccessToken();

        if ($token === null) {
            response()->json([
                'error' => [
                    'code' => '0001',
                    'detail' => 'Unauthorized.',
                ]
            ], 401);
        } else if ($auth->hasPermission($permission) === false) {
            response()->json([
                'error' => [
                    'code' => '0001',
                    'detail' => 'Forbidden.',
                ]
            ], 403);
        }
    }
}
```

##### Validation

Data validation works with the class `Mini\Validation\Validator` and the currently supported rules are: required, char, string, text, integer, float, double, decimal, boolean, date, datetime, time, email, maxLength, minLength, min, max.

You can check examples on [unit tests](http://187.87.153.124:3000/jonathas/mini-fwk/blob/master/tests/Validation/ValidatorTest.php)

```php
<?php

namespace App\Controllers;

use Mini\Helpers\Request;
use Mini\Controllers\BaseControllers; // Implements validate and validateEntity
use App\Models\User;
use App\Models\Retailer;

class ValidationExampleController
{
    /**
     * @Get("/validation-example")
     */
    public function index()
    {
        // Complete example
        $validator = app()->get('Mini\Validation\Validator');
        $validator->setData(Request::instance()->get('data'))
        $validator->validate([
            'name' => 'string|required', // Rules are separeted by '|'
            'addresses.*.street' => 'string|required' // Validate nested arrays inside 'addresses' key
        ]);
        // Will throw ValidationException if error is found
        echo 'Data successfuly validated';

        // Example using validate method. So you don't need a $validator instance
        $this->validate([
            'name' => 'string:6:255', // Limit by length between 6 and 255 chars
        ]);

        // Example using rules from model classe
        $this->validateEntity(new User);

        // Example using multiple models
        $this->validateEntities([
            '*' => new Retailer,
            'owner' => new User
        ]);
    }
}
```

##### Services

Services are stored in `src/Services`, they are used to contain separate http logic (controllers) from business logic and can extend `Mini\Entity\DataMapper`.

##### Models

Models represent business objects like 'User' or 'Retailer' and contain data related to attribute schema. They are stored in `src/Models` following the example:

```php
<?php

namespace App\Models;

use Mini\Entity\Entity;
use Mini\Entity\Behaviors\QueryAware;

class User extends Entity
{
    use QueryAware; // Implement methods from MySQL ORM. Example: User::q()->listObject();

    /**
     * Table name used in MySQL
     *
     * @var string
     */
    public $table = 'users';

    /**
     * Define fields 'updated_at' and 'created_at' to control timestamps
     *
     * @var bool
     */
    public $useTimeStamps = true;

    /**
     * Define field 'deleted_at' to mark a row as deleted. Further calls to User::q() will automatically check for this field
     *
     * @type bool
     */
    public $useSoftDeletes = true;

    /**
     * Field definition
     *
     * @type array
     */
    public $definition = [
        'id' => 'pk',
        'name' => 'string',
        'password' => 'string'
    ];

    /**
     * Fields that are filled and validated
     *
     * @var array
     */
    public $fillable = [
        'name',
        'password'
    ];

    /**
     * Fields that are serialized with json_encode
     *
     * @var array
     */
    public $visible = [
        'id',
        'name'
    ];
}
```

### MySQL ORM

You can build complex MySQL queries with `Mini\Entity\Query` class. Follow the examples.

```php
<?php

use Mini\Entity\Query;
use App\Models\User;

// Complete example

$query = (new Query)
    ->connection('default')
    ->from('users')
    ->alias('u')
    ->select(['u.id', 'u.name', 'um.email'])
    ->innerJoin('user_emails um', 'um.user_id', '=', 'u.id')
    ->where('id', '=', 1);

$user = $query->getArray();

// Generating an sql

$sql = $query->makeSql();


// Using entity query alias in a Model that uses the trait `Mini\Entity\Behaviors\QueryAware`

$users = User::q()->limit(0, 1)->listObject(); // Can be listArray if you dont need an object
```

You can check examples on [unit tests](http://187.87.153.124:3000/jonathas/mini-fwk/blob/master/tests/Entity/QueryTest.php)

### MongoDB ODM

You can build complex MySQL queries with `Mini\Entity\Query` class. Follow the examples.

```php
<?php

use Mini\Entity\Mongo\Query;
use App\Models\User;

// Complete example

$chatMessages = (new Query('mongo', 'chat_messages'))
    ->filter('chat_id', 1)
    ->projection(['description' => 1])
    ->sort(['timestamp' => 1])
    ->skip(5)
    ->limit(10)
    ->listArray();

// Using entity query alias in a Model that uses the trait `Mini\Entity\Mongo\Behaviors\MongoQueryAware`

$chatMessages = ChatMessage::q()->filter('chat_id', 1)->listArray();
```

### Data migrations and seeds

Data migrations and seeds are essential to keep mysql schemas and default data in sync between development and production environment. There are are two ways of creating a migration: manually and automatic. The most common is to generate a migration that automatically check for differences in your entity field definition and mysql information schema. Use the following example:

##### Migrations


```sh
./console make:migration --diff # Create a migration for all tables
./console make:migration --diff --force # Force "alter tables" on "not null" columns
./console make:migration --diff --filter '(permissoes|perfil_permissoes)' # Check only tables matching the pattern
./console migrate # Run this after checking if the generated migration is ok
```

In other moments, creating a migration manually will be needed. Run the following command and check for the created migration.

```sh
$ ./console make:migration
Migration file created at /home/jeferson/Projects/Starti/starti-backoffice-api/migrations/Migration20170531174950.php
```

```php
<?php

use Mini\Entity\Migration\AbstractMigration;

class Migration20170531174950 extends AbstractMigration
{
    public $connection = 'default';

    public function up()
    {
        // this method is auto-generated, please modify it to your needs
        $this->addSql('UPDATE users SET email = NULL WHERE email = \'\'');
    }

    public function down()
    {
        // this method is auto-generated, please modify it to your needs
    }
}
```

##### Seeds

When using seeds be careful to use initial seeds only for things that will not change or be added in production.

Create files in 'seeds/initial/YOUR_TABLE_NAME' or 'seeds/test/YOUR_TABLE_NAME' following this example:

```php
<?php

return [
  'connection' => 'default',

   'rows' => [
       [
           'id' => '1', // Primary keys is required
           'name' => 'AdmFirewall',
       ],
       [
           'id' => '2',
           'name' => 'AdmVoice',
       ]
   ]
];
```

Then you can run seeds with either "--initial" or "--test" flags. This command will remove all rows from your seeded tables that are not in the file.

```sh
$ ./console db:seed --initial
```

### Console commands

The console executable available in the root directory of your project can execute several framework specific commands. But it can execute user generated commands too.

```sh
$ ./console make:command --name script:license:refresh --description "Update license file"
Command file created at /home/jeferson/Projects/Starti/starti-backoffice-api/src/Commands/ScriptLicenseRefresh.php
```

```php
<?php

namespace App\Commands;

use Mini\Console\Command\AbstractCommand;
use Commando\Command as Commando;

class ScriptLicenseRefresh extends AbstractCommand
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'script:license:refresh';
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Update license file';
    }

    /**
     * @param Commando $commando
     */
    public function setUp(Commando $commando)
    {
        /**
         * Example:
         *
         * $commando->option('name')
         *     ->describedAs('Command name, example: "script:invoice:process"')
         *     ->defaultsTo('');
         */
    }

    /**
     * @param Commando $commando
     */
    public function run(Commando $commando)
    {
        /**
         * Example:
         *
         * echo $commando['name'];
         */
    }
}
```

Then you can run your command

```php
./console script:license:refresh
```

### Background job queues

Some tasks like sending e-mails and importing data need to be executed in background. Workers are processes that run waiting for queued commands. First, install beanstalkd in your machine.

```sh
$ apt-get install beanstalkd # Ubuntu/Debian
$ yum install beanstalkd # Fedora/Centos
```

Step 1: Setup beanstalkd in your .env file

```sh
printf 'WORKER_DRIVER=BEANSTALKD\nBEANSTALKD_HOST="127.0.0.1"\nBEANSTALKD_PORT=11300' >> .env
```

Step 2: Create a worker class file in `src/Workers`

```sh
$ ./console make:worker --name ImportFile
```

Step 3: Edit the file `src/Workers/ImportFileWorker` and run the worker

```sh
$ ./console worker --run ImportFile # In production use something like supervisord to keep the process running forever
```

Step 4: Send jobs

```php
<?php

namespace App\Controllers;

use Mini\Helpers\Request;
use Mini\Workers\WorkerQueue;

class ExampleController
{
    /**
     * @Get("/example")
     * @Middleware("permission:SOME_PERMISSION")
     */
    public function index()
    {
        WorkerQueue::addQueue(
            'SendEmail',
            [
                'someparam' => 'someargument'
            ]
        );
    }
}
```

### Application hooks and dependency container

The file `src/Application.php` can be used to setup classes and handling exceptions. Example:

```php
<?php

namespace Mini;

use Throwable;

/**
 * Application
 *
 * Handle application specific behaviors using predefined hooks methods. You can extend it in your app
 *
 * @package Mini
 */
class Application
{
    public function afterContainerSetUp()
    {
        // Is exected before router initialize
    }

    public function afterConfigurationSetup()
    {
        // Is exected before router initialize
    }

    public function onException($exception)
    {
        if ($exception instanceof \Mini\Validation\ValidationException) {
            response()->json([
                'error' => [
                    'detail' => $exception->errors
                ]
            ], 400);
        } else {
            response()->json([
                'error' => [
                    'detail' => $exception->getMessage() . ' ' . $exception->getTraceAsString()
                ]
            ], 500);
        }
    }
}
```

### Helper functions

Thare are some global functions that come with the framework. Examples:

```php

// Get an item from an array using "dot" notation.
array_get($_POST, 'user.email');

// Get variables from .env file
env('DATABASE_NAME');

// Filter array keys
array_only(['name' => 'John', 'password' => '123'], ['name']);

// Exclude array keys
array_except(['name' => 'John', 'password' => '123'], ['password']);
```

You can check more examples on [source code](http://187.87.153.124:3000/jonathas/mini-fwk/blob/master/src/Helpers/Instance/helpers.php)
