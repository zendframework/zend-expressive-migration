<?php

declare(strict_types=1);

namespace Zend\Expressive\Migration;

class Application extends \Symfony\Component\Console\Application
{
    public function __construct(string $name = 'expressive-migration', string $version = 'UNKNOWN')
    {
        parent::__construct($name, $version);

        $this->addCommands([
            new MigrateCommand('migrate'),
        ]);
    }
}
