<?php

use Doctrine\REST\Server\Server,
    Doctrine\Common\ClassLoader;

$pathToDoctrine = '/Users/jwage/Sites/doctrine2git/lib';

require $pathToDoctrine . '/Doctrine/Common/ClassLoader.php';

$classLoader = new ClassLoader('Doctrine\REST', __DIR__ . '/../lib');
$classLoader->register();

$classLoader = new ClassLoader('Doctrine', $pathToDoctrine);
$classLoader->register();

$classLoader = new ClassLoader('Entities', __DIR__);
$classLoader->register();

$config = new \Doctrine\ORM\Configuration();
$config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
$config->setProxyDir('/tmp');
$config->setProxyNamespace('Proxies');
$config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(__DIR__));

$connectionOptions = array(
  'driver' => 'pdo_mysql',
  'dbname' => 'rest_test',
  'user' => 'root'
);

$em = \Doctrine\ORM\EntityManager::create($connectionOptions, $config);

$parser = new \Doctrine\REST\Server\PHPRequestParser();
$requestData = $parser->getRequestArray();

$configuration = new \Doctrine\REST\Server\Configuration($em->getConnection());
$configuration->setBaseUrl('http://localhost/rest/example/server.php');
$configuration->setAuthenticatedUsername(isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null);
$configuration->setAuthenticatedPassword(isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null);
$configuration->setUsername('jwage');
$configuration->setPassword('jwage');

//$configuration->setCredentialsCallback(function($username, $password, $action, $entity, $id) {
//    return true;
//});

$userConfiguration = new \Doctrine\REST\Server\EntityConfiguration('user', 'users', 'user', 'users');
$userConfiguration->setIdentifierKey('id');
$userConfiguration->isReadOnly(false);
$userConfiguration->setUsername('jwage');
$userConfiguration->setPassword('jwage');

$configuration->registerEntity($userConfiguration);

$server = new \Doctrine\REST\Server\Server($configuration, $requestData);
$server->execute();
$server->getResponse()->send();