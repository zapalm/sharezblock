<!-- MODULE Shares block in the product page -->
<script type="text/javascript">
// <![CDATA[
{literal}
	function checkShareProductRules()
	{
		$.ajax({
		  url: {/literal}"{$m_dir}controllers/front/check.php",{literal}
		  cache: false,
		  data: {/literal}"check_share={$conf.SHAREZBLOCK_PROP}&check_price={$conf.SHAREZBLOCK_PRICE_STAT}&price={$conf.SHAREZBLOCK_PRICE}&id_sal_product={$share_product.id_sal_product}&id_share_product={$share_product.id_share_product}&id_cart={$id_cart}&id_lang={$id_lang}", {literal}
		  success: function(html){
			var ret = parseInt(html);

			if(ret==1 || ret==3 || ret==5) {
				window.location = {/literal}"{$base_dir}cart.php?qty=1&id_product={$share_product.id_share_product|intval}&token={$static_token}&add=1"{literal};
			}
			else if (ret==0) {
				$("#add_share_status_msg").html({/literal}"{l s='You have added this item to your cart.' mod='sharezblock'}"{literal});
			}
			else if(ret==2){
				$("#add_share_status_msg").html({/literal}"{l s='You have not added the main product to your cart.' mod='sharezblock'}"{literal});
			}
			else if(ret==4) {
				$("#add_share_status_msg").html({/literal}"{l s='Quantity of product from the page in the cart must be equal quantity of product from the block in the cart.' mod='sharezblock'}"{literal});
			}
			else if(ret==6) {
				$("#add_share_status_msg").html({/literal}"{l s='You will get product for free if you have cart total price more or equal' mod='sharezblock'}"+" <b>{$conf.SHAREZBLOCK_PRICE_FORMATED}</b>"{literal});
			}
		 }
		});
	}
{/literal}
//]]>
</script>

{if $share_product && $conf.SHAREZBLOCK_SEP || $share_product && ($share_product.date - $smarty.now|date_format:'%Y-%m-%d' < $share_product.days)}
	{assign var='share_product_link' value=$link->getProductLink($share_product.id_share_product, $share_product.link_rewrite)}
	<div class="sharez-block">
		<span class="sharez-header">
			{if $conf.SHAREZBLOCK_PRICE_STAT}
				{l s='It may be free' mod='sharezblock'}
			{elseif $conf.SHAREZBLOCK_SEP}
				{l s='Also bought' mod='sharezblock'}
			{else}
				{l s='Special offer' mod='sharezblock'}
			{/if}
		</span>
		<p class="sharez-present">
			{if $conf.SHAREZBLOCK_PRICE_STAT}
				{l s='You will get product for free if you have cart total price more or equal' mod='sharezblock'}
				{$conf.SHAREZBLOCK_PRICE_FORMATED}
			{elseif $conf.SHAREZBLOCK_SEP}
				{l s='Customers who bought this product also bought:' mod='sharezblock'}
			{else}
				{l s='if you buy you will get the product in this block with special price:' mod='sharezblock'}
			{/if}
		</p>
		<center>
			<h5>
				<a href="{$share_product_link}">
					{$share_product.name}
				</a>
			</h5>
			<a href="{$share_product_link}">
				<img src="{$link->getImageLink($share_product.link_rewrite, $share_product.id_image, 'medium')}" />
			</a>
		</center>
		{if $conf.SHAREZBLOCK_DESCR}
			<p>
				<a href="{$share_product_link}">
					{$share_product.description_short|strip_tags:'UTF-8'}
				</a>
			</p>
		{/if}
		<br/>
		<center>
			{if $conf.SHAREZBLOCK_SEP}
				<div class="ajax_block_product">
					<a rel="ajax_id_product_{$share_product.id_share_product|intval}" class="exclusive ajax_add_to_cart_button" href="{$base_dir}cart.php?qty=1&id_product={$share_product.id_share_product|intval}&token={$static_token}&add=1" title="{l s='Add to cart' mod='sharezblock'}">
						{l s='Add to cart' mod='sharezblock'}
					</a>
				</div>
			{else}
				<input type="button" value="{l s='Add to cart' mod='sharezblock'}" name="share_bt" class="exclusive" onclick="checkShareProductRules();"/>
			{/if}
		</center>
		{if !$conf.SHAREZBLOCK_SEP}
			<p class="share-end">
				{l s='special offer period ends in:' mod='sharezblock'}
			</p>
			<p class="sharez-days">
				{$share_product.days} {l s='days' mod='sharezblock'}
			</p>
		{/if}
		<p class="price">
			{l s='Price:' mod='sharezblock'} {$share_product.price}
		</p>
		{if !$conf.SHAREZBLOCK_SEP}
			<p>
				<span style="color:red" id="add_share_status_msg"></span>
			</p>
		{/if}
		<div class="clear"></div>
	</div>
	<div class="clear"></div>
{/if}
<!-- /MODULE Shares block in the product page -->