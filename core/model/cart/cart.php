<?php

class Cart {
	
	private $db;
	private $order;
	
	function __construct(){
		global $xFrame;
		$order = $xFrame->getSessionData('order');
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
			);
			$this->db->insert($this->order);
		}
	}
	
	function saveOrder(){
		
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
	
}