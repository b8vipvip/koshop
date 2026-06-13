<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$expected = getenv('KOSHOP_CHAT_API_TOKEN');
$auth = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
if ($expected && !hash_equals('Bearer ' . $expected, $auth)) {
    http_response_code(401);
    echo json_encode(array('ok' => false, 'message' => '未授权'));
    exit;
}

$action = isset($Params['user_parameters_unordered']['action']) ? $Params['user_parameters_unordered']['action'] : 'conversations';
$id = isset($Params['user_parameters_unordered']['id']) ? (int)$Params['user_parameters_unordered']['id'] : 0;
function out($v) { echo json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
function body() { return json_decode(file_get_contents('php://input'), true) ?: array(); }
function orderStatus($id) { $s = array('待付款', '待发货', '已完成'); return $s[$id % 3]; }
function koshopChatVariables($c) {
    $v = json_decode((string)$c->chat_variables, true); if (!is_array($v)) $v = array();
    if (isset($c->identifier) && strpos((string)$c->identifier, 'koshop:') === 0) { $identity = json_decode(base64_decode(substr((string)$c->identifier, 7)), true); if (is_array($identity)) $v = array_merge($v, $identity); }
    return $v;
}
function koshopBuyerName($c) { $v = koshopChatVariables($c); return trim((string)($v['koshop_buyer_name'] ?? '')) ?: ($c->nick ?: ('访客 ' . $c->id)); }
function koshopBuyerAvatar($c) { $v = koshopChatVariables($c); return trim((string)($v['koshop_buyer_avatar'] ?? '')); }
function koshopMessageSender($m) {
    if (isset($m->user_id) && (int)$m->user_id > 0) return 'seller';
    if (isset($m->name_support) && trim((string)$m->name_support) !== '') return 'seller';
    if (isset($m->meta_msg) && strpos((string)$m->meta_msg, 'koshop_seller') !== false) return 'seller';
    return 'buyer';
}
function koshopMessageMeta($m) { $v = json_decode((string)($m->meta_msg ?? ''), true); return is_array($v) ? $v : array(); }
function chatItem($c) {
    $last = '';
    try { $msgs = erLhcoreClassModelmsg::getList(array('filter' => array('chat_id' => $c->id), 'sort' => 'id DESC', 'limit' => 1)); if ($msgs) $last = reset($msgs)->msg; } catch (Throwable $e) {}
    $avatar = koshopBuyerAvatar($c);
    return array('id' => (int)$c->id, 'buyerName' => koshopBuyerName($c), 'buyerAvatar' => $avatar, 'avatar' => $avatar, 'lastMessage' => $last, 'lastMessageAt' => date('H:i', (int)$c->time), 'unread' => (int)$c->user_status === 0, 'unreadCount' => (int)$c->user_status === 0 ? 1 : 0, 'status' => (int)$c->status === 2 ? 'closed' : 'active', 'orderStatus' => orderStatus($c->id), 'isStarred' => false, 'isPinned' => false);
}

try {
    if ($action === 'unread') out(array('ok' => true, 'count' => (int)erLhcoreClassModelChat::getCount(array('filter' => array('user_status' => 0)))));
    if ($action === 'conversations') {
        $page = max(1, (int)($_GET['page'] ?? 1)); $size = min(100, max(1, (int)($_GET['pageSize'] ?? 30)));
        $params = array('sort' => 'id DESC', 'limit' => $size, 'offset' => ($page - 1) * $size); if (($_GET['filter'] ?? '') === 'closed') $params['filter'] = array('status' => 2);
        $items = array(); foreach (erLhcoreClassModelChat::getList($params) as $c) $items[] = chatItem($c);
        out(array('ok' => true, 'items' => $items, 'pagination' => array('page' => $page, 'pageSize' => $size, 'total' => erLhcoreClassModelChat::getCount(array()))));
    }
    $c = erLhcoreClassModelChat::fetch($id); if (!$c) throw new Exception('会话不存在');
    if ($action === 'conversation') out(array('ok' => true, 'conversation' => array('id' => $id, 'buyerName' => koshopBuyerName($c), 'buyerAvatar' => koshopBuyerAvatar($c), 'avatar' => koshopBuyerAvatar($c), 'status' => 'active', 'orderStatus' => orderStatus($id), 'buyerId' => $c->hash, 'source' => 'Live Helper Chat')));
    if ($action === 'messages' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $b = body(); $content = trim((string)($b['content'] ?? '')); if ($content === '') throw new Exception('消息内容不能为空');
        $sellerName = trim((string)($b['sellerName'] ?? '')) ?: 'admin'; $sellerAvatar = trim((string)($b['sellerAvatar'] ?? ''));
        $userId = 0; try { $currentUser = erLhcoreClassUser::instance(); if ($currentUser->isLogged()) $userId = (int)$currentUser->getUserData()->id; } catch (Throwable $e) {}
        $m = new erLhcoreClassModelmsg(); $m->chat_id = $id; $m->user_id = $userId > 0 ? $userId : -1; $m->name_support = $sellerName; $m->meta_msg = json_encode(array('koshop_seller' => true, 'sellerAvatar' => $sellerAvatar)); $m->time = time(); $m->msg = $content; $m->saveThis();
        out(array('ok' => true, 'message' => array('id' => (int)$m->id, 'sender' => 'seller', 'content' => $content, 'createdAt' => '刚刚', 'read' => false, 'senderName' => $sellerName, 'senderAvatar' => $sellerAvatar, 'buyerName' => koshopBuyerName($c), 'buyerAvatar' => koshopBuyerAvatar($c), 'sellerName' => $sellerName, 'sellerAvatar' => $sellerAvatar)));
    }
    if ($action === 'messages') {
        $items = array(); foreach (erLhcoreClassModelmsg::getList(array('filter' => array('chat_id' => $id), 'sort' => 'id ASC', 'limit' => 500)) as $m) {
            $sender = koshopMessageSender($m); $meta = koshopMessageMeta($m); $sellerName = trim((string)($m->name_support ?? '')) ?: 'admin'; $sellerAvatar = trim((string)($meta['sellerAvatar'] ?? ''));
            $items[] = array('id' => (int)$m->id, 'sender' => $sender, 'content' => $m->msg, 'createdAt' => date('m-d H:i', (int)$m->time), 'read' => (int)$m->user_id === 0, 'senderName' => $sender === 'seller' ? $sellerName : koshopBuyerName($c), 'senderAvatar' => $sender === 'seller' ? $sellerAvatar : koshopBuyerAvatar($c), 'buyerName' => koshopBuyerName($c), 'buyerAvatar' => koshopBuyerAvatar($c), 'sellerName' => $sellerName, 'sellerAvatar' => $sellerAvatar);
        } out(array('ok' => true, 'items' => $items));
    }
    if ($action === 'read') { erLhcoreClassChat::markAsRead($c); out(array('ok' => true)); }
    if ($action === 'star' || $action === 'pin') out(array('ok' => true));
} catch (Throwable $e) { http_response_code(500); out(array('ok' => false, 'message' => $e->getMessage())); }
out(array('ok' => false, 'message' => '接口不存在'));
