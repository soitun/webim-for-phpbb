<?php
include_once('common.php');
$id = p("id");
if(empty($id)){
header("HTTP/1.0 400 Bad Request");
echo 'Empty post $id';
}else{

        $db->sql_query("UPDATE ".im_tname('histories')." SET fromdel=1 WHERE `from`='$user->id' AND `to`='$id'");
        $db->sql_query("UPDATE ".im_tname('histories')." SET todel=1 WHERE `to`='$user->id' AND `from`='$id'");
        $db->sql_query("DELETE FROM ".im_tname('histories')." WHERE fromdel=1 AND todel=1");
        echo "ok";
}