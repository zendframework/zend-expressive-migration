<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-migration for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-migration/blob/master/LICENSE.md New BSD License
 */

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
