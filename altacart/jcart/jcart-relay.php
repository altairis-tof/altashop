<?php
// JCART v1.1
// http://conceptlogic.com/jcart/

// THIS FILE TAKES INPUT FROM AJAX REQUESTS VIA JQUERY post AND get METHODS, THEN PASSES DATA TO JCART
// RETURNS UPDATED CART HTML BACK TO SUBMITTING PAGE
/*
// INCLUDE JCART BEFORE SESSION START
include_once 'jcart.php';

// START SESSION
session_start();
*/
include_once('../../shared/global.php');

// INITIALIZE JCART AFTER SESSION START
$cart =& $_SESSION['altaCart'];
if(!is_object($cart)) {

  require_once($context['path_to_root'].'altacart/cart.php');
  $cart = new altaCart();
}

// PROCESS INPUT AND RETURN UPDATED CART HTML
$cart->update();
$rendus = array("line"=> $cart->render('line'),
       "resume"=>$cart->render('resume'),
       "full"=>$cart->render('full,multishop'),
       "action"=>$_REQUEST['action']
       );
echo json_encode($rendus);

?>
