<?php 


global $livechat_db;
$livechat_db=new mysqli('<DATABASE_HOST>', '<DATABASE_USER>', '<DATABASE_PASSWORD>', '<DATABASE_NAME>');

global $livechat_prefix;
$livechat_prefix = 'telegram_live_chat_';

if ($livechat_db->connect_errno) {
    printf("Connect failed: %s\n", $livechat_db->connect_error);
   // exit();
}

define('BOT_TOKEN', '<BOT_TOKEN>');
define('BOT_ADMIN_SECRET', '<BOT_ADMIN_SECRET>');
define('ADMIN_CHAT_TOKEN', 'default')
?>