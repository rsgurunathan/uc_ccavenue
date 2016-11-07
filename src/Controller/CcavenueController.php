<?php

namespace Drupal\uc_ccavenue\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_order\Entity\Order;
use Drupal\uc_ccavenue\Plugin\Ubercart\PaymentMethod\CcavenuePayment;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for Ccavenue routes.
 */
class CcavenueController extends ControllerBase {

  /**
   * Handles a complete Ccavenue transaction.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the cart or checkout complete page.
   */
  public function orderComplete(OrderInterface $uc_order) {
    // If the order ID specified in the return URL is not the same as the one in
    // the user's session, we need to assume this is either a spoof or that the
    // user tried to adjust the order on this side while at CCAvenue.
    $session = \Drupal::service('session');
    if (!$session->has('cart_order') || intval($session->get('cart_order')) != $uc_order->id()) {
      drupal_set_message($this->t('Thank you for your order!'));
      return $this->redirect('uc_cart.cart');
    }

    // Ensure the payment method is CCAvenue.
    $method = \Drupal::service('plugin.manager.uc_payment.method')->createFromOrder($uc_order);
    if (!$method instanceof CcavenuePayment) {
      return $this->redirect('uc_cart.cart');
    }

    // This lets us know it's a legitimate access of the complete page.
    $session = \Drupal::service('session');
    $session->set('uc_checkout_complete_' . $uc_order->id(), TRUE);

    return $this->redirect('uc_cart.checkout_complete');
  }

  /**
   * Forms Ccavenue request.
   *
   *   A redirect to CCAvenue site.
   */
  public function ccavenueRequest() {

		$post_params = \Drupal::request()->request->all();

		$session = \Drupal::service('session');

		$order = Order::load($session->get('cart_order'));

		$plugin = \Drupal::service('plugin.manager.uc_payment.method')->createFromOrder($order);

		$config = $plugin->getConfiguration();

		$merchant_data='';
		$working_key=$config['ccavenue_working_key'];
		$access_code=$config['ccavenue_access_code'];

		foreach ($post_params as $key => $value){
			$merchant_data.=$key.'='.$value.'&';
		}
		$merchant_data.='merchant_id='.$config['ccavenue_merchant_id'];
		//var_dump($merchant_data); exit;
		$encrypted_data=$this->encrypt($merchant_data,$working_key); // Method for encrypting the data.

		$data = array('encRequest'=>$encrypted_data, 'ccavenue_access_code'=>$access_code);
		$build['form'] = $this->formBuilder()->getForm('\Drupal\uc_ccavenue\Form\RequestForm', $data);

		$build['#attached']['library'][] = 'uc_ccavenue/uc_ccavenue.ccavenue_request';
		//exit;
		//return $build;
		$html = \Drupal::service('renderer')->render($build);
		$response = new Response();
		$response->setContent($html.'<script>document.getElementById("uc-ccavenue-request-form").submit()</script>');

		return $response;
  }
  
  public function encrypt($plainText,$key)	{
		$secretKey = $this->hextobin(md5($key));
		$initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
		$openMode = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '','cbc', '');
		$blockSize = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, 'cbc');
		$plainPad = $this->pkcs5_pad($plainText, $blockSize);
		if (mcrypt_generic_init($openMode, $secretKey, $initVector) != -1) 
		{
			  $encryptedText = mcrypt_generic($openMode, $plainPad);
				  mcrypt_generic_deinit($openMode);
						
		} 
		return bin2hex($encryptedText);
	}

	public function decrypt($encryptedText,$key)	{
		$secretKey = $this->hextobin(md5($key));
		$initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
		$encryptedText=$this->hextobin($encryptedText);
		$openMode = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '','cbc', '');
		mcrypt_generic_init($openMode, $secretKey, $initVector);
		$decryptedText = mdecrypt_generic($openMode, $encryptedText);
		$decryptedText = rtrim($decryptedText, "\0");
		mcrypt_generic_deinit($openMode);
		return $decryptedText;
		
	}
	//*********** Padding Function *********************

	public function pkcs5_pad ($plainText, $blockSize)	{
		$pad = $blockSize - (strlen($plainText) % $blockSize);
		return $plainText . str_repeat(chr($pad), $pad);
	}

	//********** Hexadecimal to Binary function for php 4.0 version ********

	public function hextobin($hexString) 	{ 
		$length = strlen($hexString); 
		$binString="";   
		$count=0; 
		while($count<$length) 
		{       
			$subString =substr($hexString,$count,2);           
			$packedString = pack("H*",$subString); 
			if ($count==0)
		{
			$binString=$packedString;
		} 
			
		else 
		{
			$binString.=$packedString;
		} 
			
		$count+=2; 
		} 
		return $binString; 
	}
}
