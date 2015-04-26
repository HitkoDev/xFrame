<?php

if(!defined('CORE_PATH')) define('CORE_PATH', realpath(dirname(__file__) . '/../core'));
if(!defined('ASSETS_PATH')) define('ASSETS_PATH', realpath(dirname(__file__) . '/assets'));

include_once(CORE_PATH . '/config.php');
include_once(CORE_PATH . '/init.php');

echo $xFrame->parseTemplate();