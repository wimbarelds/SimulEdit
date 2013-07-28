<?php
    
    // There's an error with not sending stuff if the same user submits twice
    
    error_reporting(E_ALL);
    ini_set("display_errors", 1);
    require_once("include/mysql.php");
    
    function wait_for_next($refId, $instanceId, $timeout)
    {
        if(time() >= $timeout) {
            echo "null";
            return;
        }
        
        global $sql;
        usleep(500);
        $latest = $sql->query("SELECT `id` FROM `updatelog` WHERE `instance` != '".$instanceId."' ORDER BY `id` DESC LIMIT 0, 1", Mysql::single_assoc);
        if($latest["id"] > $refId){
            echo json_encode($sql->query("SELECT `id` AS `refId` , `text` FROM `updatelog` WHERE `id` = '".$latest["id"]."' LIMIT 0, 1", Mysql::single_assoc));
            return;
        }
        else {
            wait_for_next($refId, $instanceId, $timeout);
        }
    }
    
    $refId = $sql->escape($_POST["refId"]);
    $instanceId = $sql->escape($_POST["instanceId"]);
    
    wait_for_next($refId, $instanceId, time() + 20);
    
?>