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

function out($v) {
    echo json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function body() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : array();
}

function orderStatus($id) {
    $s = array('待付款', '待发货', '已完成');
    return $s[$id % 3];
}

function koshopSafeProp($obj, $name, $default = '') {
    try {
        return isset($obj->$name) ? $obj->$name : $default;
    } catch (Throwable $e) {
        return $default;
    }
}

function koshopChatVariables($c) {
    $v = array();

    try {
        $raw = koshopSafeProp($c, 'chat_variables', '');
        $decoded = json_decode((string)$raw, true);
        if (is_array($decoded)) $v = $decoded;
    } catch (Throwable $e) {}

    try {
        $identifier = (string)koshopSafeProp($c, 'identifier', '');
        if (strpos($identifier, 'koshop:') === 0) {
            $identity = json_decode(base64_decode(substr($identifier, 7)), true);
            if (is_array($identity)) $v = array_merge($v, $identity);
        }
    } catch (Throwable $e) {}

    return $v;
}

function koshopBuyerName($c) {
    $v = koshopChatVariables($c);
    $name = trim((string)($v['koshop_buyer_name'] ?? ''));
    if ($name !== '') return $name;

    $nick = trim((string)koshopSafeProp($c, 'nick', ''));
    return $nick !== '' ? $nick : ('访客 ' . (int)$c->id);
}

function koshopBuyerAvatar($c) {
    $v = koshopChatVariables($c);
    return trim((string)($v['koshop_buyer_avatar'] ?? ''));
}

function koshopMessageSender($m) {
    $uid = (int)koshopSafeProp($m, 'user_id', 0);
    $supportName = trim((string)koshopSafeProp($m, 'name_support', ''));

    if ($uid > 0) return 'seller';
    if ($supportName !== '') return 'seller';

    return 'buyer';
}

function chatItem($c) {
    $last = '';
    try {
        $msgs = erLhcoreClassModelmsg::getList(array(
            'filter' => array('chat_id' => $c->id),
            'sort' => 'id DESC',
            'limit' => 1
        ));
        if ($msgs) {
            $m = reset($msgs);
            $last = (string)$m->msg;
        }
    } catch (Throwable $e) {}

    $avatar = koshopBuyerAvatar($c);

    return array(
        'id' => (int)$c->id,
        'buyerName' => koshopBuyerName($c),
        'buyerAvatar' => $avatar,
        'avatar' => $avatar,
        'lastMessage' => $last,
        'lastMessageAt' => date('H:i', (int)$c->time),
        'unread' => (int)$c->user_status === 0,
        'unreadCount' => (int)$c->user_status === 0 ? 1 : 0,
        'status' => (int)$c->status === 2 ? 'closed' : 'active',
        'orderStatus' => orderStatus($c->id),
        'isStarred' => false,
        'isPinned' => false
    );
}

