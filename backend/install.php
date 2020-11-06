<?php 

require_once(realpath(dirname(__FILE__).'/livechat-config.php'));

global $livechat_prefix;
global $livechat_db;

$db_init_cmd = "CREATE TABLE IF NOT EXISTS  `${livechat_prefix}admin_chat` (    chat_id bigint,    token varchar(255),    current_session_id varchar(255),    current_chat_start_date_utc datetime,    last_heart_beat_utc datetime,    last_message_date_utc datetime,    dirty  TINYINT(1) );";

$db_init_cmd.="CREATE TABLE IF NOT EXISTS  `${livechat_prefix}admin_chat_message` (    chat_id bigint,    message_date_utc datetime,     message_text text,    is_read TINYINT(1) default 0);";

$db_init_cmd.="CREATE TABLE IF NOT EXISTS  `${livechat_prefix}setting` (   
     setting_key varchar(255) PRIMARY KEY,    setting_value varchar(255) );";

$db_init_cmd.="INSERT INTO `${livechat_prefix}setting` (setting_key, setting_value) VALUES ('last_telegram_bot_offset','0') ON DUPLICATE KEY UPDATE setting_value=setting_value";

$result = $livechat_db->query($db_init_cmd);
return $result;

echo $result;


?>