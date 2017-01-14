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

function generateID()
{
    return strtoupper(str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT));
}
function returnID($id)
{
    //Hash and return id and secId.
    global $r;
    //Limit the user to 50 id requests in a 5min interval.
    $userHash = "id_".hash("md4", $_SERVER['REMOTE_ADDR']);
    $r->select(2);
    if ($r->exists($userHash)) {
        $idRequests = $r->get($userHash);
        $idRequests += 1;
        $r->set($userHash, $idRequests);
        $r->expire($userHash, 300);
        if ($idRequests > 50) {
            return "ERR: Too many requests for a new id.";
        }
    } else {
        $r->set($userHash, 1);
        $r->expire($userHash, 300);
    }
    $r->select(1);
    //Get salty
    $salt = $r->get("salt");
    //Generate id hash
    $secId = hash("sha256", $id . $salt);
    //Return array
    return array('id' => $id, 'hash' => $secId);
}

function hasPostedLately()
{
    global $r;
    $r->select(2);
    $userHash = hash("md4", $_SERVER['REMOTE_ADDR']);
    if ($r->exists($userHash)) {
        return true;
    }
    return false;
}

function newThread($prefix, $title, $text)
{
    //Make sure thread title contains something.
    if (isNothing($title)) {
        echo "ERR: Needs more than whitespace";
        return;
    }
    //Create a new thread from a previously generated id and text.
    global $r;
    $r->select(0);
    $r->set("t_" . $prefix, -1);
    $r->set("title_" . $prefix, $title);
    $r->set("text_" . $prefix, $text);
    updateThread($prefix);
    //$msgArray = json_encode(array($title, $prefix, time(), 0));
    //$r->set($prefix . "_0", $msgArray);
    //$r->setTimeout($prefix . "_0", $timeOut);
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
function isNothing($text)
{
    $clearText = preg_replace("/\W/","", $text);
    if ($clearText == "") {
        return true;
    };
    return false;
}

function saveUserHash()
{
    global $r;
    $r->select(2);
    $userHash = hash("md4", $_SERVER['REMOTE_ADDR']);
    $r->set($userHash, true);
    $r->expire($userHash, 600);
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

if (hasPostedLately()) {
    echo "ERR: TOOSOON";
} else {
    if ($_REQUEST["title"] != "") {
        $newId = generateID();
        newThread($newId, substr(htmlspecialchars($_REQUEST["title"], ENT_QUOTES), 0, 200), substr(htmlspecialchars($_REQUEST["text"], ENT_QUOTES), 0, 1000));
        echo json_encode(returnID($newId));
    }
}
saveUserHash();
?>
