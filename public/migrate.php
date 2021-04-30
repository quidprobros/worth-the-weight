<?php

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Events\Dispatcher;

require __DIR__ . '/../vendor/autoload.php';

/**
 * 設定資料庫連線
 */
$capsule = new Capsule();

$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => ':memory:',
]);

$capsule->setEventDispatcher(new Dispatcher(new Container));
$capsule->setAsGlobal();
$capsule->bootEloquent();

/**
 * 初始化必要參數
 */
$container = Container::getInstance();
$databaseMigrationRepository = new DatabaseMigrationRepository($capsule->getDatabaseManager(), 'migration');
$databaseMigrationRepository->createRepository();
$container->instance(MigrationRepositoryInterface::class, $databaseMigrationRepository);
$container->instance(ConnectionResolverInterface::class, $capsule->getDatabaseManager());

/**
 * 執行 migration
 */
$paths = [
    __DIR__ . '/migrations',
];

/** @var Migrator $migrator */
$migrator = $container->make(Migrator::class);
$migrator->run($paths);
//var_dump($migrator->getNotes());

/**
 * 執行 rollback
 */
$migrator->rollback($paths);
//var_dump($migrator->getNotes());
