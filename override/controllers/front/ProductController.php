<?php

class ProductController extends ProductControllerCore
{
	public function process()
	{
		parent::process();

		$module_name = 'sharezblock';
		if (Module::isInstalled($module_name) && ($module_instance = Module::getInstanceByName($module_name)) && $module_instance->active)
		{
			global $smarty;

			$product = $smarty->get_template_vars('product');
			if($module_instance::isProductBindedWithShareProduct($product->id))
				$product->isSpecialProduct = 1;
			else
				$product->isSpecialProduct = 0;

			$smarty->assign('product', $product);
		}
	}
}