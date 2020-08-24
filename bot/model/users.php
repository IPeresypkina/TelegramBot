<?php
include __DIR__ . '/../db_connect.php';

/** модель работы с пользователями **/

/**
 * Создание Пользователя чата
 * @param $name_telegram
 * @param $id_telegram
 */
function make_owners($name_telegram, $id_telegram){
    global $pdo;
    $query = "INSERT INTO users (name_telegram, id_telegram) VALUES (?,?)";
    $pdo->prepare($query)->execute([$name_telegram, $id_telegram]);
}

/**
 * Возвращает true если есть такой пользователь в бд иначе false
 * @param $id
 * @return bool
 */
function is_user_set($id){
    global $pdo;
    $query = $pdo->prepare("SELECT * FROM users WHERE id_telegram = :id_telegram LIMIT 1");
    $query->execute([':id_telegram' => $id]);
    if($query->fetch() !== false) return true;
    return false;
}

/**
 * Получение информации о владельце чата
 * @param $id
 * @return array|mixed
 */
function get_owner($id){
    global $pdo;
    $query = $pdo->prepare('SELECT * FROM users WHERE id_telegram = :id');
    $query->execute([':id' => $id]);
    $arr = $query->fetchAll();
    return $arr[0];
}

