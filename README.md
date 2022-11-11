# TG Messenger

A simple Telegram message sending composer package

### Installation

`composer require dement0r/tg-messenger:dev-master`

### Using

Create object:

`$bot = new Messenger('BOT_TOKEN')`

Set webhook:

`$bot->setWebhook('url' ?? null)`

Get webhook info:

`$bot->getWebhook()`

Removing webhook:

`$bot->removeWebhook()`

Getting message from webhook url:

`$bot->getMessage()`

Sending message:

`$bot->sendMessage('chat_id', 'text', 'message_id')`