# sso_hub_server
 
init sso server

1. key generate
    - `php artisan key:generate` and `php artisan passport:keys`

2. create client
    - `php artisan passport:client`

Command : 
- `php artisan update:database-normalize`
- `php artisan update:coursetype`
- `php artisan update:usercourse`

Fetch data command:
- `php artisan dtms:sync` (DTMS, ทุก 1 นาทีผ่าน scheduler; `--dry-run`, `--retry-failures`)
- `php artisan dtms:branches` / `php artisan dtms:map-branch {dtms_code} {brn_id}` (จับคู่สาขา DTMS กับสาขา SSO)
- `php artisan fetch:posdata` (PoS)
