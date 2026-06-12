# koshop

koshop 是独立于 koko 的虚拟商品商城与客服系统源码仓库。

## 当前线上测试域名

- 商城前台：https://shop.cn12.vip
- 商城后台：https://admin.cn12.vip
- 客服系统：https://kefu.cn12.vip
- 统一卖家后台：https://sell.cn12.vip

## 技术组合

- Dujiao-Next：商城、会员、钱包、支付、自动发货
- Live Helper Chat：在线客服，一个客服接待多个买家
- Koshop Seller Console：统一卖家工作台

## 目录说明

- third_party/dujiao-next/api：Dujiao-Next 商城后端 API
- third_party/dujiao-next/user：Dujiao-Next 买家前台
- third_party/dujiao-next/admin：Dujiao-Next 原管理后台
- third_party/live-helper-chat：Live Helper Chat 客服系统源码
- apps/koshop-seller-console：统一卖家后台
- docs/codex：Codex 二开任务说明
- deploy：部署脚本、docker-compose、Nginx 配置

## 二开原则

- 不再使用 Chatwoot。
- Live Helper Chat 必须从 iframe 临时集成升级为主题/模块级深度集成。
- 统一卖家后台通过服务端代理 API 对接 Dujiao-Next 和 Live Helper Chat。
- 不允许把数据库密码、支付密钥、SSH key、证书文件提交到 GitHub。
