<?php

use GuzzleHttp\Client;

class TelegramApi
{
    /**
     * Url telegram
     */
    private $url = 'https://api.telegram.org/bot';

    /**
     * @var string Токен бота
     */
    private $token = "ваш токен";

    /**
     * @var integer Уникальный идентификатор обновления
     */
    protected $updateId;

    /**
     * TelegramApi constructor.
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function __construct()
    {
        $client = new Client();
        $response = $client->request('get', $this->url . $this->token . '/getMe');

        if ($response->getStatusCode() !== 200) {
            throw new \InvalidArgumentException('Не удалось подключиться к боту.');
        }
    }

    /**
     * Отправка запросов к API телеграм.
     * @param $uri
     * @param array $params
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendRequest($uri, array $params = [])
    {
        $client = new Client();
        return $client->post($this->url . $this->token . '/' . $uri, $params);
    }

    /**
     * Возвращается массив объектов обновления чата
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getUpdates()
    {
        $response = $this->sendRequest('getUpdates',
            ['json' => [
		'offset' => $this->updateId + 1,
	    ]]);
        $json = json_decode($response->getBody()->getContents(), true);

        if (!empty($json["result"])) {
            $this->updateId = $json["result"][count($json["result"]) - 1]["update_id"];
        }

        return $json["result"];
    }

    /**
     * Метод для отправки текстовых сообщений.
     * @param $chat_id
     * @param $text
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendMessage($chat_id, $text)
    {
        $response = $this->sendRequest('sendMessage', ['json' => [
            'text' => $text,
            'chat_id' => $chat_id
	    ]]);

        return $response;
    }

    /**
     *
     * @param $chat_id
     * @param $text
     * @param $parse_mode
     * @param $reply_markup
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendMessageInline($chat_id, $text, $parse_mode, $reply_markup)
    {
        $response = $this->sendRequest('sendMessage', ['json' => [
            'text' => $text,
            'chat_id' => $chat_id,
            'parse_mode' => $parse_mode,
            'reply_markup' => $reply_markup
        ]]);

        return $response;
    }
}