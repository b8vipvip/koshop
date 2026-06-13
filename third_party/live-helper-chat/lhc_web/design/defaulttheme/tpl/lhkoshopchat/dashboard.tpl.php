<?php
if (!function_exists('koshop_h')) {
    function koshop_h($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('koshop_prop')) {
    function koshop_prop($obj, $name, $fallback = '未记录') {
        try {
            if (isset($obj->$name) && $obj->$name !== '') {
                return $obj->$name;
            }
        } catch (Throwable $e) {}
        return $fallback;
    }
}
if (!function_exists('koshop_status_text')) {
    function koshop_status_text($chat) {
        $status = isset($chat->status) ? (int)$chat->status : -1;
        if ($status === 0) return '等待中';
        if ($status === 1) return '进行中';
        if ($status === 2) return '已结束';
        return '状态 ' . $status;
    }
}
?>
<style>
.koshop-workbench{display:grid;grid-template-columns:290px minmax(420px,1fr) 300px;min-height:calc(100vh - 110px);border:1px solid #e8e9ed;border-radius:14px;overflow:hidden;background:#fff}
.kw-col{min-width:0;border-right:1px solid #eceef1}
.kw-col:last-child{border:0}
.kw-head{padding:18px;border-bottom:1px solid #eceef1}
.kw-head h2,.kw-head h3{margin:0;font-size:16px}
.kw-head p{margin:5px 0 0;color:#8a919c;font-size:12px}
.kw-search{margin-top:13px;width:100%;border:1px solid #e2e5e9;border-radius:9px;padding:9px}
.kw-chat{display:block;padding:13px 16px;border-bottom:1px solid #f0f1f3;color:#333;text-decoration:none}
.kw-chat:hover,.kw-chat.active{background:#fff5ef}
.kw-chat b{font-size:13px}
.kw-chat small{display:block;margin-top:4px;color:#9299a3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.kw-status{float:right;border-radius:12px;padding:2px 7px;background:#edf8f2;color:#1f9a61;font-size:10px}
.kw-center{display:flex;flex-direction:column;background:#f7f8fa}
.kw-empty{margin:auto;text-align:center;color:#8f96a0}
.kw-card{margin:18px;background:#fff;border:1px solid #e7e9ed;border-radius:12px;padding:18px}
.kw-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:15px}
.kw-btn{display:inline-block;padding:9px 13px;border-radius:8px;background:#ef623e;color:#fff;text-decoration:none;font-size:12px}
.kw-btn.alt{background:#fff;border:1px solid #ddd;color:#555}
.kw-info{padding:18px}
.kw-info dl{font-size:12px}
.kw-info dt{color:#969da6;margin-top:14px}
.kw-info dd{margin:3px 0;word-break:break-all}
.kw-error{margin:12px 18px;padding:12px;border-radius:10px;background:#fff2f0;color:#b42318;border:1px solid #ffccc7;font-size:12px}
@media(max-width:1100px){.koshop-workbench{grid-template-columns:260px 1fr}.kw-info{display:none}}
@media(max-width:720px){.koshop-workbench{display:block}.kw-col{border:0}.kw-center{min-height:360px}.kw-list{max-height:330px;overflow:auto}}
</style>

<?php if (!empty($koshop_errors)) : ?>
    <div class="kw-error">
        Koshop 工作台读取会话时遇到兼容性提示：
        <?php echo koshop_h(implode('；', $koshop_errors)); ?>
    </div>
<?php endif; ?>

<div class="koshop-workbench">
    <section class="kw-col">
        <div class="kw-head">
            <h2>买家咨询</h2>
            <p>等待中、进行中与最近结束的会话</p>
            <input class="kw-search" id="kw-search" placeholder="搜索访客 ID / 买家昵称" oninput="document.querySelectorAll('.kw-chat').forEach(e=>e.hidden=!e.innerText.toLowerCase().includes(this.value.toLowerCase()))">
        </div>

        <div class="kw-list">
            <?php if (empty($chats)) : ?>
                <div class="kw-empty" style="padding:50px 10px">当前没有咨询</div>
            <?php endif; ?>

            <?php foreach ($chats as $chat) : ?>
                <?php
                    $chatTitle = koshop_prop($chat, 'nick', '');
                    if ($chatTitle === '') $chatTitle = '访客 #' . $chat->id;
                    $lastMsg = koshop_prop($chat, 'last_msg', '暂无消息');
                ?>
                <a class="kw-chat <?php if ($selected_chat && $selected_chat->id == $chat->id) echo 'active'; ?>"
                   href="<?php echo erLhcoreClassDesign::baseurl('site_admin/koshopchat/dashboard')?>/(cid)/<?php echo (int)$chat->id?>">
                    <span class="kw-status"><?php echo koshop_h(koshop_status_text($chat)); ?></span>
                    <b><?php echo koshop_h($chatTitle); ?></b>
                    <small><?php echo koshop_h($lastMsg); ?></small>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="kw-col kw-center">
        <?php if ($selected_chat) : ?>
            <?php
                $selectedTitle = koshop_prop($selected_chat, 'nick', '');
                if ($selectedTitle === '') $selectedTitle = '访客 #' . $selected_chat->id;
            ?>
            <div class="kw-head">
                <h3><?php echo koshop_h($selectedTitle); ?></h3>
                <p>会话 #<?php echo (int)$selected_chat->id; ?> · 使用 Live Helper Chat 原权限与会话机制</p>
            </div>

            <div class="kw-card">
                <h3>会话已选中</h3>
                <p>点击下方按钮进入原生实时会话视图进行回复、转接、上传文件或结束会话；返回即可继续切换其他买家。</p>
                <div class="kw-actions">
                    <a class="kw-btn" href="<?php echo erLhcoreClassDesign::baseurl('chat/adminchat')?>/<?php echo (int)$selected_chat->id; ?>">进入会话并回复</a>
                    <a class="kw-btn alt" href="<?php echo erLhcoreClassDesign::baseurl('front/default')?>">原客服后台</a>
                </div>
            </div>
        <?php else : ?>
            <div class="kw-empty">
                <h3>当前没有咨询</h3>
                <p>新消息到达后会显示在左侧列表。</p>
            </div>
        <?php endif; ?>
    </section>

    <aside class="kw-info">
        <h3>买家信息</h3>
        <?php if ($selected_chat) : ?>
            <dl>
                <dt>访客 ID</dt>
                <dd>#<?php echo (int)$selected_chat->id; ?></dd>

                <dt>IP</dt>
                <dd><?php echo koshop_h(koshop_prop($selected_chat, 'ip')); ?></dd>

                <dt>浏览页面</dt>
                <dd><?php echo koshop_h(koshop_prop($selected_chat, 'current_page')); ?></dd>

                <dt>访客来源</dt>
                <dd><?php echo koshop_h(koshop_prop($selected_chat, 'referrer')); ?></dd>

                <dt>订单信息</dt>
                <dd>可按手机号、邮箱、订单号或兑换码查询（待接入 Dujiao API）</dd>
            </dl>
        <?php else : ?>
            <p>选择会话后显示买家信息。</p>
        <?php endif; ?>
    </aside>
</div>
