<?php 

require_once(realpath(dirname(__FILE__).'/livechat-config.php'));

global $livechat_prefix;
global $livechat_db;

$db_init_cmds = array("CREATE TABLE IF NOT EXISTS  `${livechat_prefix}admin_chat` (    chat_id bigint,    token varchar(255),    current_session_id varchar(255),    current_chat_start_date_utc datetime,    last_heart_beat_utc datetime,    last_message_date_utc datetime,    dirty  TINYINT(1) );",
"CREATE TABLE IF NOT EXISTS  `${livechat_prefix}admin_chat_message` (    chat_id bigint,    message_date_utc datetime,     message_text text,    is_read TINYINT(1) default 0);",
"CREATE TABLE IF NOT EXISTS  `${livechat_prefix}setting` (setting_key varchar(150) PRIMARY KEY,    setting_value varchar(255) );",
"INSERT INTO `${livechat_prefix}setting` (setting_key, setting_value) VALUES ('last_telegram_bot_offset','0') ON DUPLICATE KEY UPDATE setting_value=setting_value;"
);

foreach ($db_init_cmds as $cmd){
     error_log($cmd);
     $result = $livechat_db->query($cmd);

     echo $result;
}


?>