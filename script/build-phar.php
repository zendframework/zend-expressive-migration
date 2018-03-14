#!/usr/bin/env php
<?php

declare(strict_types=1);

error_reporting(E_ALL | E_STRICT);

if (ini_get('phar.readonly') === '1') {
    echo 'Unable to build, phar.readonly in php.ini is set to read only.' . PHP_EOL;
    exit(1);
}

exec('composer install --prefer-dist --no-dev -o');

$script = 'expressive-migration';

echo "Building $script phar" . PHP_EOL;

$pharName = $script . '.phar';
$pharFile = getcwd() . '/' . $pharName;
echo "\t=> $pharFile" . PHP_EOL;
if (file_exists($pharFile) === true) {
    echo "\t** file exists, removing **" . PHP_EOL;
    unlink($pharFile);
}

$phar = new Phar($pharFile, 0, $pharName);

// Add the files.
echo "\t=> adding files... ";

foreach (['src', 'vendor'] as $dir) {
    $srcDir = realpath(__DIR__ . '/../' . $dir);
    $srcDirLen = strlen($srcDir);

    $rdi = new \RecursiveDirectoryIterator($srcDir, \RecursiveDirectoryIterator::FOLLOW_SYMLINKS);
    $di = new \RecursiveIteratorIterator($rdi, 0, \RecursiveIteratorIterator::CATCH_GET_CHILD);

    foreach ($di as $file) {
        $filename = $file->getFilename();

        // Skip hidden files.
        if (substr($filename, 0, 1) === '.') {
            continue;
        }

        $fullpath = $file->getPathname();
        $path = $dir . substr($fullpath, $srcDirLen);

        $phar->addFromString($path, php_strip_whitespace($fullpath));
    }
}

// Add licence file.
$phar->addFromString('licence.txt', php_strip_whitespace(realpath(__DIR__ . '/../LICENSE.md')));

echo 'done' . PHP_EOL;

// Add the stub.
echo "\t=> adding stub... ";
$stub = '#!/usr/bin/env php' . "\n";
$stub .= '<?php' . "\n";
$stub .= 'Phar::mapPhar(\'' . $pharName . '\');' . "\n";
$stub .= 'require_once "phar://' . $pharName . '/vendor/autoload.php";' . "\n";
$stub .= 'exit((new \Zend\Expressive\Migration\Application())->run());' . "\n";
$stub .= '__HALT_COMPILER();';
$phar->setStub($stub);

echo 'done' . PHP_EOL;

exec('composer install');
