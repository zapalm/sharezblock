<?php
/**
 * Special offer block in the product page: module for PrestaShop 1.4
 *
 * @author zapalm <zapalm@ya.ru>
 * @copyright (c) 2010-2016, zapalm
 * @link http://prestashop.modulez.ru/en/frontend-features/13-special-offer-block-module-for-prestashop.html The module's homepage
 * @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_'))
	exit;

/**
 * @version v1.3.0 alpha - development version!
 */
class SharezBlock extends Module
{
	/** товар с акцией один на любое количество основного товара */
	const QTY_RESTRICT_ONE = 0;

	/** количество товара с акцией должно быть пропорционально количеству основного товара */
	const QTY_RESTRICT_PROPORTIONAL = 1;

	/** любое количество товара с акцией */
	const QTY_RESTRICT_MANY = 2;

	/** @var array возможные ограничения по количеству акционного товара */
	public $quantity_prop;

	public function __construct()
	{
		$this->name = 'sharezblock';
		$this->tab = 'pricing_promotion';
		$this->version = '1.3.0';
		$this->author = 'zapalm';
		$this->need_instance = 0;
		$this->bootstrap = false;

		parent::__construct();

		$this->displayName = $this->l('Special offer block in the product page');
		$this->description = $this->l('It shows a special offer block on product page to enable cross selling.');

		$this->quantity_prop = array(
			self::QTY_RESTRICT_ONE          => $this->l('Only one'),
			self::QTY_RESTRICT_PROPORTIONAL => $this->l('Proportional'),
			self::QTY_RESTRICT_MANY         => $this->l('Many'),
		);

		global $cookie;
		$this->iso_code = Language::getIsoById((int)$cookie->id_lang);
		$this->defaultCurrency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
	}

	public function install()
	{
		return parent::install()
			&& $this->registerHook('extraRight')
			&& $this->registerHook('header')
			&& $this->instllDb();
	}

	public function uninstall()
	{
		$sql = 'drop table `'._DB_PREFIX_.'share_product`';
		$res = Db::getInstance()->Execute($sql);

		return parent::uninstall() && $res;
	}

	private function instllDb()
	{
		$sql = '
			CREATE TABLE `'._DB_PREFIX_.'share_product` (
			`id_product` int(10) unsigned NOT NULL,
			`id_share_product` int(10) unsigned NOT NULL,
			`days` tinyint(3) unsigned NOT NULL,
			`sep` tinyint(1) unsigned NOT NULL,
			`prop` tinyint(1) unsigned NOT NULL,
			`date` date NOT NULL,
			`price` decimal(20,6) NOT NULL default 0.0,
			`descr` tinyint(1) unsigned NOT NULL,
			PRIMARY KEY  (`id_product`,`id_share_product`)
			) ENGINE='._MYSQL_ENGINE_;

		return Db::getInstance()->Execute($sql);
	}

