<?php
include __DIR__ . '/../db_connect.php';

/** модель работы с транзакциями **/

const TYPE_BONUS = 'bonus';
const TYPE_FEE = 'fee';
const TYPE_CASH_IN = 'cash_in';
const TYPE_CASH_OUT = 'cash_out';

$labels = [
    'bonus' => 'Начисление бонусного вознаграждения ввиде баллов (1 бал = 1₽)',
    'fee' => 'Списание денежных средств за неприемлемое повидение',
    'cash_in' => 'Пополение баланса',
    'cash_out' => 'Ежемесячное списание за подписку бота'
];

/**
 * Присвоение клиенту бонусного вознаграждения
 * @param $user_id
 * @param $bonus
 */
function set_bonus_client($user_id, $bonus){
    global $pdo;
    $query = "INSERT INTO transactions (payment, type, user_id) VALUES (?,?,?)";
    $pdo->prepare($query)->execute([$bonus, TYPE_BONUS, $user_id]);
}

/**
 * Присвоение штрафа клиенту (возможно денежное списание)
 * @param $user_id
 * @param $fine
 */
function set_fine_client($user_id, $fine = null){
    global $pdo;
    $query = "INSERT INTO transactions (payment, type, user_id) VALUES (?,?,?)";
    $pdo->prepare($query)->execute([$fine, TYPE_FEE, $user_id]);
}

/**
 * Пополнение счета клиента
 * @param $user_id
 * @param $cash_in
 */
function set_cash_in_client($user_id, $cash_in){
    global $pdo;
    $query = "INSERT INTO transactions (payment, type, user_id) VALUES (?,?,?)";
    $pdo->prepare($query)->execute([$cash_in, TYPE_CASH_IN, $user_id]);
}

/**
 * Списание со счета клиента денег
 * @param $user_id
 * @param $cash_out
 */
function set_cash_out_client($user_id, $cash_out){
    global $pdo;
    $query = "INSERT INTO transactions (payment, type, user_id) VALUES (?,?,?)";
    $pdo->prepare($query)->execute([$cash_out, TYPE_CASH_OUT, $user_id]);
}

/**
 * Получение баланса пользователя user_id
 * @param $user_id
 * @return mixed
 */
function get_balance($user_id){
    global $pdo;
    $query = $pdo->prepare('SELECT SUM(payment) FROM transactions WHERE user_id = :user_id');
    $query->execute([':user_id' => $user_id]);
    $arr = $query->fetch();
    return $arr["SUM(payment)"];
}

/**
 * Получение отчета о транзакциях за последнии месяца
 * @param $user_id
 * @param $monthPeriod
 * @return string
 */
function get_history_transactions($user_id, $monthPeriod){
    global $pdo;
    //Входящий баланс (баланс который был до $monthPeriod-x месячного периода)
    $query = $pdo->prepare('SELECT SUM(payment) FROM transactions WHERE user_id = :user_id AND `created_at` < CURDATE() - INTERVAL '. $monthPeriod .' MONTH');
    $query->execute([':user_id' => $user_id]);
    $incomingBalance = $query->fetch();
    //Операции за $monthPeriod месяца
    $query = $pdo->prepare('SELECT type, payment, created_at FROM transactions WHERE user_id = :user_id AND `created_at` > CURDATE() - INTERVAL '. $monthPeriod .' MONTH');
    $query->execute([':user_id' => $user_id]);
    $transactions3Months = $query->fetchAll();
    $report = parser_history_transactions($user_id, $incomingBalance, $transactions3Months);
    return $report;
}

/**
 * Парсер данных транзакций
 * @param $user_id
 * @param $incomingBalance
 * @param $transactions3Months
 * @return string
 */
function parser_history_transactions($user_id, $incomingBalance, $transactions3Months){
    global $labels;

    $report = "Входящий баланс до начала периода составлял: "
        . $incomingBalance["SUM(payment)"]
        . "₽\nИстория операций за последнии 3 месяца:\n";

    foreach ($transactions3Months as $transactions3Month) {
        $description = $labels[$transactions3Month["type"]];
        $cost = $transactions3Month["payment"];
        $data = $transactions3Month["created_at"];
        $report .= getCostFormat($cost) . "₽, " . $data . ", " . $description . "\n";
    }
    $report .= "Исходящий баланс составляет: " . get_balance($user_id) . "₽";
    return $report;
}

/**
 * Получить стоимость в формате (+/-)
 * @param $cost
 * @return mixed|string
 */
function getCostFormat($cost){
    return $cost[0] == "-" ? str_ireplace($cost[0], "−", $cost) : "+" . $cost;
}