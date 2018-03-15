<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-migration for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-migration/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Migration;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class MigrateCommand extends Command
{
    /** @var InputInterface */
    private $input;

    /** @var OutputInterface */
    private $output;

    private $packages = [
        'zendframework/zend-diactoros',
        'zendframework/zend-component-installer',
        'zendframework/zend-expressive-hal',
        'zendframework/zend-problem-details',
        'zendframework/zend-stratigility',
    ];

    private $packagesPattern = '#^zendframework/zend-expressive#';

    private $skeletonVersion;

    protected function configure()
    {
        $this->setDescription('Migrate ZF Expressive application to the latest version.');
        $this->addArgument(
            'path',
            InputArgument::OPTIONAL,
            'Path to the expressive application',
            realpath(getcwd())
        );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $path = $input->getArgument('path') ?: getcwd();
        if (! is_dir($path)) {
            throw new InvalidArgumentException('Given path is not a directory.');
        }

        if (! is_writable(sprintf('%s/composer.json', $path))) {
            throw new InvalidArgumentException(sprintf(
                'File %s/composer.json does not exist or is not writable.',
                $path
            ));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $path = $input->getArgument('path');
        chdir($path);

        $packages = $this->findPackagesToUpdate();
        if (! isset($packages['zendframework/zend-expressive'])) {
            $output->writeln('<error>Package zendframework/zend-expressive has not been detected.</error>');
            return 1;
        }

        if (file_exists('composer.lock')) {
            $lock = json_decode(file_get_contents('composer.lock'), true);
            foreach ($lock['packages'] as $package) {
                if (strtolower($package['name']) === 'zendframework/zend-expressive'
                    && preg_match('/\d+\.\d+(\.\d+)?/', $package['version'], $matches)
                ) {
                    $version = $matches[0];
                    break;
                }
            }
        }

        if (! isset($version)) {
            $output->writeln('<error>Cannot detect expressive version.</error>');
            return 1;
        }

        $output->writeln(sprintf('<info>Detected expressive in version %s</info>', $version));

        if (strpos($version, '2.') !== 0) {
            $output->writeln(sprintf('<error>This tool can migrate only Expressive v2 applications</error>'));
            return 1;
        }

        $removePackages = [];
        if (isset($packages['aura/di'])) {
            $removePackages[] = 'aura/di';

            $packages['zendframework/zend-auradi-config'] = [
                'name' => 'zendframework/zend-auradi-config',
                'dev' => false,
            ];
        }

        if (isset($packages['pimple/pimple'])
            || isset($packages['xtreamwayz/pimple-container-interop'])
        ) {
            $removePackages[] = 'pimple/pimple';
            $removePackages[] = 'xtreamwayz/pimple-container-interop';

            $packages['zendframework/zend-pimple-config'] = [
                'name' => 'zendframework/zend-pimple-config',
                'dev' => false,
            ];
        }

        if (isset($packages['http-interop/http-middleware'])) {
            $removePackages[] = 'http-interop/http-middleware';
        }

        if ($removePackages) {
            exec(sprintf(
                'composer remove %s',
                implode(' ', $removePackages)
            ));
        }

        $this->updatePackages($packages);
        $this->updatePipeline();
        $this->updateRoutes();
        $this->replaceIndex();

        if (isset($packages['zendframework/zend-pimple-config'])) {
            $container = $this->getFileContent('src/ExpressiveInstaller/Resources/config/container-pimple.php');
            file_put_contents('config/container.php', $container);
        }

        if (isset($packages['zendframework/zend-auradi-config'])) {
            $container = $this->getFileContent('src/ExpressiveInstaller/Resources/config/container-aura-di.php');
            file_put_contents('config/container.php', $container);
        }

        $src = $this->getDirectory('Please provide the path to the application sources', 'src');
        $this->migrateInteropMiddlewares($src);

        $actionDir = $this->getDirectory(
            'Please provide the path to the application actions to be converted to request handlers'
        );

        $this->migrateMiddlewaresToRequestHandlers($actionDir);

        $this->csAutoFix();

        return 0;
    }

    private function csAutoFix() : void
    {
        $this->output->writeln('<question>Running CS auto-fixer</question>');
        if (file_exists('vendor/bin/phpcbf')) {
            exec('composer cs-fix', $output);
            $this->output->writeln($output);
        }
    }

    private function getDirectory(string $questionString, string $default = null) : string
    {
        $helper = $this->getHelper('question');
        $question = new Question(
            ($default ? sprintf('%s [<info>%s</info>]', $questionString, $default) : $questionString) . ': ',
            $default
        );
        $question->setValidator(function ($dir) {
            if (! $dir || ! is_dir($dir)) {
                throw new RuntimeException(sprintf('Directory %s does not exist. Please try again', $dir));
            }

            return $dir;
        });
        $src = $helper->ask($this->input, $this->output, $question);

        $this->output->writeln('<question>Provided directory is: ' . $src . '</question>');

        return $src;
    }

    private function migrateInteropMiddlewares(string $src) : void
    {
        exec(sprintf(
            'composer expressive -- migrate:interop-middleware --src %s',
            $src
        ), $output);

        $this->output->writeln($output);
    }

    private function migrateMiddlewaresToRequestHandlers(string $dir) : void
    {
        exec(sprintf(
            'composer expressive -- migrate:middleware-to-request-handler --src %s',
            $dir
        ), $output);

        $this->output->writeln($output);
    }

    private function updatePackages(array $packages) : void
    {
        exec('rm -Rf vendor');
        exec('composer install --no-interaction');

        $composer = $this->getComposerContent();
        $composer['config']['sort-packages'] = true;
        if (isset($composer['config']['platform']['php'])
            && strpos($composer['config']['platform']['php'], '7.1') === false
            && strpos($composer['config']['platform']['php'], '7.2') === false
            && strpos($composer['config']['platform']['php'], '7.3') === false
        ) {
            $composer['config']['platform']['php'] = '7.1.3';
        }

        // Add composer scripts
        if (file_exists('vendor/bin/phpcs')) {
            $composer['scripts']['cs-check'] = 'phpcs';
        }
        if (file_exists('vendor/bin/phpcbf')) {
            $composer['scripts']['cs-fix'] = 'phpcbf';
        }
        $composer['scripts']['expressive'] = 'expressive';

        $this->updateComposer($composer);

        if (isset($packages['zendframework/zend-component-installer'])) {
            $packages['zendframework/zend-component-installer']['dev'] = true;
        } else {
            $packages['zendframework/zend-component-installer'] = [
                'name' => 'zendframework/zend-component-installer',
                'dev' => true,
            ];
        }

        if (isset($packages['zendframework/zend-expressive-tooling'])) {
            $packages['zendframework/zend-expressive-tooling']['dev'] = true;
        } else {
            $packages['zendframework/zend-expressive-tooling'] = [
                'name' => 'zendframework/zend-expressive-tooling',
                'dev' => true,
            ];
        }

        $deps = [];
        $lock = json_decode(file_get_contents('composer.lock'), true);

        foreach (array_merge($lock['packages'], $lock['packages-dev'] ?? []) as $package) {
            $name = $package['name'];
            if (! $this->isPackageToUpdate($name)) {
                continue;
            }

            exec(sprintf('composer why %s', $name), $output, $returnCode);

            if ($returnCode !== 0) {
                continue;
            }

            foreach ($output as $line) {
                $exp = explode(' ', $line, 2);
                $deps[$exp[0]] = $exp[0];
            }
        }
        unset($deps[$composer['name']]);

        $extraRequire = [];
        $extraRequireDev = [];
        foreach ($deps as $dep) {
            if (isset($composer['require'][$dep])) {
                $extraRequire[] = $dep;
            }

            if (isset($composer['require-dev'][$dep])) {
                $extraRequireDev[] = $dep;
            }
        }

        $require = [];
        $requireDev = [];
        foreach ($packages as $name => $package) {
            if ($package['dev']) {
                $requireDev[] = $name;
            } else {
                $require[] = $name;
            }
        }

        $commands = [
            sprintf(
                'composer remove --dev %s --no-interaction',
                implode(' ', array_merge($require, $requireDev, $extraRequire, $extraRequireDev))
            ),
            sprintf(
                'composer remove %s --no-interaction',
                implode(' ', array_merge($require, $requireDev, $extraRequire, $extraRequireDev))
            ),
            sprintf('composer update --no-interaction'),
            sprintf('composer require --dev %s --no-interaction', implode(' ', $requireDev)),
            sprintf('composer require %s --no-interaction', implode(' ', $require)),
            sprintf('composer require %s --no-interaction', implode(' ', $extraRequire)),
            sprintf('composer require --dev %s --no-interaction', implode(' ', $extraRequireDev)),
        ];

        foreach ($commands as $command) {
            $this->output->writeln('<question>' . $command . '</question>');
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                $this->output->writeln(
                    '<error>Error occurred on executing above command. Please see logs above</error>'
                );
                return;
            }
        }
    }

    private function updatePipeline() : void
    {
        $this->output->write('<info>Updating pipeline...</info>');

        if (! $this->addFunctionWrapper('config/pipeline.php')) {
            $this->output->writeln(' <comment>SKIPPED</comment>');
            return;
        }

        $pipeline = file_get_contents('config/pipeline.php');

        $replacement = [
            '->pipeRoutingMiddleware();' =>
                '->pipe(\Zend\Expressive\Router\Middleware\RouteMiddleware::class);',
            '->pipeDispatchMiddleware();' => '->pipe(\Zend\Expressive\Router\Middleware\DispatchMiddleware::class);',
            'Zend\Expressive\Middleware\NotFoundHandler' => 'Zend\Expressive\Handler\NotFoundHandler',
            'Zend\Expressive\Middleware\ImplicitHeadMiddleware' =>
                'Zend\Expressive\Router\Middleware\ImplicitHeadMiddleware',
            'Zend\Expressive\Middleware\ImplicitOptionsMiddleware' =>
                'Zend\Expressive\Router\Middleware\ImplicitOptionsMiddleware',
        ];

        $pipeline = strtr($pipeline, $replacement);

        // Find the latest
        $search = [
            'RouteMiddleware::class);' => false,
            'ImplicitHeadMiddleware::class);' => false,
            'ImplicitHeadMiddleware\');' => false,
            'ImplicitHeadMiddleware");' => false,
            'ImplicitOptionsMiddleware::class);' => false,
            'ImplicitOptionsMiddleware");' => false,
        ];

        foreach ($search as $string => &$pos) {
            $pos = strrpos($pipeline, $string);
        }
        arsort($search);

        $string = key($search);
        $pipeline = preg_replace(
            '/' . preg_quote($string, '/') . '/',
            $string . PHP_EOL . '$app->pipe(\Zend\Expressive\Router\Middleware\MethodNotAllowedMiddleware::class);',
            $pipeline
        );

        file_put_contents('config/pipeline.php', $pipeline);

        $this->output->writeln(' <comment>DONE</comment>');
    }

    private function updateRoutes() : void
    {
        $this->output->write('<info>Updating routes...</info>');

        if (! $this->addFunctionWrapper('config/routes.php')) {
            $this->output->writeln(' <comment>SKIPPED</comment>');
        }

        $this->output->writeln(' <comment>DONE</comment>');
    }

    private function replaceIndex() : void
    {
        $this->output->write('<info>Replacing index.php...</info>');
        $index = $this->getFileContent('public/index.php');

        file_put_contents('public/index.php', $index);
        $this->output->writeln(' <comment>DONE</comment>');
    }

    private function detectLastSkeletonVersion(string $match) : string
    {
        if (! $this->skeletonVersion) {
            $this->skeletonVersion = 'master';

            $package = json_decode(
                file_get_contents('https://packagist.org/p/zendframework/zend-expressive-skeleton.json'),
                true
            );

            $versions = array_reverse($package['packages']['zendframework/zend-expressive-skeleton']);

            foreach ($versions as $version => $details) {
                if (strpos($version, $match) === 0) {
                    $this->output->write(sprintf(' <info>from skeleton version: %s</info>', $version));
                    $this->skeletonVersion = $version;
                    break;
                }
            }
        }

        return $this->skeletonVersion;
    }

    private function getFileContent(string $path) : string
    {
        $version = $this->detectLastSkeletonVersion('3.');
        $uri = sprintf(
            'https://raw.githubusercontent.com/zendframework/zend-expressive-skeleton/%s/',
            $version
        );

        return file_get_contents($uri . $path);
    }

    private function addFunctionWrapper(string $file) : bool
    {
        if (! file_exists($file)) {
            return false;
        }

        $contents = file_get_contents($file);

        if (strpos($contents, 'return function') !== false) {
            return false;
        }

        if (strpos($contents, 'strict_types') === false) {
            $contents = str_replace('<?php', '<?php' . PHP_EOL . PHP_EOL . 'declare(strict_types=1);', $contents);
        }

        $contents = preg_replace(
            '/^\s*\$app->/m',
            sprintf(
                'return function (' . PHP_EOL
                    . '    \%s $app,' . PHP_EOL
                    . '    \%s $factory,' . PHP_EOL
                    . '    \%s $container' . PHP_EOL
                    . ') : void {',
                \Zend\Expressive\Application::class,
                \Zend\Expressive\MiddlewareFactory::class,
                \Psr\Container\ContainerInterface::class
            ) . PHP_EOL . '\\0',
            $contents,
            1
        );

        $contents = trim($contents) . PHP_EOL . '};' . PHP_EOL;

        file_put_contents($file, $contents);
        return true;
    }

    private function getComposerContent() : array
    {
        return json_decode(file_get_contents('composer.json'), true);
    }

    private function updateComposer(array $data) : void
    {
        file_put_contents('composer.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return array {
     *     @var string $name
     *     @var string $constraint
     *     @var bool $dev
     * }
     */
    private function findPackagesToUpdate() : array
    {
        $packages = [];
        $composer = $this->getComposerContent();

        foreach ($composer['require'] as $package => $constraint) {
            $package = strtolower($package);
            if ($this->isPackageToUpdate($package)) {
                $packages[$package] = [
                    'name'       => $package,
                    'constraint' => $constraint,
                    'dev'        => false,
                ];
            }
        }

        foreach ($composer['require-dev'] as $package => $constraint) {
            $package = strtolower($package);
            if ($this->isPackageToUpdate($package)) {
                $packages[$package] = [
                    'name'       => $package,
                    'constraint' => $constraint,
                    'dev'        => true,
                ];
            }
        }

        return $packages;
    }

    private function isPackageToUpdate(string $name) : bool
    {
        return in_array($name, $this->packages, true) || preg_match($this->packagesPattern, $name);
    }
}
