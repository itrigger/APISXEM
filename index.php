<?php
include_once('simple_html_dom.php');
/*
1. Делаем запрос на сервер А и берем курсы металлов
2. Если ошибка, делаем запрос на сервер Б и берем курсы металлов
3. Если ошибка

*/




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

            } else {
                $metals = $metals_api2_array;
                $real_source = "metals_api_2";
                //отправляем письмо админу
                $message = "Не доступны данные из источника metal_api_1. Данные взяты из источника metal_api_2";

            }
        } else {
            $metals = $metals_api1_array;
            $real_source = "metals_api_1";

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
                $real_source2 = "stable.json";



                //отправляем письмо админу
                $message = "Не доступен ни один из источников данных для металлов, данные взяты из stable.json";
            }

        }
    }




    ?>


    <h3>Полученные данные из источника - <?=$real_source?> </h3>
    <p>Золото: <?=$metals["gold"]?></p>
    <p>Серебро: <?=$metals["silver"]?></p>
    <p>Платина: <?=$metals["platinum"]?></p>
    <p>Паладий: <?=$metals["palladium"]?></p>
    <h3>Полученные данные из источника - <?=$real_source2?> </h3>
    <p>Рубль: <?=$stocks["rub"]?></p>
    <p>Евро: <?=$stocks["eur"]?></p>

    <div class="alert error"><?=$message?></div>
    <?php

} //глобальная проверка открылся ли файл настроек

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
echo 'Время сервера: '.$server_time;
echo '<hr/>';
echo 'Время апдейта: '.$next_update_date.$global_update_time;
echo '<hr/>';
$time = strtotime("+1 day");
$nextdate = date("Ymd", $time);
echo 'Время апдейта: '.$nextdate;
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
<h3>Курсы с файла today.json</h3>
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