<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/platron.php');
require_once('PG_Signature.php');
require_once('OfdReceiptItem.php');
require_once('OfdReceiptRequest.php');

$arrRequest = $_POST;
$secure_cart = explode('_', $arrRequest['pg_order_id']);
$cart = new Cart((int)($secure_cart[0]));
$customer = new Customer((int)$cart->id_customer);

$context = Context::getContext();
$context->cart = new Cart($secure_cart[0]);

$platron = new platron();
    $response   = $platron->createQuery('init_payment.php', $arrRequest);
    $responseElement = new SimpleXMLElement($response);        

    $checkResponse   = PG_Signature::checkXML('init_payment.php', $responseElement, $platron->pl_secret_key);
    $redirectUrl     = (string) $responseElement->pg_redirect_url;

    $response = $platron->checkResponseFromCreateTransaction($checkResponse,$responseElement);

if(!$response){
	$platron->validateOrder((int)($secure_cart[0]), Configuration::get('PS_OS_ERROR'), 0, $platron->displayName, 'Wrong signature', array(), NULL, false, $customer->secure_key);
	Tools::redirect($arrRequest['pg_failure_url']);
}
else {
    $paymentId  = (string) $responseElement->pg_payment_id;
    // создание чека 
    if ($platron->isCreateOfdCheck([])) {
       $orderItems = $platron->createItemsOfOrderByCheck($cart);

       $ofdReceiptRequest = new OfdReceiptRequest($platron->pl_merchant_id, $paymentId);
       $ofdReceiptRequest->setItems($orderItems);
       $ofdReceiptRequest->createParamSign($platron->pl_secret_key);

       $responseOfd = $platron->createQuery($ofdReceiptRequest->getAction(), $ofdReceiptRequest->getParams());
       $responseElementOfd = new SimpleXMLElement($responseOfd);
       if ((string) $responseElementOfd->pg_status != 'ok') {
           	$platron->validateOrder((int)($secure_cart[0]), Configuration::get('PS_OS_ERROR'), 0, $platron->displayName, 'Wrong signature', array(), NULL, false, $customer->secure_key);
			Tools::redirect($arrRequest['pg_failure_url']);
       }
   }
	$platron->validateOrder((int)($secure_cart[0]), Configuration::get('PS_OS_BANKWIRE'), (float)($arrRequest['pg_amount']), $platron->displayName, null, array(), NULL, false, $customer->secure_key);
    header("Location: ".$redirectUrl);
}