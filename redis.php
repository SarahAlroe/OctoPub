<?php
$redis = new Redis();
$redis->connect('127.0.0.1');
#$redis -> set('key', 'value');
echo $redis -> get('key');

?>