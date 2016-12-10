<?php
//Set up redis
$r = new Redis();
$r->connect('127.0.0.1');
$r->select(0);

function getFrom($prefix, $id)
{
    //Gets all messages from a thread since id.
    global $r;
    $messages = array();
    $latestMsg = $r->get("t_" . $prefix);
    $messagesToGet = array();
    for ($i = $id + 1; $i <= $latestMsg; $i++) {
        $messagesToGet[$i - $id] = $prefix . "_" . $i;
    }
    $jmessages = $r->mGet($messagesToGet);
    if ($jmessages[0] != "") {
        foreach ($jmessages as $jmessage) {
            $message = json_decode($jmessage);
            if ($message != null) {
                $messages[] = array('text' => $message[0], "id" => $message[1], 'time' => $message[2], 'number' => $message[3]);
            }
        }
    }
    return $messages;
}

echo json_encode(getFrom($_REQUEST["thread"], $_REQUEST["fromNumber"]));
?>
