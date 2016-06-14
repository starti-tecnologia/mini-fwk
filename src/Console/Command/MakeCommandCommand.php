<?php

namespace Mini\Console\Command;

use Commando\Command as Commando;

class MakeCommandCommand extends AbstractCommand
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'make:command';
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Make a command class';
    }

    /**
     * @param Commando $commando
     */
    public function setUp(Commando $commando)
    {
        $commando->option('name')
            ->describedAs('Command name, example: "script:invoice:process"')
            ->required();

        $commando->option('description')
            ->describedAs('Command description, example: "Starts invoice processing"')
            ->defaultsTo('');
    }

    /**
     * @param Commando $commando
     */
    public function run(Commando $commando)
    {
        $className = ucwords(str_replace(':', ' ', $commando['name']));
        $className = str_replace(' ', '', $className);
        $path = app()->get('Mini\Kernel')->getCommandsPath();
        if (! is_dir($path)) {
            mkdir($path);
        }
        $file = $path . DIRECTORY_SEPARATOR .  $className . '.php';
        $replaces = [
            'ClassNamePlaceholder' => $className,
            'CommandNamePlaceholder' => $commando['name'],
            'CommandDescriptionPlaceholder' => $commando['description']
        ];
        $template = file_get_contents(__DIR__ . '/Templates/CommandTemplate.php');
        file_put_contents(
            $file,
            str_replace(array_keys($replaces), array_values($replaces), $template)
        );
        $this->write('Command file created at ' . $file, 'green');
    }
}
