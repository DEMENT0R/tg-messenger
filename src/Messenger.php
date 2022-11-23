<?php

namespace dement0r\TgMessenger;

use Exception;

class Messenger
{
    private string $apiUrl;

    public function __construct(string $token)
    {
        $this->apiUrl = "https://api.telegram.org/bot$token/";
    }

    /**
     * Get message
     *
     * @return mixed
     * @throws Exception
     */
    public function getMessage()
    {
        $update = $this->getContentUpdate();

        if (!isset($update['message']) || !$update['message']) {
            throw new Exception('Message receiving error!');
        }

        return $update['message'];
    }

    /**
     * Get content update
     *
     * @return mixed|void
     * @throws Exception
     */
    private function getContentUpdate()
    {
        $content = file_get_contents("php://input");
        $update  = json_decode($content, true);

        if (!$update) {
            throw new Exception('Error! No update!');
        }

        return $update;
    }

    /**
     * Send message
     *
     * @param int        $chatId
     * @param string     $messageText
     * @param int|null   $messageId
     * @param array|null $replayMarkup
     * @param string     $parseMode
     *
     * @throws Exception
     */
    public function sendMessage(
        int $chatId,
        string $messageText,
        int $messageId = null,
        array $replayMarkup = null,
        string $parseMode = 'Markdown'
    ) {
        $texts = str_split($messageText, 4094);
        foreach ($texts as $text) {
            $request = [
                'chat_id' => $chatId,
                'text'    => $text,
            ];
            if ($messageId) {
                $request['reply_to_message_id'] = $messageId;
            }
            if ($replayMarkup) {
                $request['reply_markup'] = $replayMarkup;
            }
            if ($parseMode) {
                $request['parse_mode'] = $parseMode;
            }
            $this->apiRequest('sendMessage', $request);
        }
    }

    /**
     * Webhook set
     *
     * @param null $url
     *
     * @return bool|string
     */
    public function setWebhook($url = null)
    {
        $ch    = curl_init($this->apiUrl . 'setWebhook');
        $query = [
            // todo need to debug
            'url' => $url ?? "https://$_SERVER[HTTP_HOST]$_SERVER[PHP_SELF]",
        ];
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /**
     * Remove webhook
     *
     * @return bool|string
     */
    public function removeWebhook()
    {
        $ch    = curl_init($this->apiUrl . 'setWebhook');
        $query = ['remove'];
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /**
     * Webhook get
     * https://api.telegram.org/bot_BOT_TOKEN_/getWebhookInfo
     *
     * @return bool|string
     */
    public function getWebhook()
    {
        $ch = curl_init($this->apiUrl . 'getWebhookInfo');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /**
     * @param $method
     * @param $parameters
     *
     * @return bool
     */
    public function apiRequestWebhook($method, $parameters): bool
    {
        if (!is_string($method)) {
            error_log("Method name must be a string\n");

            return false;
        }

        if (!$parameters) {
            $parameters = [];
        } else {
            if (!is_array($parameters)) {
                error_log("Parameters must be an array\n");

                return false;
            }
        }

        $parameters["method"] = $method;

        $payload = json_encode($parameters);
        header('Content-Type: application/json');
        header('Content-Length: ' . strlen($payload));

        //echo $payload;

        return true;
    }

    /**
     * @throws Exception
     */
    public function exec_curl_request($handle)
    {
        $response = curl_exec($handle);

        if ($response === false) {
            $errno = curl_errno($handle);
            $error = curl_error($handle);
            error_log("Curl returned error $errno: $error\n");
            curl_close($handle);

            return false;
        }

        $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
        curl_close($handle);

        if ($http_code >= 500) {
            // do not wat to DDOS server if something goes wrong
            sleep(10);

            return false;
        } else {
            $response = json_decode($response, true);
            if ($http_code != 200) {
                error_log("Request has failed with error {$response['error_code']}: {$response['description']}\n");
                if ($http_code == 401) {
                    throw new Exception('Invalid access token provided');
                }

                return false;
            } else {
                if (isset($response['description'])) {
                    error_log("Request was successful: {$response['description']}\n");
                }
                $response = $response['result'];
            }
        }

        return $response;
    }

    /**
     * @throws Exception
     */
    public function apiRequest($method, $parameters)
    {
        if (!is_string($method)) {
            error_log("Method name must be a string\n");

            return false;
        }

        if (!$parameters) {
            $parameters = [];
        } else {
            if (!is_array($parameters)) {
                error_log("Parameters must be an array\n");

                return false;
            }
        }

        foreach ($parameters as &$val) {
            // encoding to JSON array parameters, for example reply_markup
            if (!is_numeric($val) && !is_string($val)) {
                $val = json_encode($val);
            }
        }
        $url = $this->apiUrl . $method . '?' . http_build_query($parameters);

        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($handle, CURLOPT_TIMEOUT, 60);

        return $this->exec_curl_request($handle);
    }

    /**
     * @throws Exception
     */
    public function apiRequestJson($method, $parameters): bool
    {
        if (!is_string($method)) {
            error_log("Method name must be a string\n");

            return false;
        }

        if (!$parameters) {
            $parameters = [];
        } else {
            if (!is_array($parameters)) {
                error_log("Parameters must be an array\n");

                return false;
            }
        }

        $parameters["method"] = $method;

        $handle = curl_init($this->apiUrl);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($handle, CURLOPT_TIMEOUT, 60);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters));
        curl_setopt($handle, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

        return $this->exec_curl_request($handle);
    }
}
