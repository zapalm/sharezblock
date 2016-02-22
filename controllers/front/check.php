<?php
/**
 * @todo переделать на класс контроллера фронтофиса
 * @todo возвращать json вместо числовых флагов
 */

include_once (dirname(__FILE__).'/../../../../config/config.inc.php');

$module_name = 'sharezblock';
$module_active = false;
if (Module::isInstalled($module_name) && ($module_instance = Module::getInstanceByName($module_name)) && $module_instance->active)
	$module_active = true;

if (!$module_active)
	exit;

// флаг для обозначения проверки правил аукциона при попытки добавить аукционный товар в корзину
$check_share = Tools::getvalue('check_share');
if (!is_numeric($check_share) || !array_key_exists((int)$check_share, $module_instance->quantity_prop))
	exit;

$cart = new Cart((int)Tools::getvalue('id_cart'));
$total = $cart->getOrderTotal(true, $cart::ONLY_PRODUCTS);
$res = $cart->getProducts(true);
$id_share_product = (int)Tools::getvalue('id_share_product');
$id_sal_product = (int)Tools::getvalue('id_sal_product');

if ((int)Tools::getvalue('check_price'))
{
	$price = (float)Tools::getvalue('price');
	if ($total > 0 && $total >= $price)
	{
		if ($module_instance::findProductInProductsArray($res, $id_share_product))
			echo '0';  // товар с акцией уже есть в корзине
		else
			echo '1'; // позволить добавить товар

		exit;
	}
	elseif ($total < $price)
	{
		echo '6'; // не набрана сумма заказа для бесплатного товара
		exit;
	}
}

// есть ли основной товар?
$ret = 2; // основного товара нет в корзине
$sal_quantity = $module_instance::findProductInProductsArray($res, $id_sal_product);
if ($sal_quantity)
	$ret = 1; // есть, значит продолжаем...

// есть ли товар с акцией
if ($ret == 1)
{
	$share_quantity = $module_instance::findProductInProductsArray($res, $id_share_product);
	if ($share_quantity)
		$ret = 0; // есть
}

if ($check_share == $module_instance::QTY_RESTRICT_PROPORTIONAL && $sal_quantity && $share_quantity)
	$ret = $sal_quantity == $share_quantity + 1 ? 3 : 4;
elseif ($check_share == $module_instance::QTY_RESTRICT_MANY && $ret != 2)
	$ret = 5;

// 0 - товар с акцией уже есть в корзине
// 1 - товар с акцией отсутствует в корзине
// 2 - основного товара нет в корзине
// 3 - количество товаров совпадает
// 4 - количество товаров не совпадают
// 5 - можно добавить любое количество товара с акцией
// 6 - не набрана сумма заказа для бесплатного товара
echo $ret;