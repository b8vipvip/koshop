# Codex task: koshop phase 2 - real Live Helper Chat customization and unified seller console

## Background

The first Codex delivery finished part of the Dujiao-Next buyer storefront work and created a first-stage `apps/koshop-seller-console`.

However, the first delivery explicitly did **not** modify Live Helper Chat core/templates/themes because the repository did not contain `third_party/live-helper-chat`. Now the Live Helper Chat source has been imported from the running US02T server into:

- `third_party/live-helper-chat`

The unified seller console domain has also been added in Cloudflare Tunnel:

- `https://sell.cn12.vip`

Current domains:

- Buyer storefront: `https://shop.cn12.vip`
- Dujiao-Next Admin: `https://admin.cn12.vip`
- Live Helper Chat: `https://kefu.cn12.vip`
- Unified seller console: `https://sell.cn12.vip`

Important legal/UI rule:

Do not copy Taobao/Qianniu trademarks, logos, copyrighted icons, exact assets, or pixel-perfect UI. Use common e-commerce seller chat and seller workstation interaction patterns as references only. Build an original Koshop UI.

---

## High-priority fixes from review

1. Fix `README.md`: remove old Chatwoot references and use Live Helper Chat everywhere.
2. Ensure `third_party/live-helper-chat` source is truly present and used for customization.
3. Do not keep Live Helper Chat as an iframe-only integration. Add a real e-commerce customer service theme/module.
4. Build a real buyer chat UI, not just the default Live Helper Chat page.
5. Build a real operator/backend three-column chat workstation, not just the default admin iframe.
6. Enhance `apps/koshop-seller-console` so product/order/transaction/finance operations go through a server-side API/proxy, not only links to the original admin.

---

## Part 1: Full Chinese localization for Live Helper Chat

Goal:

Make Live Helper Chat fully Chinese for the Koshop deployment.

Requirements:

1. Default visitor language should be Simplified Chinese.
2. Default operator/admin language should be Simplified Chinese.
3. Audit visible strings in the customized buyer chat theme and seller chat workstation:
   - Buttons
   - Empty states
   - Error messages
   - Online/offline status
   - New message indicators
   - System notices
   - Quick reply UI
   - Attachment/file UI
   - End chat / transfer / mark done UI
4. Replace hard-coded English in customized Koshop templates with Chinese.
5. Use Live Helper Chat translation conventions where possible.
6. Keep fallback English translations where practical, but the Koshop default must be Chinese.
7. Do not break the existing Live Helper Chat original admin. It can remain available as a fallback.

Deliverables:

- List all language/translation files changed or added.
- List all templates/components where English was replaced.
- Explain how to switch language if needed.

---

## Part 2: Live Helper Chat buyer-side e-commerce chat UI

Target source:

- `third_party/live-helper-chat`

Goal:

Create a buyer chat interface similar to modern e-commerce app store-customer-service chat, but with original Koshop visual design.

Required behavior:

1. Support full-page chat URL and embedded widget.
2. Mobile-first layout.
3. Top bar:
   - Back button
   - Store/service title, e.g. “店铺客服”
   - Online/offline status
4. Message list:
   - Buyer messages on the right
   - Operator messages on the left
   - System messages centered or subtle
   - Time separators
   - Auto-scroll to latest message
5. Bottom input bar:
   - Text input
   - Send button
   - Image/file attachment if Live Helper Chat supports it
   - Emoji/quick phrase entry if feasible
6. Offline mode:
   - If no operator online, show Chinese offline message/leave-message form.
7. Keep all Live Helper Chat core features:
   - Start chat
   - Send/receive messages
   - Operator reply
   - Department routing
   - Offline message
   - Existing permissions
   - Existing session handling

Implementation preference:

- Prefer Live Helper Chat theme/template/module extension approach.
- Avoid deep core modifications unless necessary.
- If core changes are unavoidable, document each changed file and why.

Suggested naming:

- Theme/module name can be `koshop_ecommerce_chat` or `koshop_chat`.

Acceptance tests:

1. Open `https://kefu.cn12.vip/index.php/chat/start`.
2. Buyer sees Chinese e-commerce chat UI.
3. Buyer sends a message.
4. Operator receives it.
5. Operator replies.
6. Buyer receives reply.
7. Offline status displays a Chinese leave-message flow.

---

## Part 3: Live Helper Chat operator three-column seller chat workstation

Target source:

- `third_party/live-helper-chat`

Goal:

Create a seller-style customer service workstation for operators.

Do not rely on iframe as the final solution.

Required route:

- Add a new route if possible, for example:
  - `/index.php/site_admin/koshopchat/dashboard`
  - or another route following Live Helper Chat module conventions

Layout requirements:

Desktop:

1. Left column: buyer consultation list
   - Visitor nickname / visitor ID
   - Last message
   - Unread count
   - Time
   - Status: waiting / active / ended / offline
   - Search/filter
2. Center column: active chat
   - Message bubbles
   - Time
   - Input box
   - Send button
   - Quick replies
   - Emoji/file/image if existing support allows
   - End chat / transfer / mark done if existing support allows
