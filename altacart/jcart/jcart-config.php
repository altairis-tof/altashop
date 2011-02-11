<?php

// JCART v1.1
// http://conceptlogic.com/jcart/

///////////////////////////////////////////////////////////////////////
// REQUIRED SETTINGS

// THE HTML NAME ATTRIBUTES USED IN YOUR ADD-TO-CART FORM
$jcart['item_id']		= 'my-item-id';			// ITEM ID
$jcart['item_name']		= 'my-item-name';		// ITEM NAME
$jcart['item_article']		= 'my-item-article';		// ITEM ARTICLE
$jcart['item_price']	= 'my-item-price';		// ITEM PRICE
$jcart['item_tax']	= 'my-item-tax';		// ITEM TAX RATE
$jcart['item_qty']		= 'my-item-qty';		// ITEM QTY
$jcart['item_shop']		= 'my-item-shop';		// ITEM SHOP
$jcart['item_add']		= 'my-add-button';		// ADD-TO-CART BUTTON

// PATH TO THE DIRECTORY CONTAINING JCART FILES
$jcart['path'] = '/altashop/altacart/jcart/';

// THE PATH AND FILENAME WHERE SHOPPING CART CONTENTS SHOULD BE POSTED WHEN A VISITOR CLICKS THE CHECKOUT BUTTON
// USED AS THE ACTION ATTRIBUTE FOR THE SHOPPING CART FORM
$jcart['form_action']	= '/checkout.php';

// YOUR PAYPAL SECURE MERCHANT ACCOUNT ID
$jcart['paypal_id']		= 'contact@altashop.org';
$jcart['paypal_cancel'] = 'http://localhost/altashop/go.php?id=checkout';
$jcart['paypal_return'] = 'http://localhost/altashop/thanks.php';
$jcart['paypal_notify'] = 'http://localhost/altashop/notify.php';

///////////////////////////////////////////////////////////////////////
// OPTIONAL SETTINGS

// OVERRIDE DEFAULT CART TEXT
$jcart['text']['cart_title']				= '';		// Shopping Cart
$jcart['text']['single_item']				= '';		// Item
$jcart['text']['multiple_items']			= '';		// Items
$jcart['text']['currency_symbol']			= '';		// $
$jcart['text']['subtotal']					= '';		// Subtotal

$jcart['text']['update_button']				= '';		// update
$jcart['text']['checkout_button']			= '';		// checkout
$jcart['text']['checkout_yacs_button']	= '';		// Checkout with PayPal
$jcart['text']['checkout_paypal_button']	= '';		// Checkout with PayPal
$jcart['text']['remove_link']				= '';		// remove
$jcart['text']['empty_button']				= '';		// empty
$jcart['text']['empty_message']				= '';		// Your cart is empty!
$jcart['text']['item_added_message']		= '';		// Item added!

$jcart['text']['price_error']				= '';		// Invalid price format!
$jcart['text']['quantity_error']			= '';		// Item quantities must be whole numbers!
$jcart['text']['checkout_error']			='';		// Your order could not be processed!

// OVERRIDE THE DEFAULT BUTTONS WITH YOUR IMAGES BY SETTING THE PATH FOR EACH IMAGE
//$jcart['button']['checkout']				= 'https://www.paypal.com/fr_FR/FR/i/btn/btn_paynowCC_LG.gif';
//$jcart['button']['paypal_checkout']			= 'https://www.paypal.com/fr_FR/FR/i/btn/btn_paynowCC_LG.gif';
$jcart['button']['update']					= '';
$jcart['button']['empty']					= '';

?>
