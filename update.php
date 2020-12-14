<?php


/* ToDO
1. Делать запрос на АПИ каждые 5 минут, получать данные, сравнивать с текущими
2. Если данные обновились, делать запрос курса валют и обновлять всё в файле
3. Если АПИ не доступен - пробовать парсить сайт китко
4. Если парсинг недоступен, отправлять письмо на почту, сохранить последние доступные данные в таблицу ручного заполнения, выводить данные из таблицы с ручным заполнением
5. Сделать интерфейс с отображением работы АПИ и ПАРСИНГА (работают или нет), на этой странице принудительно сделать запрос на АПИ и ЗАПАРСИТЬ с китко
6. Сделать интерфейс ручного ввода данных и функцию или метку, которая бы брала данные из таблицы ручного заполнения, а не АПИ
*/

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
$old_moscowstamp = $data->moscowstamp;
/*$gold = $data->gold->pm->usd;
$silver = $data->silver->usd;
$platinum = $data->platinum->pm->usd;
$palladium = $data->palladium->pm->usd;*/
//$rub => $data["rates"]["RUB"],
//$eur => $data["rates"]["EUR"],
//$date => $data

?>
<!-- Выводим информацию на экран -->

<p>Золото: <?=$old_gold?></p>
<p>Серебро: <?=$old_silver?></p>
<p>Платина: <?=$old_platinum?></p>
<p>Паладий: <?=$old_palladium?></p>
<p>Курс руб: <?=$old_rub?></p>
<p>Курс евро: <?=$old_eur?></p>
<p>Время с АПИ: <?=$old_timestamp?></p>
<p>Время обновления курса валюты по Москве: <?=$old_moscowstamp?></p>
<hr>


<?php 
/*Часть 2. Получаем данные из АПИ*/


//металлы
$url2 = 'https://prices.lbma.org.uk/json/today.json'; 
$curl2 = curl_init($url2);
curl_setopt($curl2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl2, CURLOPT_HTTPHEADER, [
  'X-RapidAPI-Host: hhttps://prices.lbma.org.uk',
  'Content-Type: application/json'
]);
$response2 = curl_exec($curl2);
curl_close($curl2);
$result2 = json_decode($response2, true);

//валюта
$url = 'https://api.exchangeratesapi.io/latest?base=USD'; 
$curl = curl_init($url);

curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, [
  'X-RapidAPI-Host: https://api.exchangeratesapi.io',
  'Content-Type: application/json'
]);
$response = curl_exec($curl);
curl_close($curl);
$result = json_decode($response, true);


$curtime = date("YmdHi"); //московское время текущее
$updatetime = date("Ymd").'0600'; //московское время обновления курса валют

$stocks_array = array(
	"gold" => $result2["gold"]["pm"]["usd"],
	"silver" => $result2["silver"]["usd"],
	"platinum" => $result2["platinum"]["pm"]["usd"],
	"palladium" => $result2["palladium"]["pm"]["usd"],
	"rub" => $result["rates"]["RUB"],
	"eur" => $result["rates"]["EUR"],
	"date" => $curtime
);

$api_stocks = $stocks_array;

?>
    <h4>Получение данных через АПИ...</h4>
    <p>Золото: <?=$api_stocks["gold"]?></p>
    <p>Серебро: <?=$api_stocks["silver"]?></p>
    <p>Платина: <?=$api_stocks["platinum"]?></p>
    <p>Паладий: <?=$api_stocks["palladium"]?></p>
    <p>Руб: <?=$api_stocks["rub"]?></p>
    <p>Евр: <?=$api_stocks["eur"]?></p>
    <p>Время (Москва): <?=$curtime?></p>
    <p>Время Обновления: <?=$updatetime?></p>
    <hr>

<?php 
/*Часть 3. Получаем данные парсингом*/
include_once('simple_html_dom.php');
//"https://www.kitco.com/gold.londonfix.html";


