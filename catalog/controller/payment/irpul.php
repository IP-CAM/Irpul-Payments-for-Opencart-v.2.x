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
			$webgate_id 	 = $this->config->get('irpul_PIN');

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
				'plugin'		=> 'OpenCart',
				'webgate_id' 	=> $webgate_id,
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

			try {
				$client = new SoapClient('https://irpul.ir/webservice.php?wsdl' , array('soap_version'=>'SOAP_1_2','cache_wsdl'=>WSDL_CACHE_NONE ,'encoding'=>'UTF-8'));
				$result = $client->Payment($parameters);
			}catch (Exception $e) { echo 'Error'. $e->getMessage();  }
			
			if (is_numeric($result['res_code']) && $result['res_code']===1 ) {
				$json['success'] = $result['url'];
			} else {
				if($result['res_code']=='-1'){
					$json['error'] = "شناسه درگاه مشخص نشده است";
				}
				elseif($result['res_code']=='-2'){
					$json['error'] = "شناسه درگاه صحیح نمی باشد";
				}
				elseif($result['res_code']=='-3'){
					$json['error'] = "شما حساب کاربری خود را در ایرپول تایید نکرده اید";
				}
				elseif($result['res_code']=='-4'){
					$json['error'] = "مبلغ قابل پرداخت تعیین نشده است";
				}
				elseif($result['res_code']=='-5'){
					$json['error'] = "مبلغ قابل پرداخت صحیح نمی باشد";
				}
				elseif($result['res_code']=='-6'){
					$json['error'] = "شناسه تراکنش صحیح نمی باشد";
				}
				elseif($result['res_code']=='-7'){
					$json['error'] = "آدرس بازگشت مشخص نشده است";
				}
				elseif($result['res_code']=='-8'){
					$json['error'] = "آدرس بازگشت صحیح نمی باشد";
				}
				elseif($result['res_code']=='-9'){
					$json['error'] = "آدرس ایمیل وارد شده صحیح نمی باشد";
				}
				elseif($result['res_code']=='-10'){
					$json['error'] = "شماره تلفن وارد شده صحیح نمی باشد";
				}
				elseif($result['res_code']=='-12'){
					$json['error'] = "نام پلاگین (Plugin) مشخص نشده است";
				}
				elseif($result['res_code']=='-13'){
					$json['error'] = "نام پلاگین (Plugin) صحیح نیست";
				}
				else{
					$json['error'] = $result['res_code'] . ' '. $result['status'];
				}
			}
			// -----------------------------------
			
		} else {
			$json['error'] = $this->language->get('error_order_id');
		}
		$this->response->setOutput(json_encode($json));
	}
	
	
	function url_decrypt($string){
		$counter = 0;
		$data = str_replace(array('-','_','.'),array('+','/','='),$string);
		$mod4 = strlen($data) % 4;
		if ($mod4) {
		$data .= substr('====', $mod4);
		}
		$decrypted = base64_decode($data);
		
		$check = array('tran_id','order_id','amount','refcode','status');
		foreach($check as $str){
			str_replace($str,'',$decrypted,$count);
			if($count > 0){
				$counter++;
			}
		}
		if($counter === 5){
			return array('data'=>$decrypted , 'status'=>true);
		}else{
			return array('data'=>'' , 'status'=>false);
		}
	}
	
	function Get_PaymentVerification($webgate_id , $tran_id , $amount){
		$parameters = array(
			'webgate_id'	=> $webgate_id,
			'tran_id' 		=> $tran_id,
			'amount'	 	=> $amount,
		);
		try {
			$client = new SoapClient('https://irpul.ir/webservice.php?wsdl' , array('soap_version'=>'SOAP_1_2','cache_wsdl'=>WSDL_CACHE_NONE ,'encoding'=>'UTF-8'));
			$result = $client->PaymentVerification($parameters);
		}catch (Exception $e) { echo 'Error'. $e->getMessage();  }
		return $result;
	}
	
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
			
		$irpul_token = isset($this->request->get['irpul_token']) ? $this->request->get['irpul_token'] : '';
		if ( isset($irpul_token) && $irpul_token!='' ) {
			$decrypted 		= $this->url_decrypt($irpul_token);
			if($decrypted['status']){
				parse_str($decrypted['data'], $ir_output);
				$tran_id 	= $ir_output['tran_id'];
				$order_id 	= $ir_output['order_id'];
				$amount 	= $ir_output['amount'];
				$refcode	= $ir_output['refcode'];
				$status 	= $ir_output['status'];
				
				//بررسی وجود سفارش
				$order_info = $this->model_checkout_order->getOrder($order_id);
				if ($order_info) {
					$amount = $this->currency->format($order_info['total'], $order_info['currency_code'], false, false);
					$amount = $this->currency->convert($amount, $order_info['currency_code'], "TOM" ) * 10;
					$orderId 	= $order_info['order_id'];
					if($status == 'paid')	
					{
						$webgate_id = $this->config->get('irpul_PIN');
						$result 	= $this->Get_PaymentVerification($webgate_id,$tran_id , $amount );
						if ($result == 1){
							$data['tran_id'] = $refcode;
							$this->model_checkout_order->addOrderHistory($order_id, $tran_id,'رسید تراکنش: $refcode ');
							$data['refcode'] = $refcode;
							$data['tran_id'] = $tran_id;
							//break;
						}else{
							$data['error_warning'] = 'فاکتور در سایت ایرپول پرداخت شده است اما در این سایت تایید نشد. لطفا این موضوع را به مدیر سایت اطلاع دهید. کد خطا: '. $result;
						}
					}
					else{
						$data['error_warning'] = 'فاکتور پرداخت نشده است !';
					}
				}
				else{
					$data['error_warning'] = 'خطا: سفارش مورد نظر یافت نشد!';
				}
			}
			else {
				$data['error_warning'] = 'خطا: کد وضعیت بازگشتی نادرست است!';
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