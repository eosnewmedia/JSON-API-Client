<?php
require_once __DIR__ . '/vendor/autoload.php';

$uri = new \Enm\JsonApi\Client\Model\Request\Uri('http://example.com/api/messages/abc/asas');

$urlPrefix = '/api';

$path = trim(ltrim(trim($uri->getPath(), '/'), trim($urlPrefix, '/')), '/');

var_dump($path);

list($type, $id) = explode('/', $path);

var_dump('Type: ' . $type, 'Id: ' . $id);