	public function getContent()
	{
		global $cookie;

		$html = '<h2>'.$this->displayName.'</h2>';

		if (isset($_POST['submit_settings']))
		{
			if ((int)Tools::getValue('product_id') == (int)Tools::getValue('product_share_id'))
				$html .= $this->displayError($this->l('Product and share product are the same.'));
			else
			{
				$res = self::getShareProductRules((int)Tools::getValue('product_id'));
				if ($res)
				{
					$res = self::updateShareProductRules(Tools::getValue('product_id'), Tools::getValue('product_share_id'), Tools::getValue('share_days'), Tools::getvalue('SHAREZBLOCK_SEP'), Tools::getvalue('SHAREZBLOCK_PROP'), Tools::getvalue('SHAREZBLOCK_PRICE'), Tools::getvalue('SHAREZBLOCK_DESCR'));

					$html .= $res ? $this->displayConfirmation($this->l('Settings updated.')) : $this->displayError($this->l('Settings not updated.'));
				}
				else
					$html .= $this->displayError($this->l('Supplement products are not set.'));
			}
		}

		if (Tools::isSubmit('submit_bind'))
		{
			if ((int)Tools::getValue('product_id') == (int)Tools::getValue('product_share_id'))
				$html .= $this->displayError($this->l('Product and share product are the same.'));
			else
			{
				$res = self::getShareProductRules((int)Tools::getValue('product_id'));
				if ($res)
					$res = self::updateShareProductRules((int)Tools::getValue('product_id'), (int)Tools::getValue('product_share_id'), (int)Tools::getValue('share_days'));
				else
					$res = self::bindProducts((int)Tools::getValue('product_id'), (int)Tools::getValue('product_share_id'), (int)Tools::getValue('share_days'));
			}

			$html .= $res ? $this->displayConfirmation($this->l('Settings updated.')) : $this->displayError($this->l('Settings not updated.'));
		}
		elseif (Tools::isSubmit('submit_unbind'))
		{
			$sql = 'DELETE FROM `'._DB_PREFIX_.'share_product` WHERE id_product='.(int)Tools::getValue('product_id').' AND id_share_product='.(int)Tools::getValue('product_share_id');
			$res = Db::getInstance()->Execute($sql);
			$html .= $res ? $this->displayConfirmation($this->l('Settings updated.')) : $this->displayError($this->l('Settings not updated.'));
		}

		if (Tools::isSubmit('products_cat') || Tools::isSubmit('products_share_cat'))
		{
			$product_cats = Product::getProducts((int)$cookie->id_lang, null, null, 'name', 'ASC', (int)Tools::getValue('products_cat'));
			$product_share_cats = Product::getProducts((int)$cookie->id_lang, null, null, 'name', 'ASC', (int)Tools::getValue('products_share_cat'));
		}
		else
		{
			$product_cats = array(0 => array('id_product' => 0, 'name' => $this->l('No products in this category')));
			$product_share_cats = array(0 => array('id_product' => 0, 'name' => $this->l('No products in this category')));
		}

		$html .= '
			<script type="text/javascript">
			// <![CDATA[
				var sep_chk=false;
				var price_chk=false;
				function handleProductSelection(id_product)
				{
					$.ajax({
					  url: "'.$this->_path.'controllers/admin/settings.php",
					  cache: false,
					  data: "select_field=1&id_product="+id_product,
					  success: function(html){
						var a = html.split("_");
 						$("#product_share_id option[value="+a[0]+"]").attr("selected", "selected");
 						$("#product_share_id option[value!="+a[0]+"]").removeAttr("selected");
 						$("#share_days").val(a[1]);
 						$("#date").html(a[2]);
						$("#SHAREZBLOCK_PROP").val(a[4]);

 						$("#SHAREZBLOCK_SEP").attr("checked", (parseInt(a[3])==1?"checked":""));

						$("#SHAREZBLOCK_PRICE").val(a[5]);
						$("#SHAREZBLOCK_PRICE_STAT").attr("checked", (parseFloat(a[5])>0?"checked":""));

						$("#SHAREZBLOCK_DESCR").attr("checked", (parseInt(a[6])==1?"checked":""));
					  }
					});
				}
			//]]>
			</script>
			<div>
			<fieldset style="width: 868px">
				<legend><img src="'.$this->_path.'logo.gif" alt="" title="" />'.$this->l('Supplement products').'</legend>
					<form action="'.$_SERVER['REQUEST_URI'].'" method="post" name="fm_submit">
						<b>'.$this->l('Days number of the special offer:').'</b>
						<input type="text" id="share_days" name="share_days" value="'.(Tools::getValue('share_days') ? Tools::getValue('share_days') : 14).'" size="3">
						<span style="color:#7F7F7F; font-size:0.85em; font-weight: bold">('.$this->l('start at:').' <span id="date"></span>)</span><br/><br/>
						<b>'.$this->l('Select product from left column and bind it with special offer product from right column by clicking to the Bind button. The same should be done to unbind.').'</b><br/><br/>
						<div style="float: left;">
							'.$this->l('Product category:').'
							<select name="products_cat" onchange="document.forms.fm_submit.submit()">';
								$cats = Category::getSimpleCategories((int)$cookie->id_lang);
								foreach ($cats as $k => $c)
									$html .= '<option value="'.$c['id_category'].'" '.(Tools::getValue('products_cat') == $c['id_category'] ? 'selected="selected"' : '').'>'.$c['name'].'</option>';

								$html .= '
							</select>
							<br/><br/>
							'.$this->l('Products').'<br/>
							<select name="product_id" size="30">';
								foreach ($product_cats as $k => $p)
									$html .= '<option value="'.$p['id_product'].'" onclick="handleProductSelection(\''.$p['id_product'].'\')">'.Tools::truncate($p['name'], 63).'</option>';

								$html .= '
							</select>
						</div>
						<div style="float: left; margin-left: 10px">
							'.$this->l('Special offer product category:').'
							<select name="products_share_cat" onchange="document.forms.fm_submit.submit()">';
								foreach ($cats as $k => $c)
									$html .= '<option value="'.$c['id_category'].'" '.(Tools::getValue('products_share_cat') == $c['id_category'] ? 'selected="selected"' : '').'>'.$c['name'].'</option>';

								$html .= '
							</select>
							<br/><br/>
							'.$this->l('Special offer products').'<br/>
							<select id="product_share_id" name="product_share_id" size="30">';
								foreach ($product_share_cats as $k => $p)
									$html .= '<option value="'.$p['id_product'].'">'.Tools::truncate($p['name'], 63).'</option>';

								$html .= '
							</select>
						</div>
						<br class="clear"><br class="clear">
						<center>
							<input type="submit" name="submit_bind" value="'.$this->l(' Bind ').'" class="button" />
							<input type="submit" name="submit_unbind" value="'.$this->l(' Unbind ').'" class="button" />
						</center>
						<br class="clear"/><br class="clear"/>

						<hr/>
						<label>'.$this->l('Show product description').'</label>
						<div class="margin-form">
							<input type="checkbox" name="SHAREZBLOCK_DESCR" id="SHAREZBLOCK_DESCR" value="1">
						</div><br/>
						<div id="sep_option">
							<label>'.$this->l('Add products to the cart separately').' (1)</label>
							<div class="margin-form">
								<input type="checkbox" id="SHAREZBLOCK_SEP" name="SHAREZBLOCK_SEP" value="1" onclick="$(\'#SHAREZBLOCK_PRICE_STAT\').attr(\'checked\', \'\')">
								&nbsp;'.$this->l('(Allows to buy a special product separately from a basic product. This option will cancel all another options.)').'
							</div>
						</div>
						<br class="clear">

						<div id="quantity_man">
							<label>'.$this->l('Quantity management').' (2)</label>
							<div class="margin-form">
								<select name="SHAREZBLOCK_PROP" id="SHAREZBLOCK_PROP">';
									foreach ($this->quantity_prop as $k => $v)
										$html .= '<option value="'.$k.'" onclick="$(\'#SHAREZBLOCK_PRICE_STAT\').attr(\'checked\', \'\'); $(\'#SHAREZBLOCK_SEP\').attr(\'checked\', \'\'); $(\'#SHAREZBLOCK_PRICE\').val(\'0\');">'.$v.'</option>';

									$html .= '
								</select>
								<br/>'.$this->l('(1. Only one</b> - allows to buy only one special product. 2. Proportional - amount of basic and special products should be proportional in the cart. 3. Many - a customer can buy any number of a special product. Quantity management option will cancel all another options.)').'
							</div>
						</div>
						<div id="price_params">
							<label>'.$this->l('Customer get product for free if he have cart total price more or equal some value:').' (3)</label>
							<div class="margin-form">
								<input type="checkbox" id="SHAREZBLOCK_PRICE_STAT" name="SHAREZBLOCK_PRICE_STAT" value="1"  onclick="$(\'#SHAREZBLOCK_SEP\').attr(\'checked\', \'\'); $(\'#SHAREZBLOCK_PRICE\').val(\'0\');">
								'.$this->l('(Uncheck it to disable. This option will cancel all another options.)').'
							</div>
							<div id="price">
								<div class="margin-form">
									<input type="text" id="SHAREZBLOCK_PRICE" name="SHAREZBLOCK_PRICE" value="0" size="10"> '.$this->defaultCurrency->sign.'
									<br/>'.$this->l('(Price for free.)').'
								</div>
							</div>
						</div>
						<br/><br/>
						<center><input type="submit" name="submit_settings" value="'.$this->l('Save').'" class="button" /></center>
					</form>
			</fieldset>
			</div>
			<br class="clear"><br class="clear">
		';

		return $html;
	}

