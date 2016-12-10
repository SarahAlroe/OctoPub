<?php
//Set up redis
$r = new Redis();
$r->connect('127.0.0.1');
$r->select(0);

//set up thread life vars
$baseTimeout = 172800; //The default lifespan of a thread - Two days
$minTimeout = 3600; //Minimum lifespan of a thread - An hour
$maxTimeout = 1209600; //Maximum timeout of a thread - Two weeks
$modTime = 600; //Time added per post and subtracted per thread. - 10 min

function authenticate($id, $secId)
{
    global $r;
    $r->select(1);
    //Get salty
    $salt = $r->get("salt");
    $calcSecId = hash("sha256", $id . $salt);
    if ($secId == $calcSecId) {
        return true;
    } else {
        return false;
    }
}


function newMsg($prefix, $msg, $userId)
{
    //Make sure message contains something.
    if (isNothing($msg)) {
        echo "ERR: Needs more than whitespace";
        return;
    }

    global $r;

    //Anti spam thingy
    $userHash = "message_".hash("md4", $_SERVER['REMOTE_ADDR']);
    $r->select(2);
    if ($r->exists($userHash)) {
        $messageCount = $r->get($userHash);
        if ($messageCount > 5) {
            echo"ERR: Too many messages in short time, please wait.";
            if ($messageCount != 999){
                $r->set($userHash, 999);
                $r->expire($userHash, 30);
            }
            return;
        }else{
            $messageCount += 1;
            $r->set($userHash, $messageCount);
            $r->expire($userHash, 4);
        }
    } else {
        $r->set($userHash, 1);
        $r->expire($userHash, 4);
    }

    //Create new message on a thread using a message text and user id.
    $r->select(0);
    if ($msg != $r->get("latestMsg")) {
        $msgId = $r->incr("t_" . $prefix);
        $msgArray = json_encode(array($msg, $userId, time(), $msgId));
        $r->set($prefix . "_" . $msgId, $msgArray);
        $r->setTimeout($prefix . "_" . $msgId, 604800);
    }
    $r->set("latestMsg", $msg);
}

function isNothing($text)
{
    $clearText = preg_replace("/\W/","", $text);
    if ($clearText == "") {
        return true;
    };
    return false;
}

function updateThread($prefix)
{
    //Reset the timeout for a thread.
    global $r, $baseTimeout, $minTimeout, $maxTimeout, $modTime;
    $replyCount = count($r->keys($prefix . "*"));
    $threadCount = count($r->keys("t_*"));
    $timeout = minMax($baseTimeout + $replyCount * $modTime - $threadCount * $modTime, $minTimeout, $maxTimeout);
    $r->setTimeout("t_" . $prefix, $timeout);
    $r->setTimeout("title_" . $prefix, $timeout);
    $r->setTimeout("text_" . $prefix, $timeout);
    //$r->setTimeout($prefix . "_0", $timeout);
}

if (authenticate($_REQUEST["id"], $_REQUEST["hash"])) {
    newMsg(htmlspecialchars($_REQUEST["thread"], ENT_QUOTES), substr(htmlspecialchars($_REQUEST["text"], ENT_QUOTES), 0, 1000), htmlspecialchars($_REQUEST["id"], ENT_QUOTES));
    updateThread(htmlspecialchars($_REQUEST["thread"], ENT_QUOTES));
} else {
    echo "ERR: OLDID";
}
?>
