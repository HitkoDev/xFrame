<?php

if(!defined('CORE_PATH')) define('CORE_PATH', realpath(dirname(__file__) . '/../core'));
if(!defined('ASSETS_PATH')) define('ASSETS_PATH', realpath(dirname(__file__) . '/assets'));

include_once(CORE_PATH . '/config.php');
require_once(CORE_PATH . '/model/xFrame/xFrame.php');

$xFrame = new xFrame('manager');