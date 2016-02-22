<?php
/**
 * @todo переделать на класс контроллера бэкофиса
 * @todo возвращать json вместо строки
 */

include_once (dirname(__FILE__).'/../../../../config/config.inc.php');

$module_name = 'sharezblock';
if (Module::isInstalled($module_name) && ($module_instance = Module::getInstanceByName($module_name)) && $module_instance->active)
{
	// обработка нажатия на основной товар в колонке категорий основных товаров (когда происходит связывание),
	// чтобы вернуть информацию об аукционном товаре, который связан с этим нажатым основным товаром
	if ((int)Tools::getValue('select_field') == 1)
	{
		$res = $module_instance::getShareProductRules((int)Tools::getValue('id_product'));
		echo $res['id_share_product'].'_'.$res['days'].'_'.$res['date'].'_'.$res['sep'].'_'.$res['prop'].'_'.$res['price'].'_'.$res['descr'];
	}
}