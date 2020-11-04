
CREATE TABLE IF NOT EXISTS  `telegram_live_chat_admin_chat` (
    chat_id bigint,
    token varchar(255),
    current_session_id varchar(255),
    current_chat_start_date_utc datetime,
    last_heart_beat_utc datetime,
    last_message_date_utc datetime,
    dirty  TINYINT(1) 
);

CREATE TABLE IF NOT EXISTS  `telegram_live_chat_admin_chat_message` (
    chat_id bigint,
    message_date_utc datetime, 
    message_text text,
    is_read TINYINT(1) default 0
);

CREATE TABLE IF NOT EXISTS  `telegram_live_chat_setting` (
    setting_key varchar(255),
    setting_value varchar(255) 
);

INSERT INTO `telegram_live_chat_setting` (setting_key, setting_value) VALUES ('last_telegram_bot_offset','0')