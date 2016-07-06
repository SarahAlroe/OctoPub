<?php

// Settings
$targetDir = './img';
$cleanupTargetDir = True; // Remove old files
$maxFileAge = 4 * 24 * 3600; // Temp file age in seconds

//Set up Redis
$r = new Redis();
$r->connect('127.0.0.1');
$r->select(1);

// Make sure file is not cached (as it happens for example on iOS devices)
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

//Get all keys and their values in the image db
$keys = $r->keys("*");
$values = $r->mget($keys);

//Return if no files or error
if (empty($_FILES) || $_FILES["file"]["error"]) {
  die('{"OK": 0}');
}

// Remove old temp files
if ($cleanupTargetDir) {
    if (!is_dir($targetDir) || !$dir = opendir($targetDir)) {
        die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
    }

    while (($file = readdir($dir)) !== false) {
        $tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;
        // Remove temp file if it is older than the max age and is not the current file
        if (!in_array($file, $keys)) {
            @unlink($tmpfilePath);
        }
    }
    closedir($dir);
}

//Get filename
$fileName = $_REQUEST["newFileName"];

//Calculate hash of new file
$fileHash = md5_file($_FILES["file"]["tmp_name"]);

//Check if file already exists
$isNewFile = true;
for ($i = 0, $c = count($keys); $i < $c; $i++) {
    if ($fileHash == $values[$i]) {
        //If it does, change fileName to old filename and delete new file.
        $fileName = $keys[$i];
        $isNewFile = false;
    }
}

//Manage upload limit
$r->select(2);
$userHash = "img_" . hash("md4", $_SERVER['REMOTE_ADDR']);
if ($r->exists($userHash)) {
    $uploadCount = $r->get($userHash);
    $uploadCount += 1;
    $r->set($userHash, $uploadCount);
    $r->expire($userHash, 300);
    if ($uploadCount > 20) {
        die('{"jsonrpc" : "2.0", "error" : {"code": 400, "message": "Image uploads too frequent, please slow down."}, "id" : "id"}');
    }
} else {
    $r->set($userHash, 1);
    $r->expire($userHash, 300);
}

if (isNewFile){
//Move file to its final dir
move_uploaded_file($_FILES["file"]["tmp_name"], "img/$fileName");
}

//Update old or set new key for file.
$r->select(1);
$r->set($fileName, $fileHash);
$r->expire($fileName, $maxFileAge);

//Return OK filename
die('{"jsonrpc" : "2.0", "result" : {"cleanFileName": "' . $fileName . '"}, "id" : "id"}');
?>
