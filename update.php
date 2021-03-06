<?php
include_once('simple_html_dom.php');
/*
1. Делаем запрос на сервер А и берем курсы металлов
2. Если ошибка, делаем запрос на сервер Б и берем курсы металлов
3. Если ошибка

*/


define('TELEGRAM_TOKEN', '1439263356:AAGCyfwUIwTcB9UZuAL4T3y1KgcNrOsdJDk');
define('TELEGRAM_CHATID', '36201502');

function message_to_telegram($text)
{
    $ch = curl_init();
    curl_setopt_array(
        $ch,
        array(
            CURLOPT_URL => 'https://api.telegram.org/bot' . TELEGRAM_TOKEN . '/sendMessage',
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POSTFIELDS => array(
                'chat_id' => TELEGRAM_CHATID,
                'text' => $text,
            ),
        )
    );
    curl_exec($ch);
}


$to = 'mkey87@mail.ru';
$subject = 'ВНИМАНИЕ!!! ПРОБЛЕМА С КУРСАМИ!!!';
$message = 'Проблемы с курсами';
$headers = 'From: webmaster@sxematika.ru' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();


$server_time = date("YmdHi");

//Открываем файл с настройками
// metal_api_1 или metal_api_2 или auto
// source_stocks_1 или source_stocks_2 или auto


$string1 = file_get_contents("settings.json");
// Превращаем строку в объект
$data1 = json_decode($string1);
// Отлавливаем ошибки возникшие при превращении
switch (json_last_error()) {
    case JSON_ERROR_NONE:
        $data_error1 = '';
        break;
    case JSON_ERROR_DEPTH:
        $data_error1 = 'Достигнута максимальная глубина стека';
        break;
    case JSON_ERROR_STATE_MISMATCH:
        $data_error1 = 'Неверный или не корректный JSON';
        break;
    case JSON_ERROR_CTRL_CHAR:
        $data_error1 = 'Ошибка управляющего символа, возможно верная кодировка';
        break;
    case JSON_ERROR_SYNTAX:
        $data_error1 = 'Синтаксическая ошибка';
        break;
    case JSON_ERROR_UTF8:
        $data_error1 = 'Некорректные символы UTF-8, возможно неверная кодировка';
        break;
    default:
        $data_error1 = 'Неизвестная ошибка';
        break;
}
// Если ошибки есть, то выводим их
if($data_error1 !='') echo '<h2 style="color:red;">'.$data_error1.'</h2>';
// Присваиваим данные переменным
$source_metals = $data1->source_metals;
$source_stocks = $data1->source_stocks;
$today_file = $data1->today_file;
$metals_msg_send = $data1->metals_msg_send;
$stocks_msg_send = $data1->stocks_msg_send;
$global_update_time = $data1->global_update_time;
$next_update_date = $data1->next_update_date;

echo '<table><tr><th>Источник курса металлов</th><th>Источник курса валют</th><th>Имя файла с курсами за сегодня</th><th>Отправлено сегодня письмо админу о металлах?</th><th>Отправлено сегодня письмо админу о курсах?</th></tr>';
echo '<tr><td>'.$source_metals.'</td><td>'.$source_stocks.'</td><td>'.$today_file.'</td><td>'.$metals_msg_send.'</td><td>'.$stocks_msg_send.'</td></tr></table>';



