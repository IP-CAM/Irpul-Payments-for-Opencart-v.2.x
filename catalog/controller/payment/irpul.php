<?php
class ControllerPaymentirpul extends Controller {
    private $WebService = array();
	private $errors = array();
	  
	public function index() {
		$this->load->language('payment/irpul');
        $data['text_wait']		= $this->language->get('text_wait');
		$data['button_confirm'] = $this->language->get('button_confirm');
		
		$data['text_wait'] = $this->language->get('text_wait');
		$data['text_ersal'] = $this->language->get('text_ersal');
		
		$data['continue'] 		= $this->url->link('checkout/success');
		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/irpul.tpl')) {
			return $this->load->view($this->config->get('config_template') . '/template/payment/irpul.tpl', $data);
		} 
		else {
			return $this->load->view('/payment/irpul.tpl', $data);
		}
	}
	
	public function confirm() {
		$this->language->load('payment/irpul');
		
		//$this->load->library('encryption');
		//$encryption = new Encryption($this->config->get('config_encryption'));
		
		$json = array();
		$data = array();
		
		$order_id = isset($this->session->data['order_id']) ? $this->session->data['order_id'] : 0;
		
		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($order_id);
		
		if ($order_info) {
			// -----------------------------------
			$token 	 = $this->config->get('irpul_PIN');

			//$callback_url = urldecode($this->url->link('payment/irpul/callback', 's_order_id=' . $encryption->encrypt($order_id), 'SSL'));
			$callback_url = $this->url->link('payment/irpul/callback');
			$data['orderTotal']   	= $this->currency->format($order_info['total'], $order_info['currency_code'], false, false);
			$data['orderTotal']   	= $this->currency->convert($data['orderTotal'], $order_info['currency_code'], "TOM")*10;
			
			$data['orderComment'] 	= $order_info['comment'];
			
			$products_array = $this->cart->getProducts();
			$products_name 	= '';
			$i 				= 0;
			$count 			= count($products_array);	
			foreach ( $products_array as $product) {
				$products_name .= $product['quantity'] . ' عدد ' . $product['name'];
				if ($i!=$count-1) {	
					$products_name .= ' | ';
				}
				$i++;
			}
				
			$parameters = array(
				'method' 		=> 'payment',
				//'plugin'		=> 'OpenCart',
				//'webgate_id' 	=> $webgate_id,
				'order_id'		=> $order_id,
				'product'		=> $products_name,
				'payer_name'	=> $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'],
				'phone' 		=> $order_info['telephone'],
				'mobile' 		=> '',
				'email' 		=> $order_info['email'],
				'amount' 		=> $data['orderTotal'],
				'callback_url' 	=> $callback_url,
				'address' 		=> $order_info['payment_zone'] . ' ' . $order_info['payment_city'] . ' '.  $order_info['payment_address_1'] .' '. $order_info['payment_address_2'] . ' کد پستی :' . $order_info['payment_postcode'] ,
				'description' 	=> '',
			);
			
			$result = post_data('https://irpul.ir/ws.php', $parameters, $token );

			if( isset($result['http_code']) ){
				$data =  json_decode($result['data'],true);

				if( isset($data['code']) && $data['code'] === 1){
					//ok
					$json['success'] = $data['url'];
				}
				else{
					$json['error'] = $result['res_code'] . ' '. $result['status'];
				}
			}else{
				$json['error'] = "عدم دریافت پاسخ";
			}
			
			// -----------------------------------
			
		} else {
			$json['error'] = $this->language->get('error_order_id');
		}
		$this->response->setOutput(json_encode($json));
	}
	
	function post_data($url,$params,$token) {
		ini_set('default_socket_timeout', 15);

		$headers = array(
			"Authorization: token= {$token}",
			'Content-type: application/json'
		);

		$handle = curl_init($url);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($handle, CURLOPT_TIMEOUT, 40);
		curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($params) );
		curl_setopt($handle, CURLOPT_HTTPHEADER, $headers );

		$response = curl_exec($handle);
		//error_log('curl response1 : '. print_r($response,true));

		$msg='';
		$http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));

		$status= true;

		if ($response === false) {
			$curl_errno = curl_errno($handle);
			$curl_error = curl_error($handle);
			$msg .= "Curl error $curl_errno: $curl_error";
			$status = false;
		}

		curl_close($handle);//dont move uppder than curl_errno

		if( $http_code == 200 ){
			$msg .= "Request was successfull";
		}
		else{
			$status = false;
			if ($http_code == 400) {
				$status = true;
			}
			elseif ($http_code == 401) {
				$msg .= "Invalid access token provided";
			}
			elseif ($http_code == 502) {
				$msg .= "Bad Gateway";
			}
			elseif ($http_code >= 500) {// do not wat to DDOS server if something goes wrong
				sleep(2);
			}
		}

		$res['http_code'] 	= $http_code;
		$res['status'] 		= $status;
		$res['msg'] 		= $msg;
		$res['data'] 		= $response;

		if(!$status){
			//error_log(print_r($res,true));
		}
		return $res;
	}
	
	/*function Get_PaymentVerification($webgate_id , $trans_id , $amount){
		$parameters = array(
			'method' 	    => 'verify',
			'trans_id' 		=> $trans_id,
			'amount'	 	=> $amount,
		);

		
		$stream_context_opts = array(
			'ssl' => array(
				'verify_peer'       => false,
				'verify_peer_name'  => false
			)
		);
		$soap_option = array('soap_version'=>SOAP_1_2, 'cache_wsdl'=>WSDL_CACHE_NONE, 'encoding'=>'UTF-8', 'stream_context'=> stream_context_create($stream_context_opts) );
		
		try {
			$client = new SoapClient( 'https://irpul.ir/webservice.php?wsdl' , $soap_option );
			$result = $client->PaymentVerification($parameters);
		}catch (Exception $e) { echo 'Error'. $e->getMessage();  }
		return $result;
	}*/
	
	public function callback() {
		$this->language->load('payment/irpul');
		
		$this->document->setTitle($this->language->get('text_heading'));
		$data['text_heading'] = $this->language->get('text_heading');

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_heading'),
			'href' => $this->url->link('payment/irpul', '', 'SSL')
		);

		$data['heading_title'] = $this->language->get('heading_title');
			if ($this->customer->isLogged()) {
			$data['text_message'] = sprintf($this->language->get('text_customer'), $this->url->link('account/account', '', 'SSL'), $this->url->link('account/order', '', 'SSL'), $this->url->link('account/download', '', 'SSL'), $this->url->link('information/contact'));
		} else {
			$data['text_message'] = sprintf($this->language->get('text_guest'), $this->url->link('information/contact'));
		}
		
		$data['error_warning'] 	= '';
		$this->load->model('checkout/order');
		
		/*
		$this->load->library('encryption');
		$encryption = new Encryption($this->config->get('config_encryption'));
		$order_id = isset($this->request->get['s_order_id']) ? $encryption->decrypt($this->request->get['s_order_id']) : 0;
		$order_info = $this->model_checkout_order->getOrder($order_id);
		if ($order_info) {
		}
		*/
		
		$trans_id 	= isset($this->request->post['trans_id']) ? $this->request->post['trans_id'] : '';
		$order_id 	= isset($this->request->post['order_id']) ? $this->request->post['order_id'] : '';
		$amount 	= isset($this->request->post['amount']) ? $this->request->post['amount'] : '';
		$refcode 	= isset($this->request->post['refcode']) ? $this->request->post['refcode'] : '';
		$status 	= isset($this->request->post['status']) ? $this->request->post['status'] : '';
			
		//بررسی وجود سفارش
		$order_info = $this->model_checkout_order->getOrder($order_id);
		if ($order_info) {
			$amount = $this->currency->format($order_info['total'], $order_info['currency_code'], false, false);
			$amount = $this->currency->convert($amount, $order_info['currency_code'], "TOM" ) * 10;
			$orderId 	= $order_info['order_id'];
			
			if($status == 'paid'){
				$token = $this->config->get('irpul_PIN');
				
				$parameters = array(
					'method' 	    => 'verify',
					'trans_id' 		=> $trans_id,
					'amount'	 	=> $amount,
				);
				
				$result =  post_data('https://irpul.ir/ws.php', $parameters, $token );
				
				if( isset($result['http_code']) ){
					$data =  json_decode($result['data'],true);

					if( isset($data['code']) && $data['code'] === 1){
						$irpul_amount  = $data['amount'];
						
						if($amount == $irpul_amount){
							$data['trans_id'] = $refcode;
							$this->model_checkout_order->addOrderHistory($order_id, $trans_id,'رسید تراکنش: $refcode ');
							$data['refcode'] = $refcode;
							$data['trans_id'] = $trans_id;
							//break;
						}
						else{
							$data['error_warning']	= 'مبلغ تراکنش در ایرپول (' . number_format($irpul_amount) . ' تومان) تومان با مبلغ تراکنش در سیمانت (' . number_format($amount) . ' تومان) برابر نیست';
						}
					}
					else{
						$data['error_warning']	= 'خطا در پرداخت. کد خطا: ' . $data['code'] . '<br/> ' . $data['status'];
					}
				}else{
					$data['error_warning']	= "پاسخی از سرویس دهنده دریافت نشد. لطفا دوباره تلاش نمائید";
				}
			}
			else{
				$data['error_warning'] = 'فاکتور پرداخت نشده است !';
			}
		}
		else{
			$data['error_warning'] = 'خطا: سفارش مورد نظر یافت نشد!';
		}

		}else{
			$data['error_warning'] = 'توکن ایرپول موجود نیست !';
		}

		if ($data['error_warning']){
			$data['continue'] = $this->url->link('checkout/checkout', '', 'SSL');
			$data['btn_text'] = 'بازگشت به سبد خرید';
		} else {
			//$data['continue'] = $this->url->link('checkout/success', '', 'SSL');
			$data['continue'] = $this->url->link('common/home', '', 'SSL');
			$data['btn_text'] = 'رفتن به صفحه اصلی';
		}
	
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/irpul_confirm.tpl')) {
			$this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/payment/irpul_confirm.tpl', $data));
		} else {
			$this->response->setOutput($this->load->view( '/payment/irpul_confirm.tpl', $data));
		}
	}
}
?>