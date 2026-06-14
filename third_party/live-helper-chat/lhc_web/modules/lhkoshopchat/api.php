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

function koshopBeijingDate($timestamp, $format = 'm-d H:i') {
    $dt = new DateTime('@' . (int)$timestamp);
    $dt->setTimezone(new DateTimeZone('Asia/Shanghai'));
    return $dt->format($format);
}

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

/*
 * 关键修复：
 * Live Helper Chat 访客页面出现
 * “You are number X in the queue. Please wait...”
 * 是因为 chat 仍是 Pending 状态。
 *
 * 卖家后台打开/读取/发送消息时，自动把会话切换为 Active。
 */
function koshopAutoAcceptChat($c, $sellerName = 'admin') {
    try {
        $changed = false;

        if ((int)koshopSafeProp($c, 'status', 0) === 0) {
            $c->status = 1; // 0 Pending, 1 Active, 2 Closed
            $changed = true;
        }

        if ((int)koshopSafeProp($c, 'user_id', 0) === 0) {
            $c->user_id = 1;
            $changed = true;
        }

        try {
            $c->user = $sellerName;
            $changed = true;
        } catch (Throwable $e) {}

        try {
            if ((int)koshopSafeProp($c, 'accept_time', 0) === 0) {
                $c->accept_time = time();
                $changed = true;
            }
        } catch (Throwable $e) {}

        try {
            $c->support_informed = 1;
            $changed = true;
        } catch (Throwable $e) {}

        if ($changed) {
            try {
                $c->updateThis();
            } catch (Throwable $e) {
                try { $c->saveThis(); } catch (Throwable $e2) {}
            }
        }
    } catch (Throwable $e) {}
}

function koshopLastMessage($id) { try { $x=erLhcoreClassModelmsg::getList(array('filter'=>array('chat_id'=>$id),'sort'=>'id DESC','limit'=>1)); return $x?reset($x):null; } catch(Throwable $e){ return null; } }
function koshopUnreadBuyerCount($c) { if ((int)koshopSafeProp($c,'user_status',1)!==0) return 0; $last=koshopLastMessage($c->id); if (!$last || koshopMessageSender($last)!=='buyer') return 0; $count=0; try { foreach(erLhcoreClassModelmsg::getList(array('filter'=>array('chat_id'=>$c->id),'sort'=>'id DESC','limit'=>500)) as $m){ if(koshopMessageSender($m)==='seller') break; $count++; } } catch(Throwable $e){} return max(1,$count); }
function koshopAttachment($raw){$x=json_decode((string)$raw,true);return is_array($x)&&isset($x['koshopType'])?$x:null;}
function koshopMessageItem($m,$c,$sellerAvatar='') { $a=koshopAttachment($m->msg);$sender=koshopMessageSender($m);$sellerName=trim((string)koshopSafeProp($m,'name_support',''))?:'店铺客服';return array('id'=>(int)$m->id,'sender'=>$sender,'type'=>$a?$a['koshopType']:'text','content'=>$a?($a['text']??'附件'):(string)$m->msg,'url'=>$a?($a['url']??''):'','thumbnailUrl'=>$a?($a['thumbnailUrl']??(($a['koshopType']??'')==='image'?($a['url']??''):'')):'','fileName'=>$a?($a['fileName']??''):'','mimeType'=>$a?($a['mimeType']??''):'','size'=>$a?(int)($a['size']??0):0,'createdAt'=>koshopBeijingDate((int)$m->time),'createdAtText'=>koshopBeijingDate((int)$m->time,'Y-m-d H:i:s'),'read'=>$sender==='buyer','senderName'=>$sender==='seller'?$sellerName:koshopBuyerName($c),'senderAvatar'=>$sender==='seller'?$sellerAvatar:koshopBuyerAvatar($c),'buyerName'=>koshopBuyerName($c),'buyerAvatar'=>koshopBuyerAvatar($c),'sellerName'=>$sellerName,'sellerAvatar'=>$sellerAvatar); }
function chatItem($c) { $last=koshopLastMessage($c->id);$avatar=koshopBuyerAvatar($c);$status=(int)koshopSafeProp($c,'status',0);$unreadCount=koshopUnreadBuyerCount($c);$t=$last?(int)$last->time:(int)$c->time;return array('id'=>(int)$c->id,'buyerName'=>koshopBuyerName($c),'buyerAvatar'=>$avatar,'avatar'=>$avatar,'lastMessage'=>$last?(string)$last->msg:'','lastMessageAt'=>$t?koshopBeijingDate($t,'H:i'):'','unread'=>$unreadCount>0,'unreadCount'=>$unreadCount,'status'=>$status===2?'closed':($status===0?'pending':'active'),'orderStatus'=>orderStatus($c->id),'isStarred'=>false,'isPinned'=>false);}

