<?php

include('vendor/autoload.php');
require_once("db_connect.php");
require_once("model/chats.php");
require_once("model/users.php");
require_once("model/transactions.php");
include 'TelegramBot.php';
include __DIR__ . '/../script.php';

$bot = new TelegramBot();

/**
 * Цена подписки на бота в месяц
 */
const subscriptionCost = 300;

/**
 * Месячный период (для вывода отчет о транзакциях за последнии monthPeriod месяцев)
 */
$monthPeriod = 3;

/**
 * Запуск бота
 */
$bot->command('start', function ($update) use ($bot) {
        $answer = 'Добро пожаловать!';
        $bot->api->sendMessage($update["message"]["chat"]["id"], $answer);
});

/**
 * Включение/Выключение автопроверок
 */
$bot->command('check', function ($update) use ($bot) {
    if ($update["message"]["chat"]["id"] < 0) {
        $data = get_udata($update["message"]["chat"]["id"]);
        if ($data["is_enabled"] == "1") {
            update_enabled($update["message"]["chat"]["id"], 0);
            $answer = 'Автопроверка отключена';
            $bot->api->sendMessage($update["message"]["chat"]["id"], $answer);
        }
        else {
            update_enabled($update["message"]["chat"]["id"], 1);
            $answer = 'Автопроверка включена';
            $bot->api->sendMessage($update["message"]["chat"]["id"], $answer);
        }
    }
    else {
        $answer = 'Команда не доступна в личном чате';
        $bot->api->sendMessage($update["message"]["chat"]["id"], $answer);
    }
});

/**
 * Вывод отчета о клиентах
 */
$bot->command('report', function ($update) use ($bot) {
    if ($update["message"]["chat"]["id"] < 0) {
        $report = getInfoClients();
        if ($report != "") {
            $bot->api->sendMessage($update["message"]["chat"]["id"], $report);
        }
    }
    else {
        $answer = 'Команда не доступна в личном чате';
        $bot->api->sendMessage($update["message"]["chat"]["id"], $answer);
    }
});

/**
 * Установка токена
 */
$bot->command('set', function ($update) use ($bot) {
    if ($update["message"]["chat"]["id"] < 0) {
        $data = get_udata($update["message"]["chat"]["id"]);
        if (!isset($data["state"])) { // если в нем нет режима - значит человек еще не взаимодействовал с этой командой
            $mode = "token"; // поэтому задаем ему действие по дефолту
        } else {
            $mode = $data["state"];
        }
        if ($mode == "token") {
            $bot->api->sendMessage($update["message"]["chat"]["id"], "Добрый день, укажите, пожалуйста, ваш токен");
            $data["state"] = "aftertoken";
            update($update["message"]["chat"]["id"], $data);
        }
        if ($mode === "done") {
            // если человек уже отправил токен - выводим ему собранную у него-же информацию
            $inline_button1 = array("text" => "Да", "callback_data" => 'data_yes');
            $inline_button2 = array("text" => "Нет", "callback_data" => 'data_no');
            $inline_keyboard = [[$inline_button1, $inline_button2]];
            $keyboard = array("inline_keyboard" => $inline_keyboard);
            $replyMarkup = json_encode($keyboard);
            $bot->api->sendMessageInline($update["message"]["chat"]["id"], "Вы уже отправляли токен и указали следующие данные:\nТокен - " . $data["token_user"] . "\nЖелаете их изменить?", 'HTML', $replyMarkup);
        }
    }
    else {
        $answer = 'Команда не доступна в личном чате';
        $bot->api->sendMessage($update["message"]["chat"]["id"], $answer);
    }
});

/**
 * Информация о балансе
 */