	public function hookHeader()
	{
		Tools::addCSS($this->_path.'sharezblock.css', 'all');
	}

	public function hookExtraRight($params)
	{
		global $smarty, $cookie, $cart, $currency;

		$conf = array();
		$res = self::getShareProductInfo((int)Tools::getValue('id_product'), (int)$cookie->id_lang);
		if ($res)
		{
			$res['id_image'] = $res['id_share_product'].'-'.$res['id_image'];
			$res['price'] = Tools::displayPrice(Product::getPriceStatic((int)$res['id_share_product']), $currency, false, false);

			$p = self::getShareProductRules((int)Tools::getValue('id_product'));
			$conf['SHAREZBLOCK_SEP'] = $p['sep'];
			$conf['SHAREZBLOCK_PROP'] = $p['prop'];
			$conf['SHAREZBLOCK_DESCR'] = $p['descr'];

			if (floatval($p['price']) > 0)
			{
				$conf['SHAREZBLOCK_PRICE'] = $p['price'];
				$conf['SHAREZBLOCK_PRICE_FORMATED'] = Tools::displayPrice(Tools::convertPrice($p['price'], $currency), $currency, false, false);
				$conf['SHAREZBLOCK_PRICE_STAT'] = 1;
			}
			else
				$conf['SHAREZBLOCK_PRICE'] = $conf['SHAREZBLOCK_PRICE_STAT'] = $conf['SHAREZBLOCK_PRICE_FORMATED'] = 0;

			$smarty->assign(array(
				'share_product' => $res,
				'id_cart' => $cart->id,
				'id_lang' => $cart->id_lang,
				'theme_img_dir' => _THEME_IMG_DIR_,
				'm_dir' => $this->_path,
				'conf' => $conf,
				'iso_code' => strtoupper($this->iso_code)
			));
		}

		return $this->display(__FILE__, 'sharezblock.tpl');
	}

