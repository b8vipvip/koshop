# koshop

koshop 是独立于 koko 的虚拟商品商城与客服系统仓库。

## 目录说明

- third_party/dujiao-next/api：Dujiao-Next 商城后端 API
- third_party/dujiao-next/user：Dujiao-Next 用户前台
- third_party/dujiao-next/admin：Dujiao-Next 管理后台
- third_party/chatwoot：Chatwoot 客服系统
- deploy：部署脚本、docker-compose、Nginx 配置
- docs：二开说明、集成说明

## 和 koko 的关系

- koko：原来的兑换码/业务后端系统
- koshop：新的商城、钱包、自动发货、客服系统

两个项目独立仓库、独立部署，后续通过 API 打通。
