<?php
require __DIR__.'/../vendor/autoload.php';

$pusher = new Pusher\Pusher(
    'bcd87a9824594fff91de',
    'd2337adaccd71b03fef8',
    '2103345',
    ['cluster' => 'eu', 'useTLS' => true]
);

$result = $pusher->trigger('private-sync-control', 'sync.command', [
    'command' => 'test',
    'params' => ['test' => true],
]);

echo "Resultado: ";
var_dump($result);
