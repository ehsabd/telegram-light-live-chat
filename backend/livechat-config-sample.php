<?php 


global $livechat_db;
$livechat_db=new mysqli('localhost', 'USER', 'PASSWORD', 'DATABASE_NAME');


if ($livechat_db->connect_errno) {
    printf("Connect failed: %s\n", $livechat_db->connect_error);
   // exit();
}

define('BOT_TOKEN', '<YOUR TELEGRAM BOT TOKEN>');

?>