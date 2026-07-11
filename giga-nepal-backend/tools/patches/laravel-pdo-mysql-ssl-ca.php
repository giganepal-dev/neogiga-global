<?php

declare(strict_types=1);

$target = __DIR__.'/../../vendor/laravel/framework/config/database.php';

if (! is_file($target)) {
    fwrite(STDERR, "Laravel database config patch skipped: {$target} not found.\n");
    exit(0);
}

$contents = file_get_contents($target);

if ($contents === false) {
    fwrite(STDERR, "Laravel database config patch skipped: could not read {$target}.\n");
    exit(0);
}

$replacement = "(class_exists(\\Pdo\\Mysql::class) ? \\Pdo\\Mysql::ATTR_SSL_CA : constant('PDO::MYSQL_ATTR_SSL_CA')) => env('MYSQL_ATTR_SSL_CA'),";

if (str_contains($contents, $replacement)) {
    exit(0);
}

$updated = str_replace(
    'PDO::MYSQL_ATTR_SSL_CA => env(\'MYSQL_ATTR_SSL_CA\'),',
    $replacement,
    $contents,
);

if ($updated === $contents) {
    fwrite(STDERR, "Laravel database config patch skipped: expected PDO SSL constant pattern not found.\n");
    exit(0);
}

file_put_contents($target, $updated);

