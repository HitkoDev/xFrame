<?php

if(!defined('CORE_PATH')) define('CORE_PATH', realpath(dirname(__file__) . '/core'));

include_once(CORE_PATH . '/config.php');
require_once(CORE_PATH . '/model/xFrame/xFrame.php');

$xFrame = new xFrame();

var_dump($xFrame);