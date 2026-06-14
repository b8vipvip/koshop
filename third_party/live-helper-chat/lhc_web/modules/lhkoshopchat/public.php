<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if ($origin === 'https://shop.cn12.vip' || preg_match('#^https?://localhost(?::\d+)?$#', $origin)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
function pout($v,$code=200){http_response_code($code);echo json_encode($v,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
function pbody(){$v=json_decode(file_get_contents('php://input'),true);return is_array($v)?$v:array();}
function safe($o,$n,$d=''){try{return isset($o->$n)?$o->$n:$d;}catch(Throwable $e){return $d;}}
$action=$Params['user_parameters_unordered']['action']??'start';
$id=(int)($_GET['id']??0);$hash=(string)($_GET['hash']??'');
try {
 if($action==='start'){
  $b=pbody();$name=trim((string)($b['name']??''))?:'访客';$avatar=trim((string)($b['avatar']??''));$uid=(string)($b['userId']??'');
  $c=new erLhcoreClassModelChat();$c->nick=$name;$c->hash=bin2hex(random_bytes(20));$c->time=time();$c->status=1;$c->user_id=1;$c->dep_id=1;$c->user_status=0;
  $identity=array('koshop_buyer_name'=>$name,'koshop_buyer_avatar'=>$avatar,'koshop_user_id'=>$uid);$c->identifier='koshop:'.base64_encode(json_encode($identity));$c->chat_variables=json_encode($identity);$c->saveThis();
  pout(array('ok'=>true,'session'=>array('id'=>(int)$c->id,'hash'=>$c->hash)));
 }
 $c=erLhcoreClassModelChat::fetch($id);if(!$c||$hash===''||!hash_equals((string)$c->hash,$hash))pout(array('ok'=>false,'message'=>'会话无效'),403);
 if($action==='send'){
  $content=trim((string)(pbody()['content']??''));if($content==='')pout(array('ok'=>false,'message'=>'消息不能为空'),422);
  $m=new erLhcoreClassModelmsg();$m->chat_id=$id;$m->user_id=0;$m->time=time();$m->msg=$content;$m->saveThis();$c->last_msg_id=$m->id;$c->time=time();$c->user_status=0;$c->updateThis();pout(array('ok'=>true));
 }
 if($action==='messages'){
  $items=array();foreach(erLhcoreClassModelmsg::getList(array('filter'=>array('chat_id'=>$id),'sort'=>'id ASC','limit'=>500)) as $m)$items[]=array('id'=>(int)$m->id,'sender'=>(int)safe($m,'user_id',0)>0?'seller':'buyer','content'=>(string)$m->msg);
  pout(array('ok'=>true,'items'=>$items));
 }
} catch(Throwable $e){pout(array('ok'=>false,'message'=>'客服服务暂时不可用'),500);}
pout(array('ok'=>false,'message'=>'接口不存在'),404);