try {
    if ($action === 'unread') {
        $count = 0;
        foreach (erLhcoreClassModelChat::getList(array('filter' => array('user_status' => 0), 'limit' => 1000)) as $unreadChat) {
            $count += koshopUnreadBuyerCount($unreadChat);
        }
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
                'status' => (int)koshopSafeProp($c, 'status', 0) === 0 ? 'pending' : 'active',
                'orderStatus' => orderStatus($id),
                'buyerId' => koshopSafeProp($c, 'hash', ''),
                'source' => 'Live Helper Chat'
            )
        ));
    }

    if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') { if(!isset($_FILES['file'])) throw new Exception('请选择文件'); $f=$_FILES['file'];$type=(string)($_POST['type']??'file');$allowed=array('image'=>array('image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'),'video'=>array('video/mp4'=>'mp4','video/webm'=>'webm','video/quicktime'=>'mov'));if(!isset($allowed[$type])||(int)$f['error']!==UPLOAD_ERR_OK)throw new Exception('上传失败');$max=$type==='image'?10485760:52428800;if((int)$f['size']>$max)throw new Exception('文件过大');$mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);if(!isset($allowed[$type][$mime]))throw new Exception('文件格式不支持');$dir=getenv('KOSHOP_UPLOAD_DIR')?:dirname(__DIR__,3).'/var/koshop_uploads';if(!is_dir($dir))mkdir($dir,0750,true);$name=bin2hex(random_bytes(20)).'.'.$allowed[$type][$mime];if(!move_uploaded_file($f['tmp_name'],$dir.'/'.$name))throw new Exception('保存失败');$base=rtrim(getenv('KOSHOP_UPLOAD_BASE_URL')?:'https://kefu.cn12.vip/var/koshop_uploads','/');$thumbUrl=$type==='image'?$base.'/'.$name:'';if($type==='video'&&isset($_FILES['thumbnail'])&&(int)$_FILES['thumbnail']['error']===UPLOAD_ERR_OK){$tf=$_FILES['thumbnail'];$tm=(new finfo(FILEINFO_MIME_TYPE))->file($tf['tmp_name']);if(in_array($tm,array('image/jpeg','image/webp'),true)&&$tf['size']<=2097152){$tn=bin2hex(random_bytes(20)).($tm==='image/webp'?'.webp':'.jpg');if(move_uploaded_file($tf['tmp_name'],$dir.'/'.$tn))$thumbUrl=$base.'/'.$tn;}}$payload=array('koshopType'=>$type,'url'=>$base.'/'.$name,'thumbnailUrl'=>$thumbUrl,'fileName'=>basename((string)$f['name']),'mimeType'=>$mime,'size'=>(int)$f['size'],'text'=>$type==='image'?'图片':'视频');$m=new erLhcoreClassModelmsg();$m->chat_id=$id;$m->user_id=1;$m->name_support=(string)($_POST['sellerName']??'admin');$m->time=time();$m->msg=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);$m->saveThis();$c->last_msg_id=$m->id;$c->user_status=1;$c->updateThis();out(array('ok'=>true,'message'=>koshopMessageItem($m,$c))); }

    if ($action === 'messages' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $b = body();
        $content = trim((string)($b['content'] ?? ''));

        if ($content === '') {
            throw new Exception('消息内容不能为空');
        }

        $sellerName = trim((string)($b['sellerName'] ?? '')) ?: 'admin';
        $sellerAvatar = trim((string)($b['sellerAvatar'] ?? ''));

        // 卖家第一次回复时自动接入排队访客
        koshopAutoAcceptChat($c, $sellerName);

        $m = new erLhcoreClassModelmsg();
        $m->chat_id = $id;
        $m->user_id = 1;
        $m->name_support = $sellerName;
        $m->time = time();
        $m->msg = $content;
        $m->saveThis();

        try {
            $c->last_msg_id = $m->id;
            $c->last_op_msg_time = time();
            $c->time = time();
            $c->user_status = 1;
            $c->updateThis();
        } catch (Throwable $e) {}

        out(array('ok' => true, 'message' => koshopMessageItem($m, $c, $sellerAvatar)));    }

    if ($action === 'messages') {
        /*
         * 卖家打开会话详情时也自动接入。
         * 这样访客页面会从排队状态进入聊天状态。
         */
        koshopAutoAcceptChat($c, 'admin');

        $items = array();

        foreach (erLhcoreClassModelmsg::getList(array(
            'filter' => array('chat_id' => $id),
            'sort' => 'id ASC',
            'limit' => 500
        )) as $m) {
            $items[] = koshopMessageItem($m, $c);
        }

        out(array('ok' => true, 'items' => $items));
    }

    if ($action === 'read') {
        koshopAutoAcceptChat($c, 'admin');

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

        try { $c->user_status = 1; foreach (array('has_unread_messages','has_unread_op_messages') as $p) { try { $c->$p = 0; } catch (Throwable $e) {} } $c->updateThis(); } catch (Throwable $e) {}
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