	public function hookProductFooter($params)
	{
		return $this->hookExtraRight($params);
	}

	/**
	 * найти количество указанного товара в массиве товаров
	 *
	 * @param array $products
	 * @param int   $id_product
	 *
	 * @return int вернет количество товара или 0, если товар не найден
	 */
	public static function findProductInProductsArray($products, $id_product)
	{
		foreach ($products as $p)
			if ($p['id_product'] == $id_product)
				return $p['cart_quantity'];

		return 0;
	}

	/**
	 * получить информацию об аукционном товаре с правилами акции по id основного товара, связанного с ним
	 *
	 * @param int $id_product
	 *
	 * @return array|false вернет массив с данными об акционном товаре или false, если акционный товар отсутствует
	 */
	public static function getShareProductRules($id_product)
	{
		$sql = '
			SELECT
				sp.`id_share_product`,
				sp.`days`,
				sp.`date`,
				sp.`sep`,
				sp.`prop`,
				sp.`price`,
				sp.`descr`
			FROM
				`'._DB_PREFIX_.'share_product` sp
			WHERE sp.`id_product`='.(int)$id_product;

		$res = Db::getInstance()->ExecuteS($sql);

		return (count($res) ? $res[0] : false);
	}

	/**
	 * обновить информацию об аукционном товаре, привязанного к указанному основному товару
	 *
	 * @param int         $product_id
	 * @param int         $product_share_id
	 * @param int         $share_days
	 * @param int|null    $sep
	 * @param int|null    $prop
	 * @param float|null  $price
	 * @param string|null $descr
	 *
	 * @return bool вернет true при успешной операции, иначе - false
	 */
	public static function updateShareProductRules($product_id, $product_share_id, $share_days, $sep = null, $prop = null, $price = null, $descr = null)
	{
		$sql = '
			UPDATE
				`'._DB_PREFIX_.'share_product`
			SET
				`id_product` ='.(int)$product_id.',
				`id_share_product` ='.(int)$product_share_id.',
				`days`='.(int)$share_days.',
				`sep`='.(int)$sep.',
				`prop`='.(int)$prop.',
				`date`="'.date("Y-m-d").'",
				`price`='.(float)$price.',
				`descr`='.(int)$descr.'
			WHERE `id_product`='.(int)$product_id;

		return Db::getInstance()->Execute($sql);
	}

