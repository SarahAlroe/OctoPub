<?php
//Set up redis
$r = new Redis();
$r->connect('127.0.0.1');
$r->select(0);

//Check what kind of request is received and handle it appropriately
if (isset($_REQUEST["fromId"])) {
    echo json_encode(getFrom($_REQUEST["thread"], $_REQUEST["fromId"]));
} elseif (isset($_REQUEST["getHistoryFrom"])) {
    echo json_encode(getHistory($_REQUEST["getHistoryFrom"]));
} elseif (isset($_REQUEST["addMessage"])) {
    newMsg(htmlspecialchars($_REQUEST["thread"], ENT_QUOTES), substr(htmlspecialchars($_REQUEST["addMessage"], ENT_QUOTES), 0, 1000), htmlspecialchars($_REQUEST["UserId"], ENT_QUOTES));
    updateThread(htmlspecialchars($_REQUEST["thread"], ENT_QUOTES));
} elseif (isset($_REQUEST["addThread"])) {
    newThread(htmlspecialchars($_REQUEST["addThread"], ENT_QUOTES), substr(htmlspecialchars($_REQUEST["title"], ENT_QUOTES), 0, 200), substr(htmlspecialchars($_REQUEST["text"], ENT_QUOTES), 0, 1000));
    echo true;
} elseif (isset($_REQUEST["getThreads"])) {
    echo json_encode(getThreads());
} elseif (isset($_REQUEST["getThread"])) {
    echo json_encode(getThread($_REQUEST["getThread"]));
}


function getFrom($prefix, $id)
{
    //Gets all messages from a thread since id.
    global $r;
    $messages = array();
    $latestMsg = $r->get("t_" . $prefix);
    $messagesToGet = array();
    for ($i = $id; $i <= $latestMsg; $i++) {
        $messagesToGet[$i - $id] = $prefix . "_" . $i;
    }
    $jmessages = $r->mGet($messagesToGet);
    foreach ($jmessages as $jmessage) {
        $message = json_decode($jmessage);
        if ($message != null) {
            $messages[] = $message;
        }
    }
    return $messages;
}

function getHistory($prefix)
{
    //Gets latest 20 messages from a thread.
    global $r;
    $messages = array();
    $latestMsg = $r->get("t_" . $prefix);
    $messagesToGet = array();
    for ($i = $latestMsg - 20; $i <= $latestMsg; $i++) {
        $messagesToGet[$i - $latestMsg + 20] = $prefix . "_" . $i;
    }
    $jmessages = $r->mGet($messagesToGet);
    foreach ($jmessages as $jmessage) {
        $message = json_decode($jmessage);
        if ($message != null) {
            $messages[] = $message;
        }
    }
    return $messages;
}

function newMsg($prefix, $msg, $userId)
{
    //Create new message on a thread using a message text and user id.
    global $r;
    $msgId = $r->incr("t_" . $prefix);
    $msgArray = json_encode(array($msg, $userId, time(), $msgId));
    $r->set($prefix . "_" . $msgId, $msgArray);
    $r->setTimeout($prefix . "_" . $msgId, 604800);
}

function newThread($prefix, $title, $text)
{
    //Create a new thread from a previously generated id and text.
    global $r;
    $threadCount = count($r->keys("t_*"));
    $baseTimeOut = 86400;
    $minTimeOut = 1800;
    $maxTimeOut = 1209600;
    $perThreadSubtraction = 600;
    $timeOut = minMax($baseTimeOut - $threadCount * $perThreadSubtraction, $minTimeOut, $maxTimeOut);
    $r->set("t_" . $prefix, 0);
    $r->setTimeout("t_" . $prefix, $timeOut);
    $r->set("title_" . $prefix, $title);
    $r->setTimeout("title_" . $prefix, $timeOut);
    $r->set("text_" . $prefix, $text);
    $r->setTimeout("text_" . $prefix, $timeOut);
    //$msgArray = json_encode(array($title, $prefix, time(), 0));
    //$r->set($prefix . "_0", $msgArray);
    //$r->setTimeout($prefix . "_0", $timeOut);
}

function updateThread($prefix)
{
    //Reset the timeout for a thread.
    global $r;
    $replyCount = count($r->keys($prefix . "*"));
    $threadCount = count($r->keys("t_*"));
    $timeOut = minMax(14400 + $replyCount * 600 - $threadCount * 600, 1800, 604800);
    $r->setTimeout("t_" . $prefix, $timeOut);
    $r->setTimeout("title_" . $prefix, $timeOut);
    $r->setTimeout("text_" . $prefix, $timeOut);
    //$r->setTimeout($prefix . "_0", $timeOut);
}

function getThreads()
{
    //Get all threads.
    global $r;
    $threadNames = $r->keys('title_*');
    $threadsToGet = array();
    $threadIds = array();
    foreach ($threadNames as $threadName) {
        $threadsToGet[] = $threadName;
        $threadIds[] = substr($threadName, 6);
    }
    $titles = $r->mGet($threadsToGet);
    $threads = array();
    $i = 0;
    foreach ($threadsToGet as $thread) {
        $threads[] = array($titles[$i], $threadIds[$i]);
        $i++;
    }
    //Return structure: array of arrays with title and id
    return $threads;
}

function getThread($id)
{
    //Get title and text of a thread
    global $r;
    $title = $r->get("title_" . $id);
    $text = $r->get("text_" . $id);
    return array($id, $title, $text);
}

function minMax($value, $min, $max)
{
    //keep value between min and max
    if ($value < $min) {
        $value = $min;
    } elseif ($value > $max) {
        $value = $max;
    }
    return $value;
}

?>