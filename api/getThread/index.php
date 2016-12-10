<?php
//Set up redis
$r = new Redis();
$r->connect('127.0.0.1');
$r->select(0);

function getThread($id)
{
    //Get title and text of a thread
    global $r;
    $title = $r->get("title_" . $id);
    $text = $r->get("text_" . $id);
    return array('id' => $id, 'title' => $title, 'text' =>$text);
}

echo json_encode(getThread($_REQUEST["thread"]));
?>
