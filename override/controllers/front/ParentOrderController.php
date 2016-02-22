<?php

class ParentOrderController extends ParentOrderControllerCore
{
	protected function _assignSummaryInformations()
	{
		parent::_assignSummaryInformations();

		$module_name = 'sharezblock';
		if (Module::isInstalled($module_name) && ($module_instance = Module::getInstanceByName($module_name)) && $module_instance->active)
		{
			global $smarty;

			$products = $smarty->get_template_vars('products');
			foreach($products as &$product)
			{
				if($module_instance::isProductBindedWithShareProduct($product['id_product']))
					$product->isSpecialProduct = 1;
				else
					$product->isSpecialProduct = 0;
			}

			$smarty->assign('products', $products);
		}
	}
}