if($data1){ //глобальная проверка, открылся ли файл настроек

    if($source_metals == "metal_api_1"){
        /*Получаем данные по металлам из АПИ 1*/
        $url2 = 'https://prices.lbma.org.uk/json/today.json';
        $curl2 = curl_init($url2);
        curl_setopt($curl2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl2, CURLOPT_HTTPHEADER, [
            'X-RapidAPI-Host: https://prices.lbma.org.uk',
            'Content-Type: application/json'
        ]);
        $response2 = curl_exec($curl2);
        curl_close($curl2);
        $result2 = json_decode($response2, true);
       // $curtime = date("YmdHi"); //московское время текущее
        $updatetime = date("Ymd").'0600'; //московское время обновления курса валют
        $metals_api1_array = array(
            "gold" => $result2["gold"]["pm"]["usd"],
            "silver" => $result2["silver"]["usd"],
            "platinum" => $result2["platinum"]["pm"]["usd"],
            "palladium" => $result2["palladium"]["pm"]["usd"]
        );
        $metals = $metals_api1_array;
        $real_source = $source_metals;

        /*Обновление файла today.json
        $jsonString = file_get_contents($today_file);
        $data = json_decode($jsonString, true);
        $data['gold'] = $metals["gold"];
        $data['silver'] = $metals["silver"];
        $data['platinum'] = $metals["platinum"];
        $data['palladium'] = $metals["palladium"];
        $newJsonString = json_encode($data);
        file_put_contents($today_file, $newJsonString);

        $jsonString = file_get_contents('stable.json');
        $data = json_decode($jsonString, true);
        $data['gold'] = $metals["gold"];
        $data['silver'] = $metals["silver"];
        $data['platinum'] = $metals["platinum"];
        $data['palladium'] = $metals["palladium"];
        $newJsonString = json_encode($data);
        file_put_contents('stable.json', $newJsonString);
        /***************/

    } elseif ($source_metals == "metal_api_2"){
        //Получаем данные по металлам парсингом сайта "https://www.kitco.com/gold.londonfix.html"

        $context = stream_context_create(
            array(
                "http" => array(
                    "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
                )
            )
        );

        $html = file_get_html('https://www.kitco.com/gold.londonfix.html', false, $context);

        $metals_api2_array = array(
            "gold" => $html->find('#content div.lf_prices',0)->find(' tr.even',0)->find('td',1)->plaintext,
            "silver" => $html->find('#content div.lf_prices',0)->find(' tr.even',0)->find('td',2)->plaintext,
            "platinum" => $html->find('#content div.lf_prices',0)->find(' tr.even',0)->find('td',5)->plaintext,
            "palladium" => $html->find('#content div.lf_prices',0)->find(' tr.even',0)->find('td',7)->plaintext
        );
        $metals = $metals_api2_array;
        $real_source = $source_metals;

        /*Обновление файла today.json
        $jsonString = file_get_contents($today_file);
        $data = json_decode($jsonString, true);
        $data['gold'] = $metals["gold"];
        $data['silver'] = $metals["silver"];
        $data['platinum'] = $metals["platinum"];
        $data['palladium'] = $metals["palladium"];
        $newJsonString = json_encode($data);
        file_put_contents($today_file, $newJsonString);

        $jsonString = file_get_contents('stable.json');
        $data = json_decode($jsonString, true);
        $data['gold'] = $metals["gold"];
        $data['silver'] = $metals["silver"];
        $data['platinum'] = $metals["platinum"];
        $data['palladium'] = $metals["palladium"];
        $newJsonString = json_encode($data);
        file_put_contents('stable.json', $newJsonString);
        /***************/

    } elseif ($source_metals == "auto"){
        //пробуем получить по metal_API_1
        /*Получаем данные по металлам из АПИ 1*/
        $url2 = 'https://prices.lbma.org.uk/json/today.json';
        $curl2 = curl_init($url2);
        curl_setopt($curl2, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl2, CURLOPT_HTTPHEADER, [
            'X-RapidAPI-Host: https://prices.lbma.org.uk',
            'Content-Type: application/json'
        ]);
        $response2 = curl_exec($curl2);
        curl_close($curl2);
        $result2 = json_decode($response2, true);
        $metals_api1_array = array(
            "gold" => $result2["gold"]["pm"]["usd"],
            "silver" => $result2["silver"]["usd"],
            "platinum" => $result2["platinum"]["pm"]["usd"],
            "palladium" => $result2["palladium"]["pm"]["usd"]
        );

        if(($metals_api1_array["gold"]<=3)||($metals_api1_array["silver"]<=3)||($metals_api1_array["platinum"]<=3)||($metals_api1_array["palladium"]<=3)){
            echo '<div class="alert error">metal_api_1 выдает не полные данные, пробуем metal_api_2...</div>';

            $context = stream_context_create(
                array(
                    "http" => array(
                        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
                    )
                )
            );

            $html = file_get_html('https://www.kitco.com/gold.londonfix.html', false, $context);

            $metals_api2_array = array(
                "gold" => $html->find('#content div.lf_prices',0)->find(' tr.even',0)->find('td',1)->plaintext,
                "silver" => $html->find('#content div.lf_prices',0)->find(' tr.even',0)->find('td',2)->plaintext,
                "platinum" => $html->find('#content div.lf_prices',0)->find(' tr.even',0)->find('td',5)->plaintext,
                "palladium" => $html->find('#content div.lf_prices',0)->find(' tr.even',0)->find('td',7)->plaintext
            );

            if(($metals_api2_array["gold"]<=3)||($metals_api2_array["silver"]<=3)||($metals_api2_array["platinum"]<=3)||($metals_api2_array["palladium"]<=3)){ //нет возможности получить полные данные ни по одному из способов
                echo '<div class="alert error">metal_api_2 выдает не полные данные, берем данные прошлые, отправляем письмо админу</div>';

                $string2 = file_get_contents("stable.json");

                $data2= json_decode($string2);

                switch (json_last_error()) {
                    case JSON_ERROR_NONE:
                        $data_error2 = '';
                        break;
                    case JSON_ERROR_DEPTH:
                        $data_error2 = 'Достигнута максимальная глубина стека';
                        break;
                    case JSON_ERROR_STATE_MISMATCH:
                        $data_error2 = 'Неверный или не корректный JSON';
                        break;
                    case JSON_ERROR_CTRL_CHAR:
                        $data_error2 = 'Ошибка управляющего символа, возможно верная кодировка';
                        break;
                    case JSON_ERROR_SYNTAX:
                        $data_error2 = 'Синтаксическая ошибка';
                        break;
                    case JSON_ERROR_UTF8:
                        $data_error2 = 'Некорректные символы UTF-8, возможно неверная кодировка';
                        break;
                    default:
                        $data_error2 = 'Неизвестная ошибка';
                        break;
                }

                if($data_error2 !='') echo $data_error2;

                $metals["gold"] = $data2->gold;
                $metals["silver"] = $data2->silver;
                $metals["platinum"] = $data2->platinum;
                $metals["palladium"] = $data2->palladium;
                $old_timestamp = $data2->timestamp;
                $real_source = "stable.json";
                //отправляем письмо админу
                $message = "Не доступен ни один из источников данных для металлов, данные взяты из stable.json";
                if($metals_msg_send == "0"){
                    mail($to, $subject, $message, $headers);
                    /*Обновление файла настроек*/
                    $jsonString = file_get_contents('settings.json');
                    $data = json_decode($jsonString, true);
                    $data['metals_msg_send'] = "1";
                    $newJsonString = json_encode($data);
                    file_put_contents('settings.json', $newJsonString);
                    /***************/

                    message_to_telegram('Не доступен ни один из источников данных для металлов, данные взяты из stable.json');
                }
                /*Обновление файла today.json
                $jsonString = file_get_contents($today_file);
                $data = json_decode($jsonString, true);
                $data['gold'] = $metals["gold"];
                $data['silver'] = $metals["silver"];
                $data['platinum'] = $metals["platinum"];
                $data['palladium'] = $metals["palladium"];
                $newJsonString = json_encode($data);
                file_put_contents($today_file, $newJsonString);
                /***************/


            } else {
                $metals = $metals_api2_array;
                $real_source = "metals_api_2";
                //отправляем письмо админу
                $message = "Не доступны данные из источника metal_api_1. Данные взяты из источника metal_api_2";
                if($metals_msg_send == "0"){
                    mail($to, $subject, $message, $headers);
                    /*Обновление файла настроек*/
                    $jsonString = file_get_contents('settings.json');
                    $data = json_decode($jsonString, true);
                    $data['metals_msg_send'] = "1";
                    $newJsonString = json_encode($data);
                    file_put_contents('settings.json', $newJsonString);
                    /***************/

                    message_to_telegram('Не доступны данные из источника metal_api_1. Данные взяты из источника metal_api_2');
                }
                /*Обновление файла today.json
                $jsonString = file_get_contents($today_file);
                $data = json_decode($jsonString, true);
                $data['gold'] = $metals["gold"];
                $data['silver'] = $metals["silver"];
                $data['platinum'] = $metals["platinum"];
                $data['palladium'] = $metals["palladium"];
                $newJsonString = json_encode($data);
                file_put_contents($today_file, $newJsonString);

                $jsonString = file_get_contents('stable.json');
                $data = json_decode($jsonString, true);
                $data['gold'] = $metals["gold"];
                $data['silver'] = $metals["silver"];
                $data['platinum'] = $metals["platinum"];
                $data['palladium'] = $metals["palladium"];
                $newJsonString = json_encode($data);
                file_put_contents('stable.json', $newJsonString);
                /***************/
            }
        } else {
            $metals = $metals_api1_array;
            $real_source = "metals_api_1";
            /*Обновление файла today.json
            $jsonString = file_get_contents($today_file);
            $data = json_decode($jsonString, true);
            $data['gold'] = $metals["gold"];
            $data['silver'] = $metals["silver"];
            $data['platinum'] = $metals["platinum"];
            $data['palladium'] = $metals["palladium"];
            $newJsonString = json_encode($data);
            file_put_contents($today_file, $newJsonString);

            $jsonString = file_get_contents('stable.json');
            $data = json_decode($jsonString, true);
            $data['gold'] = $metals["gold"];
            $data['silver'] = $metals["silver"];
            $data['platinum'] = $metals["platinum"];
            $data['palladium'] = $metals["palladium"];
            $newJsonString = json_encode($data);
            file_put_contents('stable.json', $newJsonString);
            /***************/
        }

    }



    /*получаем курсы валют*/
    if($source_stocks == "source_stocks_1"){
        $url = 'https://api.exchangeratesapi.io/latest?base=USD&symbols=EUR,RUB';
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'X-RapidAPI-Host: https://api.exchangeratesapi.io',
            'Content-Type: application/json'
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($response, true);
        $exchange_array2 = array(
            "rub" => $result["rates"]["RUB"],
            "eur" => $result["rates"]["EUR"],
            "date" => $result["date"],
        );

        $stocks = $exchange_array2;

        /*Обновление файла
        $jsonString = file_get_contents($today_file);
        $data = json_decode($jsonString, true);
        $data['rub'] = $stocks["rub"];
        $data['eur'] = $stocks["rub"];
        $newJsonString = json_encode($data);
        file_put_contents($today_file, $newJsonString);

        $jsonString = file_get_contents('stable.json');
        $data = json_decode($jsonString, true);
        $data['rub'] = $stocks["rub"];
        $data['eur'] = $stocks["rub"];
        $newJsonString = json_encode($data);
        file_put_contents('stable.json', $newJsonString);
        /***************/


    } elseif ($source_stocks == "source_stocks_2"){

        $url3 = 'https://openexchangerates.org/api/latest.json?app_id=a086cd9bbc274aa68a79c689aa3cb400&base=USD&symbols=RUB,EUR';

        $curl3 = curl_init($url3);
        curl_setopt($curl3, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl3, CURLOPT_HTTPHEADER, [
            'X-RapidAPI-Host: https://openexchangerates.org',
            'Content-Type: application/json'
        ]);
        $response3 = curl_exec($curl3);
        curl_close($curl3);
        $result3 = json_decode($response3, true);

        $exchange_array = array(
            "rub" => $result3["rates"]["RUB"],
            "eur" => $result3["rates"]["EUR"],
            "date" => $result3["timestamp"]
        );

        $stocks = $exchange_array;

        /*Обновление файла
        $jsonString = file_get_contents($today_file);
        $data = json_decode($jsonString, true);
        $data['rub'] = $stocks["rub"];
        $data['eur'] = $stocks["rub"];
        $newJsonString = json_encode($data);
        file_put_contents($today_file, $newJsonString);

        $jsonString = file_get_contents('stable.json');
        $data = json_decode($jsonString, true);
        $data['rub'] = $stocks["rub"];
        $data['eur'] = $stocks["rub"];
        $newJsonString = json_encode($data);
        file_put_contents('stable.json', $newJsonString);
        **************/

    } elseif ($source_stocks == "auto"){
        $url = 'https://api.exchangeratesapi.io/latest?base=USD&symbols=EUR,RUB';
        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'X-RapidAPI-Host: https://api.exchangeratesapi.io',
            'Content-Type: application/json'
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        $result = json_decode($response, true);
        $exchange_array2 = array(
            "rub" => $result["rates"]["RUB"],
            "eur" => $result["rates"]["EUR"],
            "date" => $result["date"],
        );

        if(($exchange_array2["rub"]>2) || ($exchange_array2["eur"]>2)){
            $stocks = $exchange_array2;
            $real_source2 = "stocks_api_1";

            /*Обновление файла
            $jsonString = file_get_contents($today_file);
            $data = json_decode($jsonString, true);
            $data['rub'] = $stocks["rub"];
            $data['eur'] = $stocks["eur"];
            $newJsonString = json_encode($data);
            file_put_contents($today_file, $newJsonString);

            $jsonString = file_get_contents('stable.json');
            $data = json_decode($jsonString, true);
            $data['rub'] = $stocks["rub"];
            $data['eur'] = $stocks["eur"];
            $newJsonString = json_encode($data);
            file_put_contents('stable.json', $newJsonString);
            **************/

        } else {
            $url3 = 'https://openexchangerates.org/api/latest.json?app_id=a086cd9bbc274aa68a79c689aa3cb400&base=USD&symbols=RUB,EUR';

            $curl3 = curl_init($url3);
            curl_setopt($curl3, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl3, CURLOPT_HTTPHEADER, [
                'X-RapidAPI-Host: https://openexchangerates.org',
                'Content-Type: application/json'
            ]);
            $response3 = curl_exec($curl3);
            curl_close($curl3);
            $result3 = json_decode($response3, true);

            $exchange_array = array(
                "rub" => $result3["rates"]["RUB"],
                "eur" => $result3["rates"]["EUR"],
                "date" => $result3["timestamp"]
            );
            if(($exchange_array["rub"]>2) || ($exchange_array["eur"]>2)){
                $stocks = $exchange_array;
                $message = "Не доступны данные из источника stocks_api_1. Данные взяты из источника stocks_api_2";
                $real_source2 = "stocks_api_2";

                /*Обновление файла*/
               /* $jsonString = file_get_contents($today_file);
                $data = json_decode($jsonString, true);
                $data['rub'] = $stocks["rub"];
                $data['eur'] = $stocks["eur"];
                $newJsonString = json_encode($data);
                file_put_contents($today_file, $newJsonString);

                $jsonString = file_get_contents('stable.json');
                $data = json_decode($jsonString, true);
                $data['rub'] = $stocks["rub"];
                $data['eur'] = $stocks["eur"];
                $newJsonString = json_encode($data);
                file_put_contents('stable.json', $newJsonString);*/
                /***************/

                if($stocks_msg_send == "0"){
                    mail($to, $subject, $message, $headers);

                    /*Обновление файла настроек*/
                    $jsonString = file_get_contents('settings.json');
                    $data = json_decode($jsonString, true);
                    $data['stocks_msg_send'] = "1";
                    $newJsonString = json_encode($data);
                    file_put_contents('settings.json', $newJsonString);
                    /***************/

                    message_to_telegram('Не доступны данные из источника stocks_api_1. Данные взяты из источника stocks_api_2');
                }
            } else {
                echo '<div class="alert error">stocks_api_2 выдает не полные данные, берем данные прошлые, отправляем письмо админу</div>';

                $string2 = file_get_contents("stable.json");

                $data2= json_decode($string2);

                switch (json_last_error()) {
                    case JSON_ERROR_NONE:
                        $data_error2 = '';
                        break;
                    case JSON_ERROR_DEPTH:
                        $data_error2 = 'Достигнута максимальная глубина стека';
                        break;
                    case JSON_ERROR_STATE_MISMATCH:
                        $data_error2 = 'Неверный или не корректный JSON';
                        break;
                    case JSON_ERROR_CTRL_CHAR:
                        $data_error2 = 'Ошибка управляющего символа, возможно верная кодировка';
                        break;
                    case JSON_ERROR_SYNTAX:
                        $data_error2 = 'Синтаксическая ошибка';
                        break;
                    case JSON_ERROR_UTF8:
                        $data_error2 = 'Некорректные символы UTF-8, возможно неверная кодировка';
                        break;
                    default:
                        $data_error2 = 'Неизвестная ошибка';
                        break;
                }

                if($data_error2 !='') echo $data_error2;

                $stocks["rub"] = $data2->rub;
                $stocks["eur"] = $data2->eur;
//                $old_timestamp = $data2->timestamp;
                $real_source2 = "stable.json";

                /*Обновление файла*/
               /* $jsonString = file_get_contents($today_file);
                $data = json_decode($jsonString, true);
                $data['rub'] = $stocks["rub"];
                $data['eur'] = $stocks["eur"];
                $newJsonString = json_encode($data);
                file_put_contents($today_file, $newJsonString);*/
                /***************/

                //отправляем письмо админу
                $message = "Не доступен ни один из источников данных для металлов, данные взяты из stable.json";
                if($stocks_msg_send == "0"){
                    mail($to, $subject, $message, $headers);

                    /*Обновление файла настроек*/
                    $jsonString = file_get_contents('settings.json');
                    $data = json_decode($jsonString, true);
                    $data['stocks_msg_send'] = "1";
                    $newJsonString = json_encode($data);
                    file_put_contents('settings.json', $newJsonString);
                    /***************/

                    message_to_telegram('Не доступен ни один из источников данных для металлов, данные взяты из stable.json');
                }
            }

        }
    }

/*    $jsonString = file_get_contents($today_file);
    $data = json_decode($jsonString, true);
    $data['timestamp'] = $server_time;
    $newJsonString = json_encode($data);
    file_put_contents($today_file, $newJsonString);*/




    ?>


    <h3>Полученные данные из источника - <?=$real_source?> </h3>
    <p>Золото: <?=$metals["gold"]?></p>
    <p>Серебро: <?=$metals["silver"]?></p>
    <p>Платина: <?=$metals["platinum"]?></p>
    <p>Паладий: <?=$metals["palladium"]?></p>
    <h3>Полученные данные из источника - <?=$real_source2?> </h3>
    <p>Рубль: <?=$stocks["rub"]?></p>
    <p>Евро: <?=$stocks["eur"]?></p>

<?php

} //глобальная проверка открылся ли файл настроек

