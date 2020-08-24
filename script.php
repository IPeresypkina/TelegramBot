<?php
include 'Direct.php';

/**
 * @return string
 */
function getInfoClients()
{
    $direct = new Direct();

    $AgencyClientsURL = 'https://api.direct.yandex.com/json/v5/agencyclients';
    // Адрес сервиса Reports для отправки JSON-запросов (регистрозависимый)
    $ReportsURL = 'https://api.direct.yandex.com/json/v5/reports';
    $url = 'https://api.direct.yandex.ru/live/v4/json/';

    // Расходы за $days дня
    $days = 3;
    // Среднесуточный порог
    $dailyAverageThreshold = 2;
    // Тип диапазон дат
    $dateRangeType = "LAST_3_DAYS";
    // Флаг уведомления
    $flag = false;

    $agentClients = $direct->getAgencyClients($AgencyClientsURL);

    //--- Обработка результата выполнения запроса ---------------------------//
    if ($agentClients === false) {
        echo "Ошибка выполнения запроса!";
    }
    else {
        // Преобразование ответа из формата JSON
        $result = json_decode($agentClients);

        if (isset($result->error)) {
            $apiErr = $result->error;
            echo "Ошибка API {$apiErr->error_code}: {$apiErr->error_string} - {$apiErr->error_detail} (RequestId: {$apiErr->request_id})";
        }
        else {
            # Создание результирующих данных
            $report = "У клиентов «" . $result->result->Clients[0]->Grants[0]->Agency . "» заканчиваются средства:\n";

            // Вывод списка клиентов
            foreach ($result->result->Clients as $client) {

                $reportCost = $direct->getReport($client->Login, $ReportsURL, $dateRangeType);
                $resultAll = $direct->getAllClients($client->Login, $url);

                $bits[] = $direct->parserReport($reportCost);
                $cost = $direct->getCost($bits, $days, $dailyAverageThreshold);

                $amount = $resultAll->data->Accounts[0]->Amount;

                // если Остаток < Порог, уведомляем
                if ($amount < $cost)
                {
                    $flag = true;
                    if (round($amount) == 0)
                    {
                        $report .= "• " . $client->ClientInfo . " - " . $amount . "₽" . " (денег не хватает)\n";
                    }
                    else
                    {
                        $day = round($amount / $cost);
                        $report .= "• " . $client->ClientInfo . " - " . $amount . "₽" . " (хватит на ~" . $day . " дня)\n";
                    }
                }
            }
        }
    }
    return $flag ? $report : "";
}
?>
