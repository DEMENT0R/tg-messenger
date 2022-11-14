# TG Messenger

A simple Telegram message sending composer package

### Installation

With composer:

`composer require dement0r/tg-messenger:dev-master`

### Requirements
 * PHP 7.4 or higher
 * ext-curl
 * ext-json

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

`$bot->sendMessage('chat_id', 'text', 'message_id', $markupArray)`

Markup example:
```
$markupArray = [
    'keyboard'          => [['Info', 'Test', 'Help']],
    'one_time_keyboard' => false,
    'resize_keyboard'   => true,
],
```