?>







<?php


/* ToDO
1. Делать запрос на АПИ каждые 5 минут, получать данные, сравнивать с текущими
2. Если данные обновились, делать запрос курса валют и обновлять всё в файле
3. Если АПИ не доступен - пробовать парсить сайт китко
4. Если парсинг недоступен, отправлять письмо на почту, сохранить последние доступные данные в таблицу ручного заполнения, выводить данные из таблицы с ручным заполнением
5. Сделать интерфейс с отображением работы АПИ и ПАРСИНГА (работают или нет), на этой странице принудительно сделать запрос на АПИ и ЗАПАРСИТЬ с китко
6. Сделать интерфейс ручного ввода данных и функцию или метку, которая бы брала данные из таблицы ручного заполнения, а не АПИ
*/

?>



<h1 >Эталонная цена на КМ зел. Н90: === 
<span style="color:red;">
<?php
//var PLATINUM_DISCOUNT = 0.7;
//var PALLADIUM_DISCOUNT = 0.7;
//3 пл + 45 пал
//item_price = Math.round((item_gold * GOLD * GOLD_DISCOUNT + item_silver * SILVER * SILVER_DISCOUNT + item_platinum * PLATINUM * PLATINUM_DISCOUNT + item_palladium * PALLADIUM * PALLADIUM_DISCOUNT) * USD) * weight;

