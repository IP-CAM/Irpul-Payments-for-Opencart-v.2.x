<?php
class ControllerPaymentirpul extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('payment/irpul');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('irpul', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_developers'] = $this->language->get('text_developers');

		$data['entry_PIN'] = $this->language->get('entry_PIN');
		$data['entry_order_status'] = $this->language->get('entry_order_status');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');

		$data['help_encryption'] = $this->language->get('help_encryption');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		$data['tab_general'] = $this->language->get('tab_general');
		$data['entry_template'] = $this->language->get('entry_template');
		

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['PIN'])) {
			$data['error_PIN'] = $this->error['PIN'];
		} else {
			$data['error_PIN'] = '';
		}

		//$this->document->breadcrumbs = array();
        $data['breadcrumbs'] = array();
   	$this->document->breadcrumbs[] = array(
       		//'href'      => $this->url->https('common/home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
       		'text'      => $this->language->get('text_home'),
      		'separator' => FALSE
   		);

   		$this->document->breadcrumbs[] = array(
       		//'href'      => $this->url->https('extension/payment'),
			'href'      => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'),
       		'text'      => $this->language->get('text_payment'),
      		'separator' => ' :: '
   		);

   		$this->document->breadcrumbs[] = array(
       		//'href'      => $this->url->https('payment/sb24'),
			'href'      => $this->url->link('payment/irpul', 'token=' . $this->session->data['token'], 'SSL'),
       		'text'      => $this->language->get('heading_title'),
      		'separator' => ' :: '
   		);


			$data['action'] = $this->url->link('payment/irpul', 'token=' . $this->session->data['token'], 'SSL');

		  $data['cancel'] = $this->url->link('extension/irpul', 'token=' . $this->session->data['token'], 'SSL');

		if (isset($this->request->post['irpul_PIN'])) {
			$data['irpul_PIN'] = $this->request->post['irpul_PIN'];
		} else {
			$data['irpul_PIN'] = $this->config->get('irpul_PIN');
		}


		if (isset($this->request->post['irpul_order_status_id'])) {
			$data['irpul_order_status_id'] = $this->request->post['irpul_order_status_id'];
		} else {
			$data['irpul_order_status_id'] = $this->config->get('irpul_order_status_id');
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['irpul_status'])) {
			$data['irpul_status'] = $this->request->post['irpul_status'];
		} else {
			$data['irpul_status'] = $this->config->get('irpul_status');
		}

		if (isset($this->request->post['irpul_sort_order'])) {
			$data['irpul_sort_order'] = $this->request->post['irpul_sort_order'];
		} else {
			$data['irpul_sort_order'] = $this->config->get('irpul_sort_order');
		}
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		//$this->response->setOutput($this->render(TRUE), $this->config->get('config_compression'));
		
		$this->response->setOutput($this->load->view('payment/irpul.tpl', $data));
	}

	private function validate() {

		if (!$this->user->hasPermission('modify', 'payment/irpul')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['irpul_PIN']) {
			$this->error['PIN'] = $this->language->get('error_PIN');
		}


		if (!$this->error) {
			return TRUE;
		} else {
			return FALSE;
		}
	}
}
?>