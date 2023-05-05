<?php

define('MAX_FILE_SIZE', 600000000000000000); 
use Symfony\Component\Panther\Client;
require "vendor/autoload.php";
require "include/simple_html_dom.php";

//изза того что страница генерируется жаваскриптом, нужно запустить браузер, выполнить весь жс и только потом парсить
$client = Client::createChromeClient();
$crawler = $client->request('GET', 'https://us.shein.com/SHEIN-EZwear-1pc-Heart-Slogan-Graphic-Tee-p-10714724-cat-1738.html');

$root  = $client->waitFor("div[class='goods-detailv2__media-inner']");
//дождались загрузки элемента с информацией. дальше парсим другой библой
$html = str_get_html($root->html());

$info = $html->find('div[class="product-intro__info"]',0);

$name = $info->find("h1.product-intro__head-name",0)->innertext();
$id = $info->find('.product-intro__head-sku',0)->innertext();
$id = explode(" ", $id)[2];
$pricing = $info->find("div[class='product-intro__head-price j-expose__product-intro__head-price']",0);

$actual_price_and_discount = $pricing->find("span");
$actual_price = floatval(str_replace('$', '', $actual_price_and_discount[0]->innertext()));
$discount = floatval(str_replace(['%','-'], '', $actual_price_and_discount[1]->innertext()));

$rating = $info->find('.product-intro__head-reviews',0)->find('span[aria-label]',0)->attr["aria-label"];
$rating = floatval(explode(" ", $rating)[2]);

//почему то на сайте сделано так что категории продукта подгружаются после всего, так что придется снова подождать
$type_elems = $client->waitFor("label.selling-point-label__ctn")->html();
$type_elems = str_get_html($type_elems); //дождались, парсим
$type_elems = $info->find('label.selling-point-label__ctn',0);
$types = [];
foreach($type_elems->find('label[class="sui-label-common sui-label__sellpoint"]') as $type_elem){
    array_push($types, $type_elem->innertext());
}

$image = $html->find('img[class="j-verlok-lazy loaded"]',0)->attr["src"];

//доп задание: получаем ссылку
$link = "https://us.shein.com/pdsearch/".$id;
$crawler = $client->request('GET', $link);
//на данном этапе у нас ссылка не на товар а на список товаров с нужным id, нужно тыкнуть на первый
$link  = $client->waitFor('a[class="S-product-item__img-container j-expose__product-item-img"]');
$link =  str_get_html($link->html())->find('a[class="S-product-item__img-container j-expose__product-item-img"]',0)->attr['href'];
$link = "https://us.shein.com/".$link;
$product = array(
    "name" => $name,
    "id" => $id,
    "actual_price" => $actual_price,
    "discount_percent" => $discount,
    "rating" => $rating,
    "categories" => $types,
    "image" => $image,
    "link" => $link
);

foreach($product as $key=>$value) {
    echo $key." :   ".(gettype($value)!="array"? $value : implode(", " , $value))."<br>";
}

$client->close();
$client->quit();
?>