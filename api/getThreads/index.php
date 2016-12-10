<?php
//Set up redis
$r = new Redis();
$r->connect('127.0.0.1');
$r->select(0);

function getThreads()
{
    //Get all threads.
    global $r;
    $threadNames = $r->keys('title_*');
    sort($threadNames);
    $threadsToGet = array();
    $threadIds = array();
    $threadLengthsToGet = array();
    foreach ($threadNames as $threadName) {
        $threadsToGet[] = $threadName;
        $threadIds[] = substr($threadName, 6);
        $threadLengthsToGet[] = "t_" . substr($threadName, 6);
    }
    $titles = $r->mGet($threadsToGet);
    $threadLengths = $r->mget($threadLengthsToGet);
    $threads = array();
    $i = 0;
    foreach ($threadsToGet as $thread) {
        $threads[] = array('title' => $titles[$i], 'id' => $threadIds[$i], 'length' => $threadLengths[$i]);
        $i++;
    }
    //Return structure: array of arrays with title and id
    return $threads;
}

echo json_encode(getThreads());
?>
