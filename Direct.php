<?php
use GuzzleHttp\Client;

class Direct
{
    // OAuth-токен пользователя, от имени которого будут выполняться запросы
    protected $token = 'токен';

    public function getAgencyClients($AgencyClientsURL)
    {
        //--- Подготовка и выполнение запроса -----------------------------------//
        // Установка HTTP-заголовков запроса
        $headers = array(
            "Authorization: Bearer $this->token",              // OAuth-токен. Использование слова Bearer обязательно
            "Accept-Language: ru",                             // Язык ответных сообщений
            "Content-Type: application/json; charset=utf-8"    // Тип данных и кодировка запроса
        );

        // Параметры запроса к серверу API Директа
        $params = array(
            'method' => 'get',
            'params' => array(
                'SelectionCriteria' => (object) array('Archived' => 'NO'),
                'FieldNames' => array('Login', 'Phone', 'ClientInfo', 'Grants'),
                'Page' => array(
                    'Limit' => 10000,
                    'Offset' => 0
                )
            ),
        );

        // Преобразование входных параметров запроса в формат JSON
        $body = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        // Создание контекста потока: установка HTTP-заголовков и тела запроса
        $streamOptions = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => $headers,
                'content' => $body
            ),
        ));

        // Выполнение запроса, получение результата
        $result = file_get_contents($AgencyClientsURL, 0, $streamOptions);

        return $result;
    }

    public function getAllClients($login, $url)
    {
        // Параметры запроса к серверу API Директа
        $params = array(
            'method' => 'AccountManagement',
            'token' => $this->token,
            'param' => array(
                'Action' => 'Get',
                'SelectionCriteria' => array(
                    'Logins' => [$login],
                    'AccountIDS' => [],
                ),
            ),
        );
        // Преобразование входных параметров запроса в формат JSON
        $body = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        // Создание контекста потока: установка HTTP-заголовков и тела запроса
        $streamOptions = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => $body
            ),
        ));

        // Выполнение запроса, получение результата о остатке
        $result = file_get_contents($url, false, $streamOptions);
        $result = json_decode($result);

        if (isset($result->error)) {
            $apiErr = $result->error;
            echo "Ошибка API {$apiErr->error_code}: {$apiErr->error_string} - {$apiErr->error_detail} (RequestId: {$apiErr->request_id})";
        }

        return $result;
    }

    public function getReport($login, $url, $dateRangeType)
    {
        // --------Получение расходов-----------
        // Создание тела запроса
        $params = [
            "params" => [
                "SelectionCriteria" => [
                    "Filter" => [
                        [
                            "Field" => "Cost",
                            "Operator" => "GREATER_THAN",
                            "Values" => ["0"]
                        ]
                    ]
                ],
                "FieldNames" => ["Date", "Cost"],
                "ReportName" => ("Report4"),
                "ReportType" => "CAMPAIGN_PERFORMANCE_REPORT",
                "DateRangeType" => $dateRangeType,
                "Format" => "TSV",
                "IncludeVAT" => "NO",
                "IncludeDiscount" => "NO"
            ]
        ];

        // Преобразование входных параметров запроса в формат JSON
        $body = json_encode($params);

        // Создание HTTP-заголовков запроса
        $headers = array(
            "Content-Type: application/x-www-form-urlencoded\r\n".
            // OAuth-токен. Использование слова Bearer обязательно
            "Authorization: Bearer $this->token",
            // Логин клиента рекламного агентства
            "Client-Login: $login",
            // Язык ответных сообщений
            "Accept-Language: ru",
            // Режим формирования отчета
            "processingMode: auto",
            // Формат денежных значений в отчете
            "returnMoneyInMicros: false",
            // Не выводить в отчете строку с названием отчета и диапазоном дат
            "skipReportHeader: true",
            // Не выводить в отчете строку с названиями полей
            "skipColumnHeader: true",
            // Не выводить в отчете строку с количеством строк статистики
            "skipReportSummary: true"
        );
        // Создание контекста потока: установка HTTP-заголовков и тела запроса для расходов
        $streamOptions = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'header' => $headers,
                'content' => $body
            ),
        ));

        // Выполнение запроса, получение результата о расходах
        $result = file_get_contents($url, false, $streamOptions);

        return $result;
    }

    public function parserReport($text)
    {
        $delimiter = "\n";
        $bits = [];
        $splitcontents = explode($delimiter, $text);
        foreach ($splitcontents as $line)
        {
            if ($line != "")
            {
                $bits[] = explode("\t", $line);
            }
        }
        return $bits;
    }

    public function getCost($bits, $days, $dailyAverageThreshold)
    {
        $dailyAverage = 0;
        $arrayBit = $bits[0];
        foreach ($arrayBit as $bit) {
            $dailyAverage += $bit[1];
        }
        // Среднедневное = Расход за три дня / $days
        $dailyAverage = $dailyAverage / $days;

        // Порог = Среднедневное * $dailyAverageThreshold
        $threshold = $dailyAverage * $dailyAverageThreshold;

        return $threshold;
    }

}