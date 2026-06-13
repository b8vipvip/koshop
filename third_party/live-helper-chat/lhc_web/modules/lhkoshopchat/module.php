<?php

$Module = array('name' => 'Koshop 客服工作台');

$ViewList = array();

/*
 * 首版部署阶段先不在路由层强制 functions 权限，避免未给角色授权时直接 503。
 * 页面仍然挂在 site_admin 下，必须登录后台后访问。
 * 后续确认角色权限后，可恢复：
 * 'functions' => array('use')
 */
$ViewList['dashboard'] = array(
    'params' => array(),
    'uparams' => array('cid')
);

$FunctionList = array();
$FunctionList['use'] = array('explain' => '允许客服使用 Koshop 客服工作台');
