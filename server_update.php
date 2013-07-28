<?php

    error_reporting(E_ALL);
    ini_set("display_errors", 1);
    require_once("include/mysql.php");
    
    $text = $sql->escape($_POST["text"]);
    $instanceId = $sql->escape($_POST["instanceId"]);
    
    $sql->query("DELETE FROM `updatelog` WHERE `instance` = '".$instanceId."'");
    echo $sql->query("INSERT INTO `updatelog` VALUES ( NULL , '".$instanceId."' , '".$text."');", Mysql::id);
    
?>