{*
* 2007-2017 PrestaShop
*

* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*	@author PrestaShop SA <contact@prestashop.com>
*	@copyright	2007-2017 PrestaShop SA
*	@license		http://opensource.org/licenses/afl-3.0.php	Academic Free License (AFL 3.0)
*	International Registered Trademark & Property of PrestaShop SA
*}
{if $payment_info == 1}
<div class="">
	<h3>Wallet Payment Transaction</h3>
	<h4>Source: {$transaction_detail_array.source}</h4>
	<h4>Mobile: {$transaction_detail_array.mobile}</h4>
	<h4>Amount: {$transaction_detail_array.amount}</h4>
	<h4>Fee Amount: {$transaction_detail_array.fee_amount}</h4>
	<h4>Date/Time: {$transaction_detail_array.date}</h4>
	<h4>State: {$transaction_detail_array.state}</h4>
	{if $refunded != true}
	<a href="{$path}&action=refund&transaction_id={$transaction_detail_array.idx}#khalti_step_3" onclick="return confirm('Are you sure you want to refund the amount?');" class="btn btn-success">Refund</a>{/if} <a href="{$path}#khalti_step_3" class="btn btn-primary">Close</a>
</div>

{else}
<table class="table table-bordered table-hover" id="transaction_tbl">
	<thead>
	<tr>
		<th>{l s='Source' mod='khalti'}</th>
		<th>{l s='Amount(Rs)' mod='khalti'}</th>
		<th>{l s='Fee(Rs)' mod='khalti'}</th>
		<th>{l s='Date' mod='khalti'}</th>
		<th>{l s='Type' mod='khalti'}</th>
		<th>{l s='State' mod='khalti'}</th>
		<th>{l s='#' mod='khalti'}</th>
	</tr>
</thead>
<tbody>
		{foreach from=$transaction key=k item=v}
	<tr>
		<td>{$v.source|escape:'htmlall':'UTF-8'}</td>
		<td>{$v.amount|escape:'htmlall':'UTF-8'}</td>
		<td>{$v.fee|escape:'htmlall':'UTF-8'}</td>
		<td>{$v.date|escape:'htmlall':'UTF-8'}</td>
		<td>{$v.type|escape:'htmlall':'UTF-8'}</td>
		<td>{$v.state|escape:'htmlall':'UTF-8'}</td>
		<td><a href="{$path}&transaction_id={$v.idx}#khalti_step_3" class="btn btn-primary">View</a></td>
	</tr>
	{/foreach}
</tbody>
</table>
{/if}