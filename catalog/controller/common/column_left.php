<?php
class ControllerCommonColumnLeft extends Controller {
	public function index() {
		$this->load->model('design/layout');

		if (isset($this->request->get['route'])) {
			$route = (string)$this->request->get['route'];
		} else {
			$route = 'common/home';
		}

		$data['show_account_menu'] = $this->customer->isLogged() && strpos($route, 'account/') === 0;

        $data['logged'] = $this->customer->isLogged();

        if($this->customer->isLogged()) {
            $data['adsoyad'] = $this->customer->getFirstName() . ' ' . $this->customer->getLastName();
            $data['musteri_no'] = $this->customer->getMusterino();
			$data['company'] = '';

			$address_id = (int)$this->customer->getAddressId();

			if ($address_id) {
				$this->load->model('account/address');
				$default_address = $this->model_account_address->getAddress($address_id);

				if ($default_address && !empty($default_address['company'])) {
					$data['company'] = $default_address['company'];
				}
			}
        } 

		if ($data['show_account_menu']) {
			$this->load->language('extension/module/account');
			$this->load->language('account/account');

			$data['route'] = $route;
			$data['account_menu'] = array(
				'account' => $this->url->link('account/account', '', true),
				'edit' => $this->url->link('account/edit', '', true),
				'password' => $this->url->link('account/password', '', true),
				'address' => $this->url->link('account/address', '', true),
				'wishlist' => $this->url->link('account/wishlist', '', true),
				'order' => $this->url->link('account/order', '', true),
				'download' => $this->url->link('account/download', '', true),
				'reward' => $this->config->get('total_reward_status') ? $this->url->link('account/reward', '', true) : '',
				'return' => $this->url->link('account/return', '', true),
				'transaction' => $this->url->link('account/transaction', '', true),
				'recurring' => $this->url->link('account/recurring', '', true),
				'affiliate' => $this->url->link('account/affiliate/add', '', true),
				'tracking' => '',
				'newsletter' => $this->url->link('account/newsletter', '', true),
				'logout' => $this->url->link('account/logout', '', true),
				'text_account' => $this->language->get('text_account'),
				'text_edit' => $this->language->get('text_edit'),
				'text_password' => $this->language->get('text_password'),
				'text_address' => $this->language->get('text_address'),
				'text_wishlist' => $this->language->get('text_wishlist'),
				'text_order' => $this->language->get('text_order'),
				'text_download' => $this->language->get('text_download'),
				'text_reward' => $this->language->get('text_reward'),
				'text_return' => $this->language->get('text_return'),
				'text_transaction' => $this->language->get('text_transaction'),
				'text_recurring' => $this->language->get('text_recurring'),
				'text_affiliate_add' => $this->language->get('text_affiliate_add'),
				'text_affiliate_edit' => $this->language->get('text_affiliate_edit'),
				'text_tracking' => $this->language->get('text_tracking'),
				'text_newsletter' => $this->language->get('text_newsletter'),
				'text_logout' => $this->language->get('text_logout')
			);

			$this->load->model('account/customer');
			$affiliate_info = $this->model_account_customer->getAffiliate($this->customer->getId());

			if ($affiliate_info) {
				$data['account_menu']['affiliate'] = $this->url->link('account/affiliate/edit', '', true);
				$data['account_menu']['tracking'] = $this->url->link('account/tracking', '', true);
			}
		} else {
			$data['account_menu'] = array();
		}

		$layout_id = 0;

		if ($route == 'product/category' && isset($this->request->get['path'])) {
			$this->load->model('catalog/category');

			$path = explode('_', (string)$this->request->get['path']);

			$layout_id = $this->model_catalog_category->getCategoryLayoutId(end($path));
		}

		if ($route == 'product/product' && isset($this->request->get['product_id'])) {
			$this->load->model('catalog/product');

			$layout_id = $this->model_catalog_product->getProductLayoutId($this->request->get['product_id']);
		}

		if ($route == 'information/information' && isset($this->request->get['information_id'])) {
			$this->load->model('catalog/information');

			$layout_id = $this->model_catalog_information->getInformationLayoutId($this->request->get['information_id']);
		}

		if (!$layout_id) {
			$layout_id = $this->model_design_layout->getLayout($route);
		}

		if (!$layout_id) {
			$layout_id = $this->config->get('config_layout_id');
		}

		$this->load->model('setting/module');

		$data['modules'] = array();

		$modules = $this->model_design_layout->getLayoutModules($layout_id, 'column_left');

		foreach ($modules as $module) {
			$part = explode('.', $module['code']);

			if ($data['show_account_menu'] && isset($part[0]) && $part[0] == 'account') {
				continue;
			}

			if (isset($part[0]) && $this->config->get('module_' . $part[0] . '_status')) {
				$module_data = $this->load->controller('extension/module/' . $part[0]);

				if ($module_data) {
					$data['modules'][] = $module_data;
				}
			}

			if (isset($part[1])) {
				$setting_info = $this->model_setting_module->getModule($part[1]);

				if ($setting_info && $setting_info['status']) {
					$output = $this->load->controller('extension/module/' . $part[0], $setting_info);

					if ($output) {
						$data['modules'][] = $output;
					}
				}
			}
		}

		return $this->load->view('common/column_left', $data);
	}
}