if($api_stocks["gold"] < 3 || $api_stocks["silver"] < 3 || $api_stocks["platinum"] < 3 || $api_stocks["palladium"]< 3){

    $context = stream_context_create(
        array(
            "http" => array(
                "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
            )
        )
    );

    $html = file_get_html('https://www.kitco.com/gold.londonfix.html', false, $context);

    $stocks_array_parced = array(
        "gold" => $html->find('#content div.lf_prices', 0)->find(' tr.even', 0)->find('td', 1)->plaintext,
        "silver" => $html->find('#content div.lf_prices', 0)->find(' tr.even', 0)->find('td', 2)->plaintext,
        "platinum" => $html->find('#content div.lf_prices', 0)->find(' tr.even', 0)->find('td', 5)->plaintext,
        "palladium" => $html->find('#content div.lf_prices', 0)->find(' tr.even', 0)->find('td', 7)->plaintext,
        "date" => $curtime
    );

    echo "<h4>Данные по АПИ НЕ получены или получены не все, получение данных парсингом...</h4>";
    echo "<p>Золото: ".$stocks_array_parced["gold"]."</p>";
    echo "<p>Серебро: ".$stocks_array_parced["silver"]."</p>";
    echo "<p>Платина: ".$stocks_array_parced["platinum"]."</p>";
    echo "<p>Паладий: ".$stocks_array_parced["palladium"]."</p>";
    echo "<p>Время (Москва): ".$curtime."</p>";

};

?>



<?php



	//$parced_flag = 0;
	
	/*function get_parced_data($type, $parced_flag, $curtime)
	{
	   $context = stream_context_create(
		array(
			"http" => array(
				"header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
			)
		)
	  );

    if($parced_flag == 0) {
        $html = file_get_html('https://www.kitco.com/gold.londonfix.html', false, $context);

        $stocks_array_parced = array(
            "gold" => $html->find('#content div.lf_prices', 0)->find(' tr.even', 0)->find('td', 1)->plaintext,
            "silver" => $html->find('#content div.lf_prices', 0)->find(' tr.even', 0)->find('td', 2)->plaintext,
            "platinum" => $html->find('#content div.lf_prices', 0)->find(' tr.even', 0)->find('td', 5)->plaintext,
            "palladium" => $html->find('#content div.lf_prices', 0)->find(' tr.even', 0)->find('td', 7)->plaintext,
            "date" => $curtime
        );

        echo "parced<br/>";

    }

		
		switch ($type) {
			case "gold":
				return "gold";
				break;
			case "silver":
				return "silver";
				break;
			case "platinum":
				return "platinum";
				break;
			case "palladium":
				return "palladium";
				break;
			case "":
				return $stocks_array_parced;
				break;
		}
		
	}*/

?>




<?php 
/*Часть 4. Проверка и сбор данных*/

if($api_stocks["gold"] > 2) {
    $selected_gold = $api_stocks["gold"];
} else {
    $selected_gold =  $stocks_array_parced["gold"];
}

if($api_stocks["silver"] > 2) {
 $selected_silver = $api_stocks["silver"];
} else {
    $selected_silver = $stocks_array_parced["silver"];
}

if($api_stocks["platinum"] > 2) {
 $selected_platinum = $api_stocks["platinum"];
} else {
    $selected_platinum = $stocks_array_parced["platinum"];

}

if($api_stocks["palladium"] > 2) {
 $selected_palladium = $api_stocks["palladium"];
} else {
    $selected_palladium = $stocks_array_parced["palladium"];
}

?>
<h4>Результирующий блок</h4>
<p>Золото: <?=$selected_gold?></p>
<p>Серебро: <?=$selected_silver?></p>
<p>Платина: <?=$selected_platinum?></p>
<p>Паладий: <?=$selected_palladium?></p>

<?php 
/*
//Создаем массив с данными
$info = [
  "name" => "Костя",
  "lname" => "Тестов",
  "contacts" => [
    "phone" => "12345678",
    "mail" => "mail@mail.to",
    "skype" => "kost1999",
    "site" => "www.site.to",
  ],
];
// преобразовываем его в json вид
$json = json_encode($info);
// создаем новый файл
$file = fopen('today.json', 'w');
// и записываем туда данные
$write = fwrite($file,$json);
// проверяем успешность выполнения операции
if($write) echo "Данные успешно записаны!<br>";
else echo "Не удалось записать данные!<br>";
//закрываем файл
fclose($file);
*/

?>