<?php

class Cart {
	
	private $db;
	private $order;
	
	function __construct(){
		$this->init();
	}
	
	function init($order = ''){
		global $xFrame;
		if(!$order) $order = $xFrame->getSessionData('order');
		if(!$order) $order = new MongoId();
		$xFrame->setSessionData('order', $order);
		$this->db = $xFrame->getDBTable('orders');
		$ord = $this->db->find(array('_id' => $order));
		if($ord->hasNext()) $this->order = $ord->getNext();
		if(!$this->order){
			$this->order = array(
				'_id' => $order,
				'items' => array(),
				'status' => 'pending',
				'details' => array(),
			);
			$this->db->insert($this->order);
		}
	}
	
	function saveOrder(){
		$this->db->update(array('_id' => $this->order['_id']), $this->order);
	}
	
	function getSummary(){
		$total = 0;
		$count = 0;
		foreach($this->order['items'] as $item){
			$count += $item['count'];
			$total += $item['price'] * $item['count'];
		}
		return array(
			'total' => $total,
			'count' => $count,
		);
	}
	
	function formatPrice($price){
		return number_format((float)$price, 2, ',', '') . ' €';
	}
	
	function submitOrder(){
		$this->order['status'] = 'submitted';
		$this->saveOrder();
	}
	
	function getOrder(){
		return $this->order;
	}
	
	function getOrderDetails(){
		return $this->order['details'];
	}
	
	function setOrderDetails($details){
		$this->order['details'] = $details;
		$this->saveOrder();
	}
	
	function getOrderSummary(){
		
	}
	
	function formatWeight($weight){
		$weight = (float)$weight;
		$label = 'kg';
		if($weight < 1){
			$weight *= 1000.0;
			$label = 'g';
		} elseif($weight > 1000){
			$weight /= 1000.0;
			$label = 'T';
		}
		return number_format($weight, 2, ',', '') . ' ' . $label;
	}
	
	function addItem(){
		global $xFrame;
		$id = $xFrame->getAPIParamter('id');
		if(!$id) return array(
			'status' => false,
			'message' => 'missing id', 
		);
		$id = $id[0];
		$item = $xFrame->loadResource('content', array('_id' => new MongoId($id)));
		if(!$item) return array(
			'status' => false,
			'message' => 'No such item', 
		);
		$price = $item->getValue('data.price');
		$attr = $this->parseAttributes($item->getValue('attributes'));
		$attributes = array();
		foreach($attr as $name => $val){
			$value = trim($_POST[$name]);
			if(!$value) $value = 0;
			eval('$price = ' . $price . ' ' . $val[$value]['operation'] . ';');
			$attributes[$name] = $val[$value]['name'];
		}
		$amount = $_POST['product-amount'];
		if($amount < 1) $amount = 1;
		$new = array(
			'id' => $id,
			'count' => $amount,
			'name' => $item->getValue('identifier'),
			'stock' => $item->getValue('data.stock'),
			'weight' => $item->getValue('data.weight'),
			'delivery' => $item->getValue('data.deliveries'),
		);
		$b = false;
		foreach($this->order['items'] as $key => $item){
			if($item['id'] == $id && $item['attr'] == $attributes){
				$this->order['items'][$key]['count'] += $amount;
				$b = true;
				break;
			}
		}
		if(!$b){
			$new['attr'] = $attributes;
			$new['price'] = $price;
			$this->order['items'][] = $new;
		}
		$this->saveOrder();
	}
	
	function selectDelivery(){
		foreach($_POST as $key => $value){
			$ex = explode('_', $key);
			$this->order['items'][$ex[0]][$ex[1]] = $value;
		}
		$this->saveOrder();
	}
	
	function getItems(){
		return $this->order['items'];
	}
	
	function getDeliveryFields(){
		global $xFrame;
		$items = $this->order['items'];
		$deliveries = array();
		foreach($items as $item) $deliveries[] = $item['delivery'];
		$deliveries = array_unique($deliveries);
		$input = array();
		foreach($deliveries as $delivery){
			$delivery = $xFrame->loadResource('parsable', array('_id' => new MongoId($delivery)));
			if($delivery){
				$inputs = array_map('trim', explode(',', $delivery->getValue('fields')));
				foreach($inputs as $i) $input[] = $i;
			}
		}
		return array_unique($input);
	}
	
	function removeItem(){
		global $xFrame;
		$item = $xFrame->getAPIParamter('item');
		$item = $item ? $item[0] : 0;
		unset($this->order['items'][$item]);
		$this->saveOrder();
	}
	
	function updateAmount(){
		global $xFrame;
		$item = $xFrame->getAPIParamter('item');
		$item = $item ? $item[0] : 0;
		$amount = $_POST['product-amount'];
		$this->order['items'][$item]['count'] = $amount;
		$this->saveOrder();
	}
	
	function parseAttributes($attr){
		$attributes = array();
		foreach($attr as $key => $value){
			$vals = array_map('trim', explode('||', $value));
			$values = array();
			foreach($vals as $val){
				if(!$val) continue;
				$val = array_map('trim', explode('==', $val));
				$values[ $val[1] ] = array(
					'name' => $val[0],
					'operation' => $val[2],
				);
			}
			if(count($values) < 1) continue;
			$attributes[$key] = $values;
		}
		return $attributes;
	}
	
}