3. Right column: buyer info panel
   - Visitor IP
   - Current page
   - Referrer
   - Browser/device
   - Previous chats
   - Placeholder for Dujiao order info
   - Placeholder for phone/order/redeem-code lookup

Responsive requirements:

- Desktop: 3 columns
- Tablet: list + chat
- Mobile: consultation list page and chat detail page switch

Functional requirements:

1. One operator must be able to handle multiple buyers.
2. Clicking a buyer opens that conversation.
3. New messages update the list.
4. Unread count should work.
5. Existing Live Helper Chat permissions and authentication must be respected.
6. Original Live Helper Chat admin must remain available as fallback.

Acceptance tests:

1. Open new Koshop operator route after login.
2. See buyer consultation list.
3. Click buyer.
4. Send reply.
5. Buyer receives reply.
6. Multiple active buyers can be switched from the list.

---

## Part 4: Improve `apps/koshop-seller-console`

Target source:

- `apps/koshop-seller-console`

Goal:

Turn the first-stage unified seller console from “navigation shell + iframe” into a real seller backend.

Domain:

- Production/test domain: `https://sell.cn12.vip`

Important security rule:

Browser code must not contain database passwords, payment secrets, API secrets, or admin tokens.

Required architecture:

1. Keep the Vue/Vite frontend.
2. Add a server-side proxy/BFF layer for seller console APIs.
   - Suggested path: `apps/koshop-seller-console/server`
   - Suggested local port: `127.0.0.1:18100`
3. Frontend calls:
   - `/api/koshop-seller/...`
4. Server-side proxy talks to:
   - Dujiao-Next Admin/API
   - Live Helper Chat API/module endpoints
5. Secrets must be read from server-side environment variables only.
6. Provide `.env.example` for both frontend and server.

Required modules:

### Dashboard

- Today orders
- Today paid amount
- Pending orders
- Low-stock virtual goods
- Active chats
- Pending replies
- Recent order list
- Recent chat list

### Products

- Product list from Dujiao-Next
- Search/filter
- Product status
- Stock/card-secret summary
- Entry for create/edit product
- If write APIs are safe, implement create/edit; otherwise proxy to original Admin with clear status

### Orders

- Order list from Dujiao-Next
- Payment status
- Fulfillment status
- Buyer identifier if available
- Order detail
- Manual fulfillment/reissue/refund if safe APIs exist

### Transactions / Finance

- Payment records
- Wallet recharge records
- Refund records
- Revenue/cost/profit overview if fields exist
- CSV export if feasible

### Customer service

- Integrate with the new Live Helper Chat Koshop operator route/module.
- Do not use iframe as the only final solution.
- Display buyer consultation list and active chat inside seller console or deep-link to the new LHC workstation.
- Add placeholders to link buyer chat with Dujiao orders by phone/email/order number.

### Settings

- Site settings shortcuts
- Payment settings shortcuts
- Customer service settings shortcuts
- Environment/status check page

Acceptance tests:

1. Open `https://sell.cn12.vip`.
2. Seller console loads.
3. Dashboard loads real or clearly explained data.
4. Product module can read product list.
5. Order module can read order list.
6. Finance module can read payment/recharge/refund data or clearly reports missing API.
7. Customer service module opens the new Koshop LHC workstation, not just the old iframe.
8. No secret appears in built frontend files.

---

## Part 5: Verify previous Dujiao-Next storefront work

Target source:

- `third_party/dujiao-next/user`

Previous work should be kept and verified:

1. Mobile bottom nav only appears on:
   - `/`
   - `/products`
   - `/cart`
   - `/auth/login`
   - `/me`
2. Mobile bottom nav is hidden on:
   - product detail
   - checkout
   - payment
   - order detail
   - recharge order detail
   - blog detail
   - guest order detail
   - profile/security/orders/wallet subpages
3. Mobile hidden-nav pages must have a top-left back button.
4. Buyer home page and personal center must have customer service entry.
5. Product detail action area must be one row:
   - 客服
   - 加入购物车
   - 立即购买
6. Mobile fixed purchase bar must keep the same logic and not wrap badly.

If any bug is found, fix it.

---

## Deployment notes

Current deployed services on US02T:

- Dujiao-Next Docker containers:
  - API: `127.0.0.1:8080`
  - User: `127.0.0.1:8081`
  - Admin: `127.0.0.1:8082`
- Live Helper Chat:
  - Nginx + PHP-FPM + MariaDB
  - Path: `/var/www/livehelperchat`
- Seller console target:
  - Domain: `https://sell.cn12.vip`
  - Cloudflare Tunnel routes this hostname to US02T localhost:80

When implementing deployment changes, provide:

1. Build commands.
2. Nginx config for `sell.cn12.vip`.
3. Backend proxy systemd service if added.
4. Rollback steps.
5. Test checklist.

---

## Required final response from Codex

After finishing changes, provide:

1. Summary of completed work.
2. List of changed files.
3. Which requirements are fully completed.
4. Which requirements are partial and why.
5. Build commands.
6. Deployment commands.
7. Rollback commands.
8. Manual test checklist.
