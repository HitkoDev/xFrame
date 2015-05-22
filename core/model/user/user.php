<?php

class User {
	
	function login(){
		global $xFrame;
		if($xFrame->isLoggedIn()) return array(
			'success' => false,
			'message' => 'Someone is already logged in',
		);
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
		$user = $xFrame->loadResource('access', array(
			'identifier' => $user,
			'type' => 'user',
		));
		if(!$user) return array(
			'success' => false,
			'message' => 'No such user',
		);
		if(!password_verify($pass, $user->data['password'])) return array(
			'success' => false,
			'message' => 'Wrong password',
		);
		$xFrame->setSessionData('user', $user->_id);
		$xFrame->updateUser();
		return array(
			'success' => true,
			'message' => 'Successfully logged in',
		);
	}
	
	function logout(){
		global $xFrame;
		if(!$xFrame->isLoggedIn()) return array(
			'success' => false,
			'message' => 'No one is logged in',
		);
		$xFrame->setSessionData('user', '');
		$xFrame->updateUser();
		return array(
			'success' => true,
			'message' => 'Successfully logged out',
		);
	}
	
}