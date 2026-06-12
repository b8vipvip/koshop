import http from 'node:http';
import { URL } from 'node:url';

const env = process.env;
const host = env.HOST || '127.0.0.1';
const port = Number(env.PORT || 18100);
const routes = {
  '/api/koshop-seller/dashboard': ['dujiao', env.DUJIAO_DASHBOARD_PATH || '/api/v1/admin/dashboard/overview'],
  '/api/koshop-seller/products': ['dujiao', env.DUJIAO_PRODUCTS_PATH || '/api/v1/admin/products'],
  '/api/koshop-seller/orders': ['dujiao', env.DUJIAO_ORDERS_PATH || '/api/v1/admin/orders'],
  '/api/koshop-seller/finance': ['dujiao', env.DUJIAO_FINANCE_PATH || '/api/v1/admin/payments'],
  '/api/koshop-seller/chats': ['lhc', env.LHC_CHATS_PATH || '/index.php/restapi/onlinechats'],
};
const config = {
  dujiao: {base: env.DUJIAO_API_BASE, token: env.DUJIAO_ADMIN_TOKEN},
  lhc: {base: env.LHC_API_BASE, token: env.LHC_API_TOKEN},
};
const json = (res, status, body) => { res.writeHead(status, {'content-type':'application/json; charset=utf-8','cache-control':'no-store'}); res.end(JSON.stringify(body)); };
const health = () => ({ok:true, service:'koshop-seller-bff', integrations:{dujiao:Boolean(config.dujiao.base), lhc:Boolean(config.lhc.base)}});

async function proxy(req, res, key, path, incoming) {
  const target = config[key];
  if (!target.base) return json(res, 503, {ok:false, available:false, source:key, message:`尚未配置 ${key.toUpperCase()} 服务端地址`});
  const url = new URL(path, target.base.endsWith('/') ? target.base : `${target.base}/`);
  incoming.searchParams.forEach((value, name) => url.searchParams.append(name, value));
  const headers = {'accept':'application/json'};
  if (target.token) headers.authorization = `Bearer ${target.token}`;
  try {
    const upstream = await fetch(url, {headers, signal:AbortSignal.timeout(8000)});
    const text = await upstream.text();
    let data; try { data = JSON.parse(text); } catch { data = {message:text.slice(0,500)}; }
    json(res, upstream.ok ? 200 : 502, {ok:upstream.ok, available:upstream.ok, source:key, status:upstream.status, data});
  } catch (error) {
    json(res, 502, {ok:false, available:false, source:key, message:'上游服务暂时不可用', detail:error.message});
  }
}

http.createServer(async (req, res) => {
  const incoming = new URL(req.url || '/', `http://${req.headers.host || 'localhost'}`);
  if (req.method === 'GET' && incoming.pathname === '/api/koshop-seller/health') return json(res, 200, health());
  const route = routes[incoming.pathname];
  if (req.method === 'GET' && route) return proxy(req, res, route[0], route[1], incoming);
  json(res, 404, {ok:false, message:'接口不存在'});
}).listen(port, host, () => console.log(`Koshop seller BFF listening on http://${host}:${port}`));
