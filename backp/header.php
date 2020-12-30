<?php
/**
 * Header file for the Twenty Twenty WordPress default theme.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package WordPress
 * @subpackage Twenty_Twenty
 * @since Twenty Twenty 1.0
 */

?><!DOCTYPE html>

<html class="no-js piter" <?php language_attributes(); ?>>

	<head>

		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no, user-scalable=no" >

		<meta name="description" content="Скупка радиодеталей по выгодным ценам. Приём радиодеталей с содержанием драгметаллов, конденсаторы км, узнать цены на радиодетали. Оставляйте заявку, заберём в течение часа. Деньги сразу!" >
		<link rel="profile" href="https://gmpg.org/xfn/11">
		<title>Схематика | Скупка радиодеталей дорого. Заберём и оплатим сразу</title>
		<?php wp_head(); ?>

		<?php
					
		include_once('simple_html_dom.php');
		

		//https://stackoverflow.com/questions/4545790/file-get-contents-returns-403-forbidden
		//https://api.exchangeratesapi.io/latest?base=USD
		
		date_default_timezone_set("Europe/London");
		//$curtime = date("dmYHi");
		$curtime = date("YmdHi");
		// Get any existing copy of our transient data
		
		
		
			//$url = "https://www.kitco.com/gold.londonfix.html";
			
			$context = stream_context_create(
				array(
					"http" => array(
						"header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
					)
				)
			);
			

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
			
			
			$html = file_get_html('https://www.kitco.com/gold.londonfix.html', false, $context);

			$stocks_array = array(
				"gold" => $result2["gold"]["pm"]["usd"],
				"silver" => $result2["silver"]["usd"],
				"platinum" => $result2["platinum"]["pm"]["usd"],
				"palladium" => $result2["palladium"]["pm"]["usd"],
				"rub" => $result["rates"]["RUB"],
				"eur" => $result["rates"]["EUR"],
				"date" => $curtime
			);
		
			$stocks = $stocks_array;
			
			
			echo '<script> console.log("Stocks is received!");</script>';
			echo '<script> var old_stock_rub ='.$stocks["rub"].'; var old_stock_eur = '.$stocks["eur"].'; var old_stock_gold='.$stocks["gold"].'; var old_stock_silver='.$stocks["silver"].'; var old_stock_platinum='.$stocks["platinum"].'; var old_stock_palladium='.$stocks["palladium"].'; var old_stock_date = '.$stocks["date"].';</script>';
			//echo '<script> var stock_rub ='.$stocks["rub"].'; var stock_eur = '.$stocks["eur"].'; var stock_gold='.$stocks["gold"].'; var stock_silver='.$stocks["silver"].'; var stock_platinum='.$stocks["platinum"].'; var stock_palladium='.$stocks["palladium"].'; var stock_date = '.$stocks["date"].';</script>';
echo '<script> var stock_rub ='.$stocks["rub"].'; var stock_eur = '.$stocks["eur"].'; var stock_gold='.$stocks["gold"].'; var stock_silver='.$stocks["silver"].'; var stock_platinum=1009.00; var stock_palladium=2330; var stock_date = '.$stocks["date"].';</script>';
				
		?>
		

		<link href="/wp-content/themes/sxematika/assets/css/main.css?v=7" rel="stylesheet" />
		
	</head>

	<body <?php body_class(); ?>>

	
<div style="display: none;">
	<?php echo do_shortcode('[WP-Coder id="8"]'); ?>
</div>
	
