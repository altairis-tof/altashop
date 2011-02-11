<?php

function valid_order($order_id) {
	global $context;

  // definitions des locales
  setlocale(LC_TIME, "fr_FR");
  
  // maj status commande : 2=payée; 1=non
  $q = "update " . SQL::table_name('orders') . " set status = 2 where id = ".$order_id;

  // recup commande
  $orders_query = "select * from " . SQL::table_name('orders') . " o LEFT JOIN " . SQL::table_name('users') . " u ON u.id = o.user_id where o.id=".$order_id;
  $order = SQL::query_first($orders_query);
  $overlay = unserialize($order['overlay']);

  // envoi des mails
  $from = 'contact@gresivaudan.org';
  $message = $message_entete = "
              Gresivaudan.org
              ------------------------------------------------------
              Numéro de commande : ".$order_id."
              ".strftime('%A %d %B %Y à %H:%M:%S', strtotime($order['order_date']))."
              Code client : ".$order['user_id']."\n
              Produits
              ------------------------------------------------------";
  $orders_det_query = "SELECT ol.shop_id, s.title as shop_title, s.overlay as boutique_overlay, a.title, ol.quantity , ol.pu_ht from " . SQL::table_name('orders_lines') . " ol LEFT JOIN ". SQL::table_name('sections')." s ON s.id = ol.shop_id LEFT JOIN " . SQL::table_name('articles') . " a ON a.id = ol.product_id where ol.order_id = ".$order_id ." order by shop_id";
  $orders_det = SQL::query($orders_det_query);
  
  $shop_id = null;

	while($order_det = SQL::fetch($orders_det))
  {
    if (!$shop_id || $shop_id != $order_det['shop_id']) {
      if ($shop_id) {
        // on envoit ici un mail à la boutique pour le prévenir de la commande
        $overlay = unserialize($order_det['boutique_overlay']);
        $email_boutique = $overlay['email'];
        $message_boutique = $message_entete;
        $message_boutique .= "
              ------------------------------------------------------
              Sous-Total: ".str_replace('.', ',', $order['total_ht'])."EUR";
        $message_boutique .= "
              TVA : ".str_replace('.', ',', $order['tva'])."EUR";

        $message_boutique .= "
              Total: ".str_replace('.', ',', $order['total_ttc'])."EUR\n

  La commande est à retirer dans les magasins.
";

        // envoi au magasin
        $subject = 'Une commande a été enregistrée sur gresivaudan.org';
        Mailer::post($from, $email_boutique, utf8_encode($subject), utf8_encode($message_boutique));

        //
        $message .= "
              ------------------------------------------------------
        ";
      }
      $message .= "
              Boutique : ".$order_det['shop_title'];
      $shop_id = $order_det['shop_id'];
      $total_ttc_boutique = $total_ht_boutique = $total_tva_boutique = 0;
    }
    
    // mise à jour qté disponible
    $overlay = unserialize($order_det['overlay']);
    if (isset($overlay['available_qty']) && $overlay['available_qty'] > 0) {
      $overlay['available_qty'] = $overlay['available_qty'] - $order_det['quantity'];
      $q = "UPDATE ".SQL::table_name('articles')." SET overlay='".serialize($overlay)."' WHERE id='".$order_det['product_id']."'";
      SQL::query($q);
    }
    $prix_ht = $order_det['pu_ht']*$order_det['quantity'];
    $message .= "
                ".$order_det['quantity']." x ".$order_det['title']." = ".str_replace('.', ',', $total_ht)."EUR";
    $total_ht_boutique += $prix_ht;
    $total_tva_boutique += $order_det['tax'];
    $total_ttc_boutique += $order_det['tax'] + $prix_ht;
  }
  
  if ($shop_id) {
    // on envoit ici un mail à la boutique pour le prévenir de la commande
    $overlay = unserialize($order_det['boutique_overlay']);
    $email_boutique = $overlay['email'];
    $message_boutique = $message_entete;
    $message_boutique .= "
          ------------------------------------------------------
          Sous-Total: ".str_replace('.', ',', $order['total_ht'])."EUR";
    $message_boutique .= "
          TVA : ".str_replace('.', ',', $order['tva'])."EUR";

    $message_boutique .= "
          Total: ".str_replace('.', ',', $order['total_ttc'])."EUR\n

La commande est à retirer dans les magasins.
";

    // envoi au magasin
    $subject = 'Une commande a été enregistrée sur gresivaudan.org';
    Mailer::post($from, $email_boutique, utf8_encode($subject), utf8_encode($message_boutique));

  }

  $message .= "
              ------------------------------------------------------
              Sous-Total: ".str_replace('.', ',', $order['total_ht'])."EUR";
  if ($order['tva'] > 0)
    $message .= "
              TVA : ".str_replace('.', ',', $order['tva'])."EUR";

  $message .= "
              Total: ".str_replace('.', ',', $order['total_ttc'])."EUR\n

  La commande est à retirer dans les magasins.
";
  
  // envoi au client
  $subject = 'Merci pour votre commande sur gresivaudan.org !';
  Mailer::post($from, $order['email'], utf8_encode($subject), utf8_encode($message));
  // envoi à service info
  $subject = 'Une commande a été enregistrée sur gresivaudan.org';
  Mailer::post($from, 'contact@gresivaudan.org', utf8_encode($subject), utf8_encode($message));
  // envoi à altairis pour controle bon fonctionnement (temporaire)
  Mailer::post($from, 'christophe@altairis.fr', utf8_encode($subject), utf8_encode($message));

}

?>