echo round(((($metals["platinum"]*0.7*3) + ($metals["palladium"]*0.65*45))*$stocks["rub"])/31.1,2);
?>
</span>
</h1>
<hr>

<?php






echo '<hr/>';
echo $server_time;
echo '<hr/>';
echo $next_update_date.$global_update_time;
echo '<hr/>';
$time = strtotime("+1 day");
$nextdate = date("Ymd", $time);
echo $nextdate;
echo '<hr/>';


/***************/
$tempArray = array(
    "gold" => $metals["gold"],
    "silver" => $metals["silver"],
    "platinum" => $metals["platinum"],
    "palladium" => $metals["palladium"],
    "rub" => $stocks["rub"],
    "eur" => $stocks["eur"],
    "timestamp" => $server_time
);


//$jsonString = file_get_contents('log.json');
//$data = json_decode($jsonString, true);

//Данные для лога
$data4[] = $tempArray;
$newJsonString2 = json_encode($data4, JSON_PRETTY_PRINT);

//file_put_contents('log.json', $newJsonString);



/***************/
//проверяем больше ли время сервера, времени последнего суточного апдейта
if(intval($server_time) > intval($next_update_date.$global_update_time)){
    echo "Обновляем всё<hr/>";
    //ежесуточный отчет админу
    $subject = "Время сервера: ".$server_time." | Gold: ".$metals["gold"]." | Silver: ".$metals["silver"]." | Platinum: ".$metals["platinum"]." | Palladium: ".$metals["palladium"]." | RUB: ".$stocks["rub"]." | EUR: ".$stocks["eur"];

    message_to_telegram($subject);

    //обновляем следующую дату обновления
    $jsonString = file_get_contents('settings.json');
    $data = json_decode($jsonString, true);
    $data['next_update_date'] = $nextdate;
    $data['metals_msg_send'] = "0";
    $data['stocks_msg_send'] = "0";
    $newJsonString = json_encode($data);
    file_put_contents('settings.json', $newJsonString);

    $jsonString2 = file_get_contents($today_file);
    $data2 = json_decode($jsonString2, true);
    $data2['gold'] = $metals["gold"];
    $data2['silver'] = $metals["silver"];
    $data2['platinum'] = $metals["platinum"];
    $data2['palladium'] = $metals["palladium"];
    $data2['rub'] = $stocks["rub"];
    $data2['eur'] = $stocks["eur"];
    $data2['timestamp'] = $server_time;
    $newJsonString2 = json_encode($data2);
    file_put_contents($today_file, $newJsonString2);

    $jsonString3 = file_get_contents('stable.json');
    $data3 = json_decode($jsonString3, true);
    $data3['gold'] = $metals["gold"];
    $data3['silver'] = $metals["silver"];
    $data3['platinum'] = $metals["platinum"];
    $data3['palladium'] = $metals["palladium"];
    $data3['rub'] = $stocks["rub"];
    $data3['eur'] = $stocks["eur"];
    $data3['timestamp'] = $server_time;
    $newJsonString3 = json_encode($data3);
    file_put_contents('stable.json', $newJsonString3);


}

