# sell.cn12.vip unified seller console deployment

Cloudflare Tunnel route has been created:

- sell.cn12.vip -> US02T localhost:80

Recommended Nginx route after building seller console:

```nginx
server {
    listen 80;
    server_name sell.cn12.vip;

    root /var/www/koshop-seller-console;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    # Future server-side proxy for seller console APIs.
    # Never expose DB credentials or API secrets to browser code.
    location /api/koshop-seller/ {
        proxy_pass http://127.0.0.1:18100/;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```
