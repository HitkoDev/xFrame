<?php

class User {
	
	function login(){
		global $xFrame;
		$user = $_POST['username'];
		$pass = $_POST['password'];
		if(!$user) return array(
			'success' => false,
			'message' => 'Missing username',
		);
		if(!$pass) return array(
			'success' => false,
			'message' => 'Missing password',
		);
		$user = $xFrame->loadResource(array(
			'identifier' => $user,
			'type' => 'user',
		));
		if(!$user) return array(
			'success' => false,
			'message' => 'No such user',
		);
		if(!password_verify($pass, $user['data']['password'])) return array(
			'success' => false,
			'message' => 'Wrong password',
		);
		$xFrame->session->set('user', $user['_id']);
		return array(
			'success' => true,
			'message' => 'Successfully logged in',
		);
	}
	
	function logout(){
		global $xFrame;
		$xFrame->session->set('user', '');
		return array(
			'success' => true,
			'message' => 'Successfully logged out',
		);
	}
	
}