$bot->command('balance', function ($update) use ($bot) {
    if ($update["message"]["chat"]["id"] > 0) {
        $client = get_owner($update["message"]["from"]["id"]);
        $balance = get_balance($client["id"]);
        $limit = intdiv($balance, subscriptionCost);
        $inline_button = array("text" => "История транзакций", "callback_data" => 'history_balance');
        $inline_keyboard = [[$inline_button]];
        $keyboard = array("inline_keyboard" => $inline_keyboard);
        $replyMarkup = json_encode($keyboard);
        $answer = "Ваш баланс на сегодняшний день составляет: " . $balance . "₽.\nЭтого хватит на " . $limit . " (чато*месяц)";
        $bot->api->sendMessageInline($update["message"]["chat"]["id"], $answer, 'HTML', $replyMarkup);
    }
    else {
        $answer = 'Команда не доступна в общем чате';
        $bot->api->sendMessage($update["message"]["chat"]["id"], $answer);
    }
});

/**
 * Команда пополнения баланса
 */
$bot->command('pay', function ($update) use ($bot) {
    if ($update["message"]["chat"]["id"] > 0) {
        $answer = "Введите сумму пополнения баланса.\nЕжемесячная подписка - " . subscriptionCost . "₽";
        $bot->api->sendMessage($update["message"]["chat"]["id"], $answer);
        $data["state"] = "pay";
        update($update["message"]["from"]["id"], $data);
    }
    else {
        $answer = 'Команда не доступна в общем чате';
        $bot->api->sendMessage($update["message"]["chat"]["id"], $answer);
    }
});

/**
 * Обычный текст
 */
$bot->command(' ', function ($update) use ($monthPeriod, $bot) {
    if ($update["message"] != null) {
        $data = get_udata($update["message"]["chat"]["id"]);

        if (!isset($data["state"])) {
            $mode = "token";
        } else {
            $mode = $data["state"];
        }

        if ($update["message"]["text"] == "/set") {
            if ($mode == "token") {
                $bot->api->sendMessage($update["message"]["chat"]["id"], "Добрый день, укажите, пожалуйста, ваш токен");
                $data["state"] = "aftertoken";
                update($update["message"]["chat"]["id"], $data);
            }
        }
        elseif ($mode === "aftertoken") {
            $data["token_user"] = $update["message"]["text"];
            $data["state"] = "done";
            $bot->api->sendMessage($update["message"]["chat"]["id"], "Cпасибо, ваш токен принят.");
            update($update["message"]["chat"]["id"], $data);
        }
        elseif (isset($update["message"]["new_chat_member"])) {
            if (is_user_set($update["message"]["chat"]["id"]) == false) {
                make_owners($update["message"]["chat"]["username"], $update["message"]["chat"]["id"]);
                $client = get_owner($update["message"]["chat"]["id"]);
                // Начисляем 100 бонусов за первый чат
                set_bonus_client($client["id"], 100);
            }
            set_activated($update["message"]["chat"]["id"], $update["message"]["chat"]["id"]);
        }
        elseif ($mode === "pay") {
            $client = get_owner($update["message"]["chat"]["id"]);
            set_cash_in_client($client["id"], $update["message"]["text"]);
            $data["state"] = "done";
            $bot->api->sendMessage($update["message"]["chat"]["id"], "Cпасибо, ваш платеж прошел успешно.");
            update($update["message"]["chat"]["id"], $data);
        }
        else {
            $bot->api->sendMessage($update["message"]["chat"]["id"], "Не знаю я таких слов (╯°□°)╯︵ ┻━┻");
        }
    }
    elseif ($update["callback_query"] != null) {
        $data = get_udata($update["callback_query"]["message"]["chat"]["id"]);
        if ($update["callback_query"]["data"] == "data_yes") {
            $bot->api->sendMessage($update["callback_query"]["message"]["chat"]["id"], "Укажите, пожалуйста, ваш токен");
            $data["state"] = "aftertoken";
            update($update["callback_query"]["message"]["chat"]["id"], $data);
        }
        else if ($update["callback_query"]["data"] === "data_no"){
            $bot->api->sendMessage($update["callback_query"]["message"]["chat"]["id"], "Изменений никаких не произошло");
        }
        if ($update["callback_query"]["data"] == "history_balance") {
            $client = get_owner($update["callback_query"]["message"]["chat"]["id"]);
            //вывести сформированный список истории транзакций
            $answer = get_history_transactions($client["id"], $monthPeriod);
            $bot->api->sendMessage($update["callback_query"]["message"]["chat"]["id"], $answer);
        }
    }
});

$bot->run();