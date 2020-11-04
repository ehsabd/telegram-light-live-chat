# Telegram Live Chat

## Installation

1. Create a Telegram Bot using @botfather keep the token to be used in step 5.
2. You need a database for this app to work. Either use another app database or create a database for this app
3. Copy `backend` folder to a folder in your PHP project.
4. Setup appropriate routing / URL rewriting (skip this if there is no routing or URL rewriting )
5. Make a copy of `livechat-config-sample.php` and rename it to `livechat-config.php`, set `<DATABASE_HOST>`, `<DATABASE_USER>`, `<DATABASE_PASSWORD>`, `<DATABASE_NAME>`, `BOT_TOKEN` and `BOT_ADMIN_SECRET` appropriately in the config file.

## Usage