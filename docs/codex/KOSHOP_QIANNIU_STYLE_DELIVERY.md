# Koshop 电商卖家工作台二开交付说明

## 本次实现范围

### Dujiao-Next 买家前台

- 新增统一客服按钮，默认打开 `https://kefu.cn12.vip/index.php/chat/start`，可通过 `VITE_CUSTOMER_SERVICE_URL` 覆盖。
- 首页与个人中心右上角显示客服入口；商品详情购买区与移动端固定购买栏均按“客服、加入购物车、立即购买”排列。
- 移动端底部导航只在 `/`、`/products`、`/cart`、`/auth/login`、`/me` 显示。
- 其他二级或动态详情页面显示通用返回按钮；优先调用浏览器历史返回，无历史时回首页。

### 统一卖家后台

新增独立应用 `apps/koshop-seller-console`，不替换 Dujiao-Next Admin 或 Live Helper Chat Admin。第一阶段提供：

- 原创电商卖家工作台 UI，包含桌面侧栏和移动端菜单。
- 工作台经营指标、待办事项与 Admin API 看板读取能力。
- 商品、订单、交易/财务、系统设置的统一入口。
- Live Helper Chat 客服后台 iframe 接入和新窗口打开入口。
- 所有地址通过环境变量配置；浏览器代码不保存数据库密码或 API secret。

> 当前仓库没有 `third_party/live-helper-chat` 源码，因此本次没有修改 Live Helper Chat 核心、模板或主题文件。客服接待先按需求允许的第一阶段 iframe 方式集成。后续把 Live Helper Chat 源码加入仓库后，可在模块/主题层继续开发三栏客服工作台和买家聊天主题。

## 构建与运行

### 买家前台

```bash
cd third_party/dujiao-next/user
cp .env.example .env
npm ci
npm run dev
# 生产构建
npm run build
```

### 统一卖家后台

```bash
cd apps/koshop-seller-console
cp .env.example .env
# 按实际部署填写 Dujiao API、Admin 和 Live Helper Chat 地址
npm install
npm run dev
# 生产构建
npm run build
```

统一后台请求 `/admin/dashboard/overview` 时使用 `credentials: include`，需要 API 允许统一后台域名跨域并允许携带登录 Cookie。涉及新增、编辑、补发、退款等写操作，第一阶段跳转原 Admin 后台安全执行。

## 部署

### 买家前台

1. 配置 `VITE_CUSTOMER_SERVICE_URL`。
2. 执行 `npm ci && npm run build`。
3. 将 `dist/` 发布到商城前台静态站点。
4. 对 SPA 路由启用 `try_files $uri $uri/ /index.html`。

### Live Helper Chat

本次不修改 Live Helper Chat 文件。确保 `VITE_LHC_WORKBENCH_URL` 指向可登录的客服后台会话页，并检查目标站点的 `X-Frame-Options` / CSP `frame-ancestors` 是否允许统一后台域名嵌入；若不允许，使用工作台中的“新窗口打开”。

### 统一卖家后台

1. 从 `.env.example` 创建生产环境变量文件。
2. 执行 `npm install && npm run build`。
3. 将 `dist/` 发布到独立域名。
4. 配置 HTTPS、SPA fallback，以及 Dujiao Admin API 的 CORS/Cookie 策略。

## 回滚

- 买家前台：回滚本次提交并重新构建发布 `third_party/dujiao-next/user`。
- 统一卖家后台：停止其独立站点或将域名切回旧版本；原 Dujiao-Next Admin 与 Live Helper Chat Admin 始终保留，不受影响。
- 环境变量回滚不会影响数据库结构，本次没有数据库迁移。

## 验收测试清单

1. 手机打开商城首页，确认右上角客服入口可打开聊天。
2. 打开个人中心首页，确认右上角客服入口可用。
3. 打开商品详情，确认客服、加入购物车、立即购买同一行，且库存、禁用和购买逻辑仍有效。
4. 从买家客服页面发起咨询，并验证离线留言、部门分配和在线状态等原能力。
5. 打开统一卖家后台客服接待页，确认可嵌入或新窗口打开 Live Helper Chat。
6. 在 Live Helper Chat 中切换咨询、回复买家，并确认买家收到回复。
7. 手机访问顶级页面，确认底部导航显示。
8. 手机访问商品详情、订单详情、结算、支付、博客详情等二级页面，确认底部导航隐藏且左上角返回按钮可用。
9. 配置 Admin API 后刷新统一后台，确认经营指标可读取；未配置或未登录时应显示明确提示。
