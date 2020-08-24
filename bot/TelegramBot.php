<?php

include 'TelegramApi.php';

class TelegramBot
{
    /**
     * Подключение к api телеграма
     * @var TelegramApi
     */
    public $api;

    /**
     * @var array Список команд
     */
    public $commands = [];

    /**
     * @var string Последняя введенная команда (текущая)
     */
    public $lastCommand;

    /**
     * TelegramBot constructor.
     */
    public function __construct()
    {
        $this->api = new TelegramApi();
    }

    /**
     * Запуск бота
     */
    public function run() {
        while (true) {
            $updates = $this->api->getUpdates();
            foreach ($updates as $update) {
                $message = $update["message"];
                $mtext = $message["text"];
                $cid = $message["chat"]["id"];
                if (is_chat_set($cid) == false) {
                    make_chats($cid);
                }

                $command = $this->parserCommand($mtext);
                $command($update);
            }
            sleep(2);
        }
    }

    /**
     * Метод добавления команды
     *
     * @param $name
     * @param $action
     * @return mixed
     */
    public function command($name, $action) {
        return $this->commands['/' . $name] = $action;
    }

    /**
     * Возвращает обрабочитк распознанной функции
     * @param $text
     * @return mixed
     */
    private function parserCommand($text)
    {
        if (isset($this->commands[$text])) {
            $this->lastCommand = $text;
            $func = $this->commands[$text];
        } else {
            $func = $this->commands['/ '];
        }
        return $func;
    }
}