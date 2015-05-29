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
		if($user->getValue('status') != 'active') return array(
			'success' => false,
			'message' => 'This user is not active',
		);
		if(!password_verify($pass, $user->getValue('password'))) return array(
			'success' => false,
			'message' => 'Wrong password',
		);
		$xFrame->setSessionData('user', $user->getValue('_id'));
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
	
	function register(){
		global $xFrame;
		$editor = $xFrame->getModel('editor');
		foreach($_POST as $key => $value){
			$parts = array_map('trim', explode('_', $key));
			$editor->setField($parts[0], $parts[1], $value);
		}
		$user = $xFrame->loadResource('access', array(
			'identifier' => $editor->getValue('identifier'),
			'type' => 'user',
		));
		if($user) return array(
			'success' => false,
			'message' => 'username exists',
		);
		$editor->save();
		return array(
			'success' => true,
			'message' => 'you can now log in',
		);
	}
	
}