try {
    if ($action === 'unread') {
        $count = erLhcoreClassModelChat::getCount(array('filter' => array('user_status' => 0)));
        out(array('ok' => true, 'count' => (int)$count));
    }

    if ($action === 'conversations') {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $size = min(100, max(1, (int)($_GET['pageSize'] ?? 30)));

        $params = array(
            'sort' => 'id DESC',
            'limit' => $size,
            'offset' => ($page - 1) * $size
        );

        if (($_GET['filter'] ?? '') === 'closed') {
            $params['filter'] = array('status' => 2);
        }

        $items = array();
        foreach (erLhcoreClassModelChat::getList($params) as $c) {
            $items[] = chatItem($c);
        }

        out(array(
            'ok' => true,
            'items' => $items,
            'pagination' => array(
                'page' => $page,
                'pageSize' => $size,
                'total' => erLhcoreClassModelChat::getCount(array())
            )
        ));
    }

    $c = erLhcoreClassModelChat::fetch($id);
    if (!$c) throw new Exception('会话不存在');

    if ($action === 'conversation') {
        out(array(
            'ok' => true,
            'conversation' => array(
                'id' => $id,
                'buyerName' => koshopBuyerName($c),
                'buyerAvatar' => koshopBuyerAvatar($c),
                'avatar' => koshopBuyerAvatar($c),
                'status' => 'active',
                'orderStatus' => orderStatus($id),
                'buyerId' => koshopSafeProp($c, 'hash', ''),
                'source' => 'Live Helper Chat'
            )
        ));
    }

    if ($action === 'messages' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $b = body();
        $content = trim((string)($b['content'] ?? ''));

        if ($content === '') {
            throw new Exception('消息内容不能为空');
        }

        $sellerName = trim((string)($b['sellerName'] ?? '')) ?: 'admin';
        $sellerAvatar = trim((string)($b['sellerAvatar'] ?? ''));

        $m = new erLhcoreClassModelmsg();
        $m->chat_id = $id;

        /*
         * 兼容 Live Helper Chat 不同版本：
         * BFF Token 请求没有 LHC 后台登录态，所以不依赖 erLhcoreClassUser::instance()。
         * 固定 user_id=1 + name_support，保证刷新后仍识别为 seller。
         * 不写 meta_msg，避免部分 LHC 版本模型无此字段导致 500。
         */
        $m->user_id = 1;
        $m->name_support = $sellerName;
        $m->time = time();
        $m->msg = $content;
        $m->saveThis();

        /*
         * 尽量刷新 chat 最近消息时间，不支持也不影响发送成功。
         */
        try {
            $c->last_msg_id = $m->id;
            $c->time = time();
            $c->updateThis();
        } catch (Throwable $e) {}

        out(array(
            'ok' => true,
            'message' => array(
                'id' => (int)$m->id,
                'sender' => 'seller',
                'content' => $content,
                'createdAt' => '刚刚',
                'read' => false,
                'senderName' => $sellerName,
                'senderAvatar' => $sellerAvatar,
                'buyerName' => koshopBuyerName($c),
                'buyerAvatar' => koshopBuyerAvatar($c),
                'sellerName' => $sellerName,
                'sellerAvatar' => $sellerAvatar
            )
        ));
    }

    if ($action === 'messages') {
        $items = array();

        foreach (erLhcoreClassModelmsg::getList(array(
            'filter' => array('chat_id' => $id),
            'sort' => 'id ASC',
            'limit' => 500
        )) as $m) {
            $sender = koshopMessageSender($m);
            $sellerName = trim((string)koshopSafeProp($m, 'name_support', '')) ?: 'admin';

            $items[] = array(
                'id' => (int)$m->id,
                'sender' => $sender,
                'content' => $m->msg,
                'createdAt' => date('m-d H:i', (int)$m->time),
                'read' => (int)koshopSafeProp($m, 'user_id', 0) === 0,
                'senderName' => $sender === 'seller' ? $sellerName : koshopBuyerName($c),
                'senderAvatar' => $sender === 'seller' ? '' : koshopBuyerAvatar($c),
                'buyerName' => koshopBuyerName($c),
                'buyerAvatar' => koshopBuyerAvatar($c),
                'sellerName' => $sellerName,
                'sellerAvatar' => ''
            );
        }

        out(array('ok' => true, 'items' => $items));
    }

    if ($action === 'read') {
        /*
         * 兼容处理：markAsRead 在不同版本可能签名不同。
         * 标记失败也不应该阻断聊天页面，所以这里兜底返回 ok。
         */
        try {
            if (method_exists('erLhcoreClassChat', 'markAsRead')) {
                erLhcoreClassChat::markAsRead($c);
            }
        } catch (Throwable $e) {
            try {
                $c->user_status = 1;
                $c->updateThis();
            } catch (Throwable $e2) {}
        }

        out(array('ok' => true));
    }

    if ($action === 'star' || $action === 'pin') {
        out(array('ok' => true));
    }

} catch (Throwable $e) {
    http_response_code(500);
    out(array(
        'ok' => false,
        'message' => $e->getMessage()
    ));
}

out(array('ok' => false, 'message' => '接口不存在'));