	/**
	 * связать основной товар с аукционным товаром
	 *
	 * @param int $id_product
	 * @param int $id_share_product
	 * @param int $days
	 *
	 * @return bool вернет true при успешной операции, иначе - false
	 */
	public static function bindProducts($id_product, $id_share_product, $days)
	{
		$sql = '
			INSERT INTO `'._DB_PREFIX_.'share_product` (
				`id_product`,
				`id_share_product`,
				`days`,
				`date`
			)
			VALUES ('.
				(int)$id_product.','.
				(int)$id_share_product.','.
				(int)$days.','.
				'"'.date("Y-m-d").'"
			)';

		return Db::getInstance()->Execute($sql);
	}

	/**
	 * получить информацию об аукционном товаре без правил аукциона по id основного товара, связанного с ним
	 *
	 * @param int $id_product
	 * @param int $id_lang
	 *
	 * @return array|bool вернет массив с данными об акционном товаре или false, если акционный товар отсутствует
	 */
	public static function getShareProductInfo($id_product, $id_lang)
	{
		$sql = '
			SELECT
				ph.`id_share_product`,
				ph.`id_product` as `id_sal_product`,
				ph.`days`,
				ph.`date`,
				p.`price`,
				pl.`name`,
				pl.`description_short`,
				pl.link_rewrite,
				i.id_image
			FROM
				`'._DB_PREFIX_.'share_product` as ph
			LEFT JOIN `'._DB_PREFIX_.'product` p ON (p.`id_product`= ph.`id_share_product`)
			LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (p.`id_product` = pl.`id_product` AND pl.`id_lang` ='.(int)$id_lang.')
			LEFT JOIN `'._DB_PREFIX_.'image` i ON (p.`id_product`= i.`id_product`)
			LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (i.`id_image`= il.`id_image` AND il.`id_lang`='.(int)$id_lang.')
			WHERE ph.`id_product` ='.(int)$id_product.' AND i.`cover`=1';

		$res = Db::getInstance()->ExecuteS($sql);

		return (count($res) ? $res[0] : false);
	}

	/**
	 * получить id основного товара, связанного с указанным аукционным товаром
	 *
	 * @param int $id_share_product
	 * 
	 * @return int|bool вернет id основного товара или false
	 */
	public static function isProductBindedWithShareProduct($id_share_product)
	{
		$sql = 'SELECT `id_product` FROM `'._DB_PREFIX_.'share_product` WHERE `id_share_product`='.(int)$id_share_product;
		$res = Db::getInstance()->ExecuteS($sql);

		return (count($res) ? $res[0] : false);
	}
}