//ежедневное логирование (в каждый файл по крону добавляется текущая информация по курсам)
function wh_log($log_msg)
{
    $log_filename = "log";
    if (!file_exists($log_filename))
    {
        mkdir($log_filename, 0777, true);
    }
    $log_file_data = $log_filename.'/log_' . date('d-M-Y') . '.log';
    file_put_contents($log_file_data, $log_msg . "\n", FILE_APPEND);
}

wh_log($newJsonString2); //функция логирования




/*Часть 1. Загружаем данные из нашего файла today.json*/
// Загружаем данные из файла в строку
$string = file_get_contents("today.json");
// Превращаем строку в объект
$data = json_decode($string);
// Отлавливаем ошибки возникшие при превращении
switch (json_last_error()) {
    case JSON_ERROR_NONE:
        $data_error = '';
        break;
    case JSON_ERROR_DEPTH:
        $data_error = 'Достигнута максимальная глубина стека';
        break;
    case JSON_ERROR_STATE_MISMATCH:
        $data_error = 'Неверный или не корректный JSON';
        break;
    case JSON_ERROR_CTRL_CHAR:
        $data_error = 'Ошибка управляющего символа, возможно верная кодировка';
        break;
    case JSON_ERROR_SYNTAX:
        $data_error = 'Синтаксическая ошибка';
        break;
    case JSON_ERROR_UTF8:
        $data_error = 'Некорректные символы UTF-8, возможно неверная кодировка';
        break;
    default:
        $data_error = 'Неизвестная ошибка';
        break;
}
// Если ошибки есть, то выводим их
if($data_error !='') echo $data_error;
// Присваиваим данные переменным
$old_gold = $data->gold;
$old_silver = $data->silver;
$old_platinum = $data->platinum;
$old_palladium = $data->palladium;
$old_rub = $data->rub;
$old_eur = $data->eur;
$old_timestamp = $data->timestamp;

?>

<!-- Выводим информацию на экран -->
<h3>Курсы с файла today.json (тут будут перезаписываться)</h3>
<p>Золото: <?=$old_gold?></p>
<p>Серебро: <?=$old_silver?></p>
<p>Платина: <?=$old_platinum?></p>
<p>Паладий: <?=$old_palladium?></p>
<p>Курс руб: <?=$old_rub?></p>
<p>Курс евро: <?=$old_eur?></p>
<p>Время: <?=$old_timestamp?></p>
<hr>



<style>
    table td,
    table th{
        border: 1px solid gray;
        padding: 5px;
    }
    .alert{
        padding: 5px;
        background: #f0cb07;
        color: #000;
        font-weight: bold;
        margin: 10px;
        border-radius: 5px;
    }
    .alert.error{
        background: red;
        color: #fff;
    }
    .alert.success{
        background: green;
        color: #fff;
    }
</style>