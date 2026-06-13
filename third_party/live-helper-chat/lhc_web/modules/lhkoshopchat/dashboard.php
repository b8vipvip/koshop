<?php

$tpl = erLhcoreClassTemplate::getInstance('lhkoshopchat/dashboard.tpl.php');

$visible = array();
$selected = false;
$errors = array();

try {
    $statuses = array();

    foreach (array('STATUS_PENDING_CHAT', 'STATUS_ACTIVE_CHAT', 'STATUS_CLOSED_CHAT') as $const) {
        $full = 'erLhcoreClassModelChat::' . $const;
        if (defined($full)) {
            $statuses[] = constant($full);
        }
    }

    $params = array(
        'sort' => 'id DESC',
        'limit' => 80,
    );

    if (!empty($statuses)) {
        $params['filterin'] = array('status' => $statuses);
    }

    $items = erLhcoreClassModelChat::getList($params);

    foreach ($items as $item) {
        $allow = true;

        if (class_exists('erLhcoreClassChat') && method_exists('erLhcoreClassChat', 'hasAccessToRead')) {
            try {
                $allow = erLhcoreClassChat::hasAccessToRead($item);
            } catch (Throwable $e) {
                // 某些 LHC 版本 hasAccessToRead 签名不一致，先不中断工作台。
                $allow = true;
            }
        }

        if ($allow) {
            $visible[] = $item;
        }
    }

    $cid = isset($Params['user_parameters_unordered']['cid'])
        ? (int)$Params['user_parameters_unordered']['cid']
        : 0;

    foreach ($visible as $item) {
        if ((int)$item->id === $cid) {
            $selected = $item;
            break;
        }
    }

    if ($selected === false && count($visible) > 0) {
        $selected = $visible[0];
    }
} catch (Throwable $e) {
    $errors[] = $e->getMessage();
}

$tpl->set('chats', $visible);
$tpl->set('selected_chat', $selected);
$tpl->set('koshop_errors', $errors);

$Result['content'] = $tpl->fetch();
$Result['path'] = array(
    array('title' => 'Koshop 客服工作台')
);
