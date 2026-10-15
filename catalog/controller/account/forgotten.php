<?php
class ControllerAccountForgotten extends Controller {
	private $error = array();

	public function index() {
		if ($this->customer->isLogged()) {
			$this->response->redirect($this->url->link('account/account', '', true));
		}

		$this->load->language('account/forgotten');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('account/customer');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$customer_info = $this->model_account_customer->getCustomerByEmail($this->request->post['email']);

			// for better security use existing validated customer email address instead of the posted one
			$code = token(40);
			$this->model_account_customer->editCode($customer_info['email'], $code);
			$this->sendResetEmail($customer_info['email'], $code);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_forgotten'),
			'href' => $this->url->link('account/forgotten', '', true)
		);

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['action'] = $this->url->link('account/forgotten', '', true);

		$data['back'] = $this->url->link('account/login', '', true);

		if (isset($this->request->post['email'])) {
			$data['email'] = $this->request->post['email'];
		} else {
			$data['email'] = '';
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/forgotten', $data));
	}

	public function ajax() {
		$this->load->language('account/forgotten');
		$this->load->model('account/customer');

		$json = array();

		if ($this->customer->isLogged()) {
			$json['error']['warning'] = $this->language->get('error_logged');
		} elseif ($this->request->server['REQUEST_METHOD'] != 'POST' || !isset($this->request->post['email'])) {
			$json['error']['warning'] = $this->language->get('error_email');
		} elseif ($this->validate()) {
			$customer_info = $this->model_account_customer->getCustomerByEmail(trim($this->request->post['email']));
			$code = token(40);
			$this->model_account_customer->editCode($customer_info['email'], $code);
			$this->sendResetEmail($customer_info['email'], $code);
			$json['success'] = $this->language->get('text_success');
		} else {
			$json['error']['warning'] = isset($this->error['warning']) ? $this->error['warning'] : $this->language->get('error_email');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function sendResetEmail($email, $code) {
		$this->load->language('mail/forgotten');

		$data['text_greeting'] = sprintf($this->language->get('text_greeting'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
		$data['text_change'] = $this->language->get('text_change');
		$data['text_ip'] = $this->language->get('text_ip');
		$data['reset'] = str_replace('&amp;', '&', $this->url->link('account/reset', 'code=' . $code, true));
		$data['ip'] = isset($this->request->server['REMOTE_ADDR']) ? $this->request->server['REMOTE_ADDR'] : '';

		$mail = new Mail($this->config->get('config_mail_engine'));
		$mail->parameter = $this->config->get('config_mail_parameter');
		$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
		$mail->smtp_username = $this->config->get('config_mail_smtp_username');
		$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
		$mail->smtp_port = $this->config->get('config_mail_smtp_port');
		$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
		$mail->setTo($email);
		$mail->setFrom($this->config->get('config_email'));
		$mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
		$mail->setSubject(html_entity_decode(sprintf($this->language->get('text_subject'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8'));
		$mail->setText($this->load->view('mail/forgotten', $data));
		$mail->send();
	}

	protected function validate() {
		if (!isset($this->request->post['email']) || !filter_var(trim($this->request->post['email']), FILTER_VALIDATE_EMAIL)) {
			$this->error['warning'] = $this->language->get('error_email');
		} elseif (!$this->model_account_customer->getTotalCustomersByEmail(trim($this->request->post['email']))) {
			$this->error['warning'] = $this->language->get('error_email');
		}
		
		// Check if customer has been approved.
		if (!$this->error) {
			$customer_info = $this->model_account_customer->getCustomerByEmail(trim($this->request->post['email']));

			if ($customer_info && !$customer_info['status']) {
				$this->error['warning'] = $this->language->get('error_approved');
			}
		}

		return !$this->error;
	}
}
