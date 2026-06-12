<?php
$tpl = erLhcoreClassTemplate::getInstance('lhkoshopchat/dashboard.tpl.php');
$items = erLhcoreClassModelChat::getList(array(
    'sort' => 'last_msg_id DESC',
    'limit' => 80,
    'filterin' => array('status' => array(erLhcoreClassModelChat::STATUS_PENDING_CHAT, erLhcoreClassModelChat::STATUS_ACTIVE_CHAT, erLhcoreClassModelChat::STATUS_CLOSED_CHAT))
));
$visible = array();
foreach ($items as $item) {
    if (erLhcoreClassChat::hasAccessToRead($item)) $visible[] = $item;
}
$selected = false;
$cid = isset($Params['user_parameters_unordered']['cid']) ? (int)$Params['user_parameters_unordered']['cid'] : 0;
foreach ($visible as $item) if ($item->id === $cid) $selected = $item;
if ($selected === false && count($visible)) $selected = $visible[0];
$tpl->set('chats', $visible);
$tpl->set('selected_chat', $selected);
$Result['content'] = $tpl->fetch();
$Result['path'] = array(array('title' => 'Koshop 客服工作台'));
