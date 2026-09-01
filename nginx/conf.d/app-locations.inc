    client_max_body_size 20M;

    location = /api/admin/login {
        limit_req zone=api_login burst=3 nodelay;
        proxy_pass http://laravel;
        include /etc/nginx/conf.d/proxy-laravel.conf;
    }

    location = /api/check-personal-number {
        limit_req zone=api_personal burst=2 nodelay;
        proxy_pass http://laravel;
        include /etc/nginx/conf.d/proxy-laravel.conf;
    }

    location ~ ^/api/orders/ {
        limit_req zone=api_orders burst=5 nodelay;
        proxy_pass http://laravel;
        include /etc/nginx/conf.d/proxy-laravel.conf;
    }

    # The only /api/ path served by Next.js rather than Laravel. This route
    # handler owns the first-party `dj_vote_token` httpOnly cookie and forwards
    # it to Laravel as the X-Vote-Token header — Laravel itself never sees the
    # cookie. An exact-match location outranks the `/api/` prefix below, which
    # would otherwise send the browser straight to Laravel and fail every vote
    # with 422 (the header only exists once Next.js has added it).
    location = /api/dj-vote {
        limit_req zone=api_general burst=20 nodelay;
        proxy_pass http://nextjs;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_http_version 1.1;
    }

    location /api/ {
        limit_req zone=api_general burst=20 nodelay;
        proxy_pass http://laravel;
        include /etc/nginx/conf.d/proxy-laravel.conf;
    }

    location /admin {
        proxy_pass http://laravel;
        include /etc/nginx/conf.d/proxy-laravel.conf;
    }

    location /sanctum/ {
        proxy_pass http://laravel;
        include /etc/nginx/conf.d/proxy-laravel.conf;
    }

    location /livewire {
        limit_req zone=admin_livewire burst=40 nodelay;
        proxy_pass http://laravel;
        include /etc/nginx/conf.d/proxy-laravel.conf;
    }

    location /css/filament/ {
        proxy_pass http://laravel;
        include /etc/nginx/conf.d/proxy-laravel.conf;
    }

    location /js/filament/ {
        proxy_pass http://laravel;
        include /etc/nginx/conf.d/proxy-laravel.conf;
    }

    location ~ ^/(brand-logo\.jpeg|favicon\.ico)$ {
        proxy_pass http://laravel;
        include /etc/nginx/conf.d/proxy-laravel.conf;
    }

    location /storage/ {
        alias /var/www/html/storage/app/public/;
        expires 30d;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    # pgAdmin is intentionally NOT exposed publicly. Reach it over an SSH tunnel:
    #   ssh -L 5050:pgadmin:5050 root@<server>   →   http://localhost:5050
    # (The pgadmin container still runs on the internal Docker network.)

    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
    }

    location / {
        proxy_pass http://nextjs;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }
