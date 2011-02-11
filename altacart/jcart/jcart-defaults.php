<?php

// JCART v1.1
// http://conceptlogic.com/jcart/

// DEFAULT CART TEXT USED IF NOT OVERRIDDEN IN jcart-config.php
// DEFAULTS MUST BE AVAILABLE TO jcart.php AND jcart-javascript.php
// INCLUDED AS A SEPARATE FILE TO SIMPLIFY USER CONFIG

if (!$jcart['path']) die('The path to jCart isn\'t set. Please see <strong>jcart-config.php</strong> for more info.');

if (!$jcart['text']['cart_title']) $jcart['text']['cart_title']							= 'Votre panier';
if (!$jcart['text']['single_item']) $jcart['text']['single_item']						= 'produit';
if (!$jcart['text']['multiple_items']) $jcart['text']['multiple_items']					= 'produits';
if (!$jcart['text']['currency_symbol']) $jcart['text']['currency_symbol']				= '&euro;';
if (!$jcart['text']['subtotal']) $jcart['text']['subtotal']								= 'Total';

if (!$jcart['text']['update_button']) $jcart['text']['update_button']					= 'recalculer';
if (!$jcart['text']['checkout_button']) $jcart['text']['checkout_button']				= 'Voir le panier';
if (!$jcart['text']['checkout_yacs_button']) $jcart['text']['checkout_yacs_button']	= 'Commander';
if (!$jcart['text']['checkout_paypal_button']) $jcart['text']['checkout_paypal_button']	= 'Valider la commande';
if (!$jcart['text']['remove_link']) $jcart['text']['remove_link']						= '&nbsp;';
if (!$jcart['text']['empty_button']) $jcart['text']['empty_button']						= 'vide';
if (!$jcart['text']['empty_message']) $jcart['text']['empty_message']					= 'Votre panier est vide.';
if (!$jcart['text']['item_added_message']) $jcart['text']['item_added_message']			= 'Produit ajout&eacute; &agrave; la liste';

if (!$jcart['text']['price_error']) $jcart['text']['price_error']						= 'Le format du prix est invalide !';
if (!$jcart['text']['quantity_error']) $jcart['text']['quantity_error']					= 'Les quantit&eacute;s doivent &ecirc;tre des nombres entiers !';
if (!$jcart['text']['checkout_error']) $jcart['text']['checkout_error']					= 'Votre commande n\a pas pu &ecirc;tre trait&eacute;e !';

?>
