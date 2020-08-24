<?php
include __DIR__ . '/../db_connect.php';

/** модель работы с чатами **/

/**
 * Создание Чата
 * @param $chat_id
 *
 */
function make_chats($chat_id){
    global $pdo;
    $query = "INSERT INTO chats (chat_id) VALUES (?)";
    $pdo->prepare($query)->execute([$chat_id]);
}

/**
 * Возвращает true если есть такой чат в бд иначе false
 * @param $id
 * @return bool
 */
function is_chat_set($id){
    global $pdo;
    $query = $pdo->prepare("SELECT * FROM chats WHERE chat_id = :chat_id LIMIT 1");
    $query->execute([':chat_id' => $id]);
    if($query->fetch() !== false) return true;
    return false;
}

/**
 * Возвращает true если у клиента есть один чат и он первый в бд иначе false
 * @param $user_id
 * @return bool
 */
function is_first_chat($user_id){
    global $pdo;
    $query = $pdo->prepare("SELECT * FROM chats WHERE user_id = :user_id LIMIT 1");
    $query->execute([':$user_id' => $user_id]);
    if($query->fetch() !== false) return true;
    return false;
}

/**
 * Получение информации о чате
 * @param $id
 * @return array|mixed
 */
function get_udata($id){
    global $pdo;
    $query = $pdo->prepare('SELECT * FROM chats WHERE chat_id = :chat_id');
    $query->execute([':chat_id' => $id]);
    $arr = $query->fetchAll();
    return $arr[0];
}

/**
 * Обновление данных клиента
 * @param $id
 * @param array $data
 */
function update($id,$data = array()){
    global $pdo;
    if(!is_chat_set($id)){
        make_chats($id); // если каким-то чудом этот пользователь не зарегистрирован в базе
    }
    if (isset($data["token_user"])) {
        $data_token = json_encode($data["token_user"],JSON_UNESCAPED_UNICODE);
        $query = "UPDATE chats SET token_user=? WHERE chat_id=?";
        $pdo->prepare($query)->execute([$data_token, $id]);
    }

    $query = "UPDATE chats SET state=? WHERE chat_id=?";
    $pdo->prepare($query)->execute([$data["state"], $id]);
}

/**
 * Обновление флага автопроверок
 * @param $id
 * @param $is_enabled
 */
function update_enabled($id, $is_enabled){
    global $pdo;
    $data = json_encode($is_enabled,JSON_UNESCAPED_UNICODE);
    $query = "UPDATE chats SET is_enabled=? WHERE chat_id=?";
    $pdo->prepare($query)->execute([$data, $id]);
}

/**
 * Сохраняем id пользователя, который бота в чат добавил
 * @param $user_id
 * @param $chat_id
 */
function set_activated($user_id, $chat_id){
    global $pdo;
    $owner = get_owner($user_id);
    $query = "UPDATE chats SET user_id=? WHERE chat_id=?";
    $pdo->prepare($query)->execute([$owner["id"], $chat_id]);
}
