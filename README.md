# Telegram Live Chat

## Installation and Usage

1. Create a Telegram Bot using @botfather keep the token to be used in step 5.
2. You need a database for this app to work. Either use another app database or create a new database for this app
3. Copy `backend` folder to a folder in your PHP project, we recommend `telegram-live-chat` folder in root.
4. Setup appropriate routing / URL rewriting (skip this if there is no routing or URL rewriting )
5. Make a copy of `livechat-config-sample.php` and rename it to `livechat-config.php`, set `<DATABASE_HOST>`, `<DATABASE_USER>`, `<DATABASE_PASSWORD>`, `<DATABASE_NAME>`, `<BOT_TOKEN>` (bot token in step 1) and `<BOT_ADMIN_SECRET>` appropriately in the config file.
6. Open `install.php` in `backend` folder to create tables in the database.
7. Copy `div.telegram_live_chat_main_holder` element in `frontend/chat.html` to your website layout. For appropriate position, make sure that is is inside `body` or another element which wraps most of the viewport. Also include `bootstrap`,`fontawesome`,`jquery` dependencies, `frontend/style.css` link tag and `frontend/chat.js` script tag in your website layout.
8. Set appropriate `message.php` url in the `chat.js` file to this variable: `telegram_live_chat_message_url`. The default is `'/telegram-live-chat/backend/message.php'`.
9. Connect to the bot you created at step 1 and send the message you set as `<BOT_ADMIN SECRET>` in the config file to the bot.
10. Open your website and start a live chat by entering a name, email , and message. 
11. You should receive the message in Telegram bot and enjoy the telegram live chat experience.
