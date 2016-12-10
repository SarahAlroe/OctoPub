<?php
//Set up redis
$r = new Redis();
$r->connect('127.0.0.1');
$r->select(0);

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
            $messages[] = array('text' => $message[0], "id" => $message[1], 'time' => $message[2], 'number' => $message[3]);
        }
    }
    return $messages;
}

echo json_encode(getHistory($_REQUEST["thread"]));
?>
