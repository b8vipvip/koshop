# Koshop Phase 2 部署与验收

## 已落地范围
- Live Helper Chat 默认站点语言改为简体中文，英文站点入口继续保留。
- `customtheme` 增加买家全页聊天和小组件共用的 Koshop 电商聊天样式。
- 新增真实 LHC 模块路由：`/index.php/site_admin/koshopchat/dashboard`。模块读取 LHC 会话并遵守 `hasAccessToRead` 权限，实时回复仍复用原生会话页。
- 卖家后台浏览器只调用同源 `/api/koshop-seller/*`；BFF 从服务端环境变量读取上游地址与令牌。
- 商品、订单、财务写操作继续使用原 Dujiao Admin 作为安全兜底；不同 Dujiao 版本的 API 路径需在环境变量中确认。

## 构建与部署
```bash
cd /var/www/koshop/apps/koshop-seller-console
npm ci
npm run build
sudo install -d -m 750 /etc/koshop
sudo cp server/.env.example /etc/koshop/seller-bff.env
sudo editor /etc/koshop/seller-bff.env
sudo cp ../../deploy/systemd/koshop-seller-bff.service /etc/systemd/system/
sudo cp ../../deploy/nginx/sell.cn12.vip.conf /etc/nginx/conf.d/
sudo systemctl daemon-reload
sudo systemctl enable --now koshop-seller-bff
sudo nginx -t && sudo systemctl reload nginx
```
把 `third_party/live-helper-chat/lhc_web` 同步到 LHC 部署目录后清理模板缓存。给客服角色授予 `lhkoshopchat/use` 权限；超级管理员可先验证。生产 `settings.ini.php` 若已存在，还需将默认 locale/site access 调整为 `zh_CN`/`chn`，因为仓库改动的是安装默认配置。

## 回滚
```bash
git revert <phase2-commit>
sudo systemctl disable --now koshop-seller-bff
sudo rm -f /etc/nginx/conf.d/sell.cn12.vip.conf /etc/systemd/system/koshop-seller-bff.service
sudo systemctl daemon-reload
sudo nginx -t && sudo systemctl reload nginx
```

## 手工验收清单
1. 打开 `/index.php/chat/start`，确认默认中文并验证全页/小组件、在线/离线留言、买家和客服消息。
2. 客服登录后打开 `/index.php/site_admin/koshopchat/dashboard`，切换多条咨询，并进入原生会话回复、转接、上传文件、结束会话。
3. 打开 `https://sell.cn12.vip`，确认看板、商品、订单、财务接口的真实数据或明确的不可用提示。
4. 检查浏览器构建产物，不应出现数据库密码、支付密钥、Admin/LHC token。
5. 验证原 Dujiao Admin 与原 Live Helper Chat Admin 仍可正常访问。
