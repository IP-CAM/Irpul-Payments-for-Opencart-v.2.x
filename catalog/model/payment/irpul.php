<?php
class ModelPaymentirpul extends Model {
  	public function getMethod() {
		$this->load->language('payment/irpul');

		if ($this->config->get('irpul_status')) {
      		  	$status = TRUE;
      	} else {
			$status = FALSE;
		}
		
		$method_data = array();
	
		if ($status) {  
      		$method_data = array( 
			    'terms'      => '',
        		'code'       => 'irpul',
        		'title'      => $this->language->get('text_title'). $this->language->get('img_title'),
				'sort_order' => $this->config->get('irpul_sort_order')
      		);
    	}
   
    	return $method_data;
  	}
}
?>