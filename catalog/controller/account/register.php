<?php
class ControllerAccountRegister extends Controller {
	private $error = array();

	public function index() {
		if ($this->customer->isLogged()) {
			$this->response->redirect($this->url->link('account/account', '', true));
		}

		$this->load->language('account/register');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment.min.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/moment/moment-with-locales.min.js');
		$this->document->addScript('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
		$this->document->addStyle('catalog/view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');

		$this->load->model('account/customer');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$customer_id = $this->model_account_customer->addCustomer($this->request->post);

			// Clear any previous login attempts for unregistered accounts.
			$this->model_account_customer->deleteLoginAttempts($this->request->post['email']);

			$this->customer->login($this->request->post['email'], $this->request->post['password']);

			unset($this->session->data['guest']);

			$this->response->redirect($this->url->link('account/success'));
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
			'text' => $this->language->get('text_register'),
			'href' => $this->url->link('account/register', '', true)
		);
		$data['text_account_already'] = sprintf($this->language->get('text_account_already'), $this->url->link('account/login', '', true));

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['firstname'])) {
			$data['error_firstname'] = $this->error['firstname'];
		} else {
			$data['error_firstname'] = '';
		}

		if (isset($this->error['lastname'])) {
			$data['error_lastname'] = $this->error['lastname'];
		} else {
			$data['error_lastname'] = '';
		}

		if (isset($this->error['email'])) {
			$data['error_email'] = $this->error['email'];
		} else {
			$data['error_email'] = '';
		}

		if (isset($this->error['telephone'])) {
			$data['error_telephone'] = $this->error['telephone'];
		} else {
			$data['error_telephone'] = '';
		}

		if (isset($this->error['custom_field'])) {
			$data['error_custom_field'] = $this->error['custom_field'];
		} else {
			$data['error_custom_field'] = array();
		}

		if (isset($this->error['password'])) {
			$data['error_password'] = $this->error['password'];
		} else {
			$data['error_password'] = '';
		}

		if (isset($this->error['confirm'])) {
			$data['error_confirm'] = $this->error['confirm'];
		} else {
			$data['error_confirm'] = '';
		}

		$data['action'] = $this->url->link('account/register', '', true);

		$data['customer_groups'] = array();

		if (is_array($this->config->get('config_customer_group_display'))) {
			$this->load->model('account/customer_group');

			$customer_groups = $this->model_account_customer_group->getCustomerGroups();

			foreach ($customer_groups as $customer_group) {
				if (in_array($customer_group['customer_group_id'], $this->config->get('config_customer_group_display'))) {
					$data['customer_groups'][] = $customer_group;
				}
			}
		}

		if (isset($this->request->post['customer_group_id'])) {
			$data['customer_group_id'] = $this->request->post['customer_group_id'];
		} else {
			$data['customer_group_id'] = $this->config->get('config_customer_group_id');
		}

		if (isset($this->request->post['firstname'])) {
			$data['firstname'] = $this->request->post['firstname'];
		} else {
			$data['firstname'] = '';
		}

		if (isset($this->request->post['lastname'])) {
			$data['lastname'] = $this->request->post['lastname'];
		} else {
			$data['lastname'] = '';
		}

		if (isset($this->request->post['email'])) {
			$data['email'] = $this->request->post['email'];
		} else {
			$data['email'] = '';
		}

		if (isset($this->request->post['telephone'])) {
			$data['telephone'] = $this->request->post['telephone'];
		} else {
			$data['telephone'] = '';
		}

		// Custom Fields
		$data['custom_fields'] = array();
		
		$this->load->model('account/custom_field');
		
		$custom_fields = $this->model_account_custom_field->getCustomFields();
		
		foreach ($custom_fields as $custom_field) {
			if ($custom_field['location'] == 'account') {
				$data['custom_fields'][] = $custom_field;
			}
		}
		
		if (isset($this->request->post['custom_field']['account'])) {
			$data['register_custom_field'] = $this->request->post['custom_field']['account'];
		} else {
			$data['register_custom_field'] = array();
		}

		if (isset($this->request->post['password'])) {
			$data['password'] = $this->request->post['password'];
		} else {
			$data['password'] = '';
		}

		if (isset($this->request->post['confirm'])) {
			$data['confirm'] = $this->request->post['confirm'];
		} else {
			$data['confirm'] = '';
		}

		if (isset($this->request->post['newsletter'])) {
			$data['newsletter'] = $this->request->post['newsletter'];
		} else {
			$data['newsletter'] = '';
		}

		// Captcha
		if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('register', (array)$this->config->get('config_captcha_page'))) {
			$data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha'), $this->error);
		} else {
			$data['captcha'] = '';
		}

		if ($this->config->get('config_account_id')) {
			$this->load->model('catalog/information');

			$information_info = $this->model_catalog_information->getInformation($this->config->get('config_account_id'));

			if ($information_info) {
				$data['text_agree'] = sprintf($this->language->get('text_agree'), $this->url->link('information/information/agree', 'information_id=' . $this->config->get('config_account_id'), true), $information_info['title']);
			} else {
				$data['text_agree'] = '';
			}
		} else {
			$data['text_agree'] = '';
		}

		if (isset($this->request->post['agree'])) {
			$data['agree'] = $this->request->post['agree'];
		} else {
			$data['agree'] = false;
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/register', $data));
	}

	private function validate() {
		if (!empty($this->request->post['firstname'])) {
			if ((utf8_strlen($this->request->post['firstname']) < 1) || (utf8_strlen($this->request->post['firstname']) > 32)) {
				$this->error['firstname'] = $this->language->get('error_firstname');
			}
		} else {
			$this->error['firstname'] = $this->language->get('error_firstname');
		}

		if (!empty($this->request->post['lastname'])) {
			if ((utf8_strlen($this->request->post['lastname']) < 1) || (utf8_strlen($this->request->post['lastname']) > 32)) {
				$this->error['lastname'] = $this->language->get('error_lastname');
			}
		} else {
			$this->error['lastname'] = $this->language->get('error_lastname');
		}

		if (!empty($this->request->post['email'])) {
			if ((utf8_strlen($this->request->post['email']) > 96) || !filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL)) {
				$this->error['email'] = $this->language->get('error_email');
			} else {
				if ($this->model_account_customer->getTotalCustomersByEmail($this->request->post['email'])) {
					$this->error['warning'] = $this->language->get('error_exists');
				}
			}
		} else {
			$this->error['email'] = $this->language->get('error_email');
		}

		// moved up
		//if ($this->model_account_customer->getTotalCustomersByEmail($this->request->post['email'])) {
		//	$this->error['warning'] = $this->language->get('error_exists');
		//}

		if (!empty($this->request->post['telephone'])) {
			if ((utf8_strlen($this->request->post['telephone']) < 3) || (utf8_strlen($this->request->post['telephone']) > 32)) {
				$this->error['telephone'] = $this->language->get('error_telephone');
			}
		} else {
			$this->error['telephone'] = $this->language->get('error_telephone');
		}

		// Customer Group
		if (isset($this->request->post['customer_group_id']) && is_array($this->config->get('config_customer_group_display')) && in_array($this->request->post['customer_group_id'], $this->config->get('config_customer_group_display'))) {
			$customer_group_id = $this->request->post['customer_group_id'];
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}

		// no need to check if error
		if (!$this->error) {
			// Custom field validation
			$this->load->model('account/custom_field');

			$custom_fields = $this->model_account_custom_field->getCustomFields($customer_group_id);

			foreach ($custom_fields as $custom_field) {
				if ($custom_field['location'] == 'account') {
					if ($custom_field['required'] && empty($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']])) {
						$this->error['custom_field'][$custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
					} elseif (($custom_field['type'] == 'text') && !empty($custom_field['validation']) && !filter_var($this->request->post['custom_field'][$custom_field['location']][$custom_field['custom_field_id']], FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => $custom_field['validation'])))) {
						$this->error['custom_field'][$custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
					}
				}
			}
		}

		if (!empty($this->request->post['password'])) {
			if ((utf8_strlen(html_entity_decode($this->request->post['password'], ENT_QUOTES, 'UTF-8')) < 4) || (utf8_strlen(html_entity_decode($this->request->post['password'], ENT_QUOTES, 'UTF-8')) > 40)) {
				$this->error['password'] = $this->language->get('error_password');
			}
		} else {
			$this->error['password'] = $this->language->get('error_password');
		}
		
		if ($this->request->post['confirm'] != $this->request->post['password']) {
			$this->error['confirm'] = $this->language->get('error_confirm');
		}

		// Captcha
		if ($this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('register', (array)$this->config->get('config_captcha_page'))) {
			$captcha = $this->load->controller('extension/captcha/' . $this->config->get('config_captcha') . '/validate');

			if ($captcha) {
				$this->error['captcha'] = $captcha;
			}
		}

		// Agree to terms
		if ($this->config->get('config_account_id')) {
			$this->load->model('catalog/information');

			$information_info = $this->model_catalog_information->getInformation($this->config->get('config_account_id'));

			if ($information_info && !isset($this->request->post['agree'])) {
				$this->error['warning'] = sprintf($this->language->get('error_agree'), $information_info['title']);
			}
		}
		
		return !$this->error;
	}

    public function save() {
		$json = array();
		$fields = array(
			'company' => array('Kurum / Firma adı', 2, 100),
			'firstname' => array('Yetkili adı', 1, 32),
			'lastname' => array('Yetkili soyadı', 1, 32),
			'telephone' => array('Telefon numarası', 3, 32),
			'email' => array('E-posta adresi', 5, 96),
			'tax_number' => array('Vergi numarası', 10, 11),
			'tax_office' => array('Vergi dairesi', 2, 60),
			'address' => array('Açık adres', 10, 256)
		);
		$data = array();
		$errors = array();

		foreach ($fields as $field => $rules) {
			$value = isset($this->request->post[$field]) && is_scalar($this->request->post[$field]) ? trim($this->request->post[$field]) : '';
			$length = utf8_strlen($value);

			if ($length < $rules[1] || $length > $rules[2]) {
				$errors[] = $rules[0] . ' ' . $rules[1] . ' ile ' . $rules[2] . ' karakter arasında olmalıdır.';
			}

			$data[$field] = $value;
		}

		if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
			$errors[] = 'Geçerli bir e-posta adresi girin.';
		}

		if ($data['telephone'] !== '' && !preg_match('/^[0-9+().\s-]{7,32}$/', $data['telephone'])) {
			$errors[] = 'Telefon numarası geçerli bir biçimde olmalıdır.';
		}

		if ($data['tax_number'] !== '' && !preg_match('/^([0-9]{10}|[0-9]{11})$/', $data['tax_number'])) {
			$errors[] = 'Vergi numarası 10 veya 11 rakamdan oluşmalıdır.';
		}

		$document_codes = isset($this->request->post['documents']) && is_array($this->request->post['documents']) ? $this->request->post['documents'] : array();
		$document_codes = array_values(array_unique(array_filter($document_codes, function($code) {
			return is_string($code) && preg_match('/^[a-f0-9]{40}$/i', $code);
		})));
		$documents = array();
		if (!$document_codes) {
			$errors[] = 'Vergi levhası yüklemeniz zorunludur.';
		} else {
			$this->load->model('tool/upload');
			foreach ($document_codes as $code) {
				$upload = $this->model_tool_upload->getUploadByCode($code);
				if (!$upload || !isset($upload['filename']) || basename($upload['filename']) !== $upload['filename'] || !is_file(DIR_UPLOAD . $upload['filename'])) {
					$errors[] = 'Yüklenen belge kaydı bulunamadı. Vergi levhasını tekrar yükleyin.';
					continue;
				}

				$extension = strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION));
				if (!in_array($extension, array('pdf', 'png', 'jpg', 'jpeg'))) {
					$errors[] = 'Yalnızca PDF, PNG, JPG veya JPEG belgeleri kabul edilir.';
					continue;
				}

				$documents[] = array('code' => $code, 'name' => $upload['name']);
			}
		}

		if ($errors) {
			$json['error']['warning'] = implode(' ', array_unique($errors));
		} else {
			$this->load->model('account/customer');
			if ($this->model_account_customer->getTotalCustomersByEmail($data['email'])) {
				$json['error']['warning'] = 'Bu e-posta adresiyle kayıtlı bir müşteri zaten bulunuyor.';
			} elseif ($this->model_account_customer->getTotalCustomersByTelephone($data['telephone'])) {
				$json['error']['warning'] = 'Bu telefon numarasıyla kayıtlı bir müşteri zaten bulunuyor.';
			} else {
				$data['documents'] = $documents;
				$data['password'] = bin2hex(random_bytes(32));
				$data['newsletter'] = 0;
				$customer_id = $this->model_account_customer->addCustomer($data);

				$this->load->model('account/address');
				// OpenCart's standard address_1/address_2 columns are each 128 chars.
				// Split longer addresses so the address insert cannot fail after the
				// customer row has already been created.
				$address_1 = utf8_substr($data['address'], 0, 128);
				$address_2 = utf8_substr($data['address'], 128, 128);

				$this->model_account_address->addAddress($customer_id, array(
					'firstname' => $data['firstname'],
					'lastname' => $data['lastname'],
					'company' => $data['company'],
					'address_1' => $address_1,
					'address_2' => $address_2,
					'postcode' => '',
					'city' => '',
					'zone_id' => (int)$this->config->get('config_zone_id'),
					'country_id' => (int)$this->config->get('config_country_id'),
					'default' => 1
				));

				$json['success'] = 'Teşekkürler. Kurumsal başvurunuz alındı İncelendikten sonra hesabınız aktif hale getirilecektir.';
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function customfield() {
		$json = array();

		$this->load->model('account/custom_field');

		// Customer Group
		if (isset($this->request->get['customer_group_id']) && is_array($this->config->get('config_customer_group_display')) && in_array($this->request->get['customer_group_id'], $this->config->get('config_customer_group_display'))) {
			$customer_group_id = $this->request->get['customer_group_id'];
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}

		$custom_fields = $this->model_account_custom_field->getCustomFields($customer_group_id);

		foreach ($custom_fields as $custom_field) {
			$json[] = array(
				'custom_field_id' => $custom_field['custom_field_id'],
				'required'        => $custom_field['required']
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