<!--header-->
<div id="header">
  <div class="in">
    <div class="flex">
	 <div class="mobile burger">
        <div class="btn-burger">
          <span></span>
        </div>
        <div id="mobile_menu">
          <ul>
           <li><a href="/about">О нас</a></li>
            <li><a href="/katalog">Каталог</a></li>
            <li><a href="/blog">Блог</a></li>
            <li><a href="/contacts">Контакты</a></li>
          </ul>
          <div class="top-telephone">
            <a href="tel:<?php echo do_shortcode('[WP-Coder id="2"]');?>"><?php echo do_shortcode('[WP-Coder id="1"]');?></a> 
          </div>
		   <div class="selector top-selector" style="margin: 0 0 20px; display: flex; justify-content: center;">
            <div class="selector-title" id="mobile-popup-city"><span>Санкт-Петербурге</span></div>
			<div id="citypopup" style="display: none;">
				<div class="inners selector">
					 <ul class="selector-dd">
						  <li onclick="location.href='https://sxematika.ru'"><span data-title="Москве">Москва</span></li>
						  <li onclick="location.href='https://krasnodar.sxematika.ru'"><span data-title="Краснодаре">Краснодар</span></li>
						  <li onclick="location.href='https://essentuki.sxematika.ru'"><span data-title="Ессентуках">Ессентуки</span></li>
						  <li onclick="location.href='https://simferopol.sxematika.ru'"><span data-title="Симферополе">Симферополь</span></li>
						  <li onclick="location.href='https://voronezh.sxematika.ru'"><span data-title="Воронеже">Воронеж</span></li>
						  <li class="active" onclick="location.href='https://spb.sxematika.ru'"><span data-title="Санкт-Петербурге">Санкт-Петербург</span></li>
						  <li onclick="location.href='https://nnovgorod.sxematika.ru'"><span data-title="Нижнем Новгороде">Нижний Новгород</span></li>
						  <li onclick="location.href='https://izhevsk.sxematika.ru'"><span data-title="Ижевске">Ижевск</span></li>
						  <li onclick="location.href='https://penza.sxematika.ru'"><span data-title="Пензе">Пенза</span></li>
						  <li onclick="location.href='https://kazan.sxematika.ru'"><span data-title="Казани">Казань</span></li>
					</ul>
				</div>
			</div>
          </div>
          <div class="btn-group top-btn-group">
            <a class="btn btn-secondary" href="/skachat-katalog/">Скачать прайс</a>
          </div>
        </div>
      </div>
      <div class="flex flex-pull-left">
        <a href="/" class="logo">
        </a>
        <div id="top-menu">
          <ul>
            <li><a href="/about">О нас</a></li>
            <li><a href="/katalog">Каталог</a></li>
            <li><a href="/blog">Блог</a></li>
            <li><a href="/contacts">Контакты</a></li>
          </ul>
        </div>
      </div>
      <div class="flex flex-pull-right">
        <div class="top-selector-wrapper">
          <div class="top-selector-wrapper__title">Скупка радиодеталей в</div>
          <div class="selector top-selector">
            <div class="selector-title"><span>Санкт-Петербурге</span></div>
            <ul class="selector-dd">
              <li onclick="location.href='https://sxematika.ru'"><span data-title="Москве">Москва</span></li>
			  <li onclick="location.href='https://krasnodar.sxematika.ru'"><span data-title="Краснодаре">Краснодар</span></li>
			  <li onclick="location.href='https://essentuki.sxematika.ru'"><span data-title="Ессентуках">Ессентуки</span></li>
			  <li onclick="location.href='https://simferopol.sxematika.ru'"><span data-title="Симферополе">Симферополь</span></li>
			  <li onclick="location.href='https://voronezh.sxematika.ru'"><span data-title="Воронеже">Воронеж</span></li>
			  <li class="active" onclick="location.href='https://spb.sxematika.ru'"><span data-title="Санкт-Петербурге">Санкт-Петербург</span></li>
			  <li onclick="location.href='https://nnovgorod.sxematika.ru'"><span data-title="Нижнем Новгороде">Нижний Новгород</span></li>
			  <li onclick="location.href='https://izhevsk.sxematika.ru'"><span data-title="Ижевске">Ижевск</span></li>
			  <li onclick="location.href='https://penza.sxematika.ru'"><span data-title="Пензе">Пенза</span></li>
			  <li onclick="location.href='https://kazan.sxematika.ru'"><span data-title="Казани">Казань</span></li>
            </ul>
          </div>
        </div>
        <div class="top-telephone">
            <a href="tel:<?php echo do_shortcode('[WP-Coder id="2"]');?>"><?php echo do_shortcode('[WP-Coder id="1"]');?></a> 
        </div>
		<div class="btn-group social-btn-group">
          <a class="btn btn-wt-color" href="https://wa.me/<?php echo do_shortcode('[WP-Coder id="3"]');?>?text=Здравствуйте.%20Хочу%20узнать%20цены%20на%20радиодетали." onclick="ym(<?php echo do_shortcode('[WP-Coder id="7"]');?>,'reachGoal','whatsapp');VK.Goal('conversion'); return true;" target="_blank"></a>
          <a title="Должен быть установлен вайбер для ПК" href="viber://chat?number=%2B<?php echo do_shortcode('[WP-Coder id="3"]');?>" target="_blank" onclick="ym(<?php echo do_shortcode('[WP-Coder id="7"]');?>,'reachGoal','viber');VK.Goal('conversion'); return true;" class="btn btn-vb-color desktop"></a>
          <a href="viber://add?number=<?php echo do_shortcode('[WP-Coder id="3"]');?>" target="_blank" onclick="ym(<?php echo do_shortcode('[WP-Coder id="7"]');?>,'reachGoal','viber');VK.Goal('conversion'); return true;" class="btn btn-vb-color mobile"></a>
        </div>
        <div class="btn-group top-btn-group">
          <!--<a class="btn btn-primary" href="#">Оставить заявку</a>-->
          <a class="btn btn-secondary" href="/skachat-katalog/">Скачать прайс</a>
        </div>
		
      </div>
    </div>
  </div>
</div>
<!--end of header-->
<style>
#citypopup{
    padding: 20px 41px;
}
#citypopup .fancybox-slide--html .fancybox-close-small{
	padding: 5px;
}
#citypopup .fancybox-button{
	height: 35px;
	width: 35px;
		padding: 5px;
}
#citypopup svg{
	pointer-events: none;
}
#citypopup ul{
	opacity: 1 !important;
	display: block !important;
	margin: 0 !important;
	position: relative !important;	
}
#citypopup ul li{
    padding: 10px 10px 10px 20px;
    list-style: none;
    position: relative;
    cursor: pointer;
    line-height: 0;
    text-align: left;
}
</style>
<script>
	jQuery('document').ready(function(){
		jQuery('#mobile-popup-city').on('click', function(){
			  $.fancybox.open({
				src: "#citypopup",
				type: 'inline',
				toolbar: false
				/*opts : {
				  beforeShow : function( instance, current ) {
					$(".fancybox-toolbar").css("display","none");
				  },
				  afterShow : function( instance, current ) {
					$(".fancybox-content").append("<div class='fancy_close'><svg xmlns=\"http://www.w3.org/2000/svg\" version=\"1\" viewBox=\"0 0 24 24\"><path d=\"M13 12l5-5-1-1-5 5-5-5-1 1 5 5-5 5 1 1 5-5 5 5 1-1z\"></path></svg></div>");
				  }
				}*/
				//fancybox-content
			  });
		});
	});
</script>
		<?php

