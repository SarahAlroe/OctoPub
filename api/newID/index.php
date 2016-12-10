<?php
//Set up redis
$r = new Redis();
$r->connect('127.0.0.1');
$r->select(0);

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

echo json_encode(returnID(generateID()));
?>
