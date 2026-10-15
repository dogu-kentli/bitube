<?php
class ControllerAccountAccount extends Controller {
	public function index() {
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('account/account', '', true);

			$this->response->redirect($this->url->link('account/login', '', true));
		}

		$this->load->language('account/account');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', '', true)
		);

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		} 
		
		$data['edit'] = $this->url->link('account/edit', '', true);
		$data['password'] = $this->url->link('account/password', '', true);
		$data['address'] = $this->url->link('account/address', '', true);
		$data['account'] = $this->url->link('account/account', '', true);
		$data['logout'] = $this->url->link('account/logout', '', true);

		$this->load->model('account/order');
		$this->load->model('account/address');
		$this->load->model('account/customer_group');

		$performance_total = $this->model_account_order->getCustomerOrderTotal();
		$data['performance_total'] = $this->currency->format($performance_total, $this->session->data['currency']);

		$address_info = $this->model_account_address->getAddress($this->customer->getAddressId());
		$data['performance_company'] = !empty($address_info['company']) ? $address_info['company'] : $this->customer->getFirstName() . ' ' . $this->customer->getLastName();

		$customer_group = $this->model_account_customer_group->getCustomerGroup($this->customer->getGroupId());
		$data['performance_discount'] = isset($customer_group['percent']) ? (float)$customer_group['percent'] : 0;
		$data['performance_group_name'] = !empty($customer_group['name']) ? $customer_group['name'] : '';

		$performance_thresholds = array();
		foreach ($this->model_account_customer_group->getCustomerGroups() as $group) {
			// Mevcut grup zaten müşterinin uygulanan seviyesidir; hedef başka bir grup olmalıdır.
			if ((int)$group['customer_group_id'] !== (int)$this->customer->getGroupId() && isset($group['spending_threshold']) && is_numeric($group['spending_threshold'])) {
				$performance_thresholds[] = array(
					'threshold' => (float)$group['spending_threshold'],
					'name' => isset($group['name']) ? $group['name'] : '',
					'percent' => isset($group['percent']) ? (float)$group['percent'] : 0
				);
			}
		}
		usort($performance_thresholds, function($a, $b) {
			return $a['threshold'] <=> $b['threshold'];
		});

		$previous_threshold = 0;
		$next_threshold = false;
		foreach ($performance_thresholds as $threshold) {
			if ($threshold['threshold'] <= $performance_total) {
				$previous_threshold = $threshold['threshold'];
			} elseif ($next_threshold === false) {
				$next_threshold = $threshold;
			}
		}

		if ($next_threshold !== false) {
			$range = $next_threshold['threshold'] - $previous_threshold;
			$data['performance_progress'] = $range > 0 ? max(0, min(100, (($performance_total - $previous_threshold) / $range) * 100)) : 0;
			$data['performance_has_next_tier'] = true;
			$data['performance_next_group_name'] = $next_threshold['name'];
			$data['performance_next_discount'] = $next_threshold['percent'];
			$data['performance_target'] = $this->currency->format($next_threshold['threshold'], $this->session->data['currency']);
			$data['performance_remaining'] = $this->currency->format(max(0, $next_threshold['threshold'] - $performance_total), $this->session->data['currency']);
		} else {
			$data['performance_progress'] = 100;
			$data['performance_has_next_tier'] = false;
			$data['performance_next_group_name'] = '';
			$data['performance_next_discount'] = 0;
			$data['performance_target'] = '';
			$data['performance_remaining'] = '';
		}

		$data['orders'] = array();

		foreach ($this->model_account_order->getOrders(0, 5) as $result) {
			$product_total = $this->model_account_order->getTotalOrderProductsByOrderId($result['order_id']);
			$voucher_total = $this->model_account_order->getTotalOrderVouchersByOrderId($result['order_id']);

			$data['orders'][] = array(
				'order_id' => $result['order_id'],
				'status' => $result['status'],
				'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'products' => $product_total + $voucher_total,
				'total' => $this->currency->format($result['total'], $result['currency_code'], $result['currency_value']),
				'view' => $this->url->link('account/order/info', 'order_id=' . $result['order_id'], true)
			);
		}
		
		$data['credit_cards'] = array();
		
		$files = glob(DIR_APPLICATION . 'controller/extension/credit_card/*.php');
		
		foreach ($files as $file) {
			$code = basename($file, '.php');
			
			if ($this->config->get('payment_' . $code . '_status') && $this->config->get('payment_' . $code . '_card')) {
				$this->load->language('extension/credit_card/' . $code, 'extension');

				$data['credit_cards'][] = array(
					'name' => $this->language->get('extension')->get('heading_title'),
					'href' => $this->url->link('extension/credit_card/' . $code, '', true)
				);
			}
		}
		
		$data['wishlist'] = $this->url->link('account/wishlist');
		$data['order'] = $this->url->link('account/order', '', true);
		$data['shop'] = $this->url->link('product/search', '', true);
		$data['download'] = $this->url->link('account/download', '', true);
		
		if ($this->config->get('total_reward_status')) {
			$data['reward'] = $this->url->link('account/reward', '', true);
		} else {
			$data['reward'] = '';
		}		
		
		$data['return'] = $this->url->link('account/return', '', true);
		$data['transaction'] = $this->url->link('account/transaction', '', true);
		$data['newsletter'] = $this->url->link('account/newsletter', '', true);
		$data['recurring'] = $this->url->link('account/recurring', '', true);
		
		$this->load->model('account/customer');
		
		$affiliate_info = $this->model_account_customer->getAffiliate($this->customer->getId());
		
		if (!$affiliate_info) {	
			$data['affiliate'] = $this->url->link('account/affiliate/add', '', true);
		} else {
			$data['affiliate'] = $this->url->link('account/affiliate/edit', '', true);
		}
		
		if ($affiliate_info) {		
			$data['tracking'] = $this->url->link('account/tracking', '', true);
		} else {
			$data['tracking'] = '';
		}
		
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		
		$this->response->setOutput($this->load->view('account/account', $data));
	}

	public function country() {
		$json = array();

		$this->load->model('localisation/country');

		$country_info = $this->model_localisation_country->getCountry($this->request->get['country_id']);

		if ($country_info) {
			$this->load->model('localisation/zone');

			$json = array(
				'country_id'        => $country_info['country_id'],
				'name'              => $country_info['name'],
				'iso_code_2'        => $country_info['iso_code_2'],
				'iso_code_3'        => $country_info['iso_code_3'],
				'address_format'    => $country_info['address_format'],
				'postcode_required' => $country_info['postcode_required'],
				'zone'              => $this->model_localisation_zone->getZonesByCountryId($this->request->get['country_id']),
				'status'            => $country_info['status']
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
