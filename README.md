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

Fetch data from PoS and EM command:
- `php artisan fetch:apidata idmskk` 
- `php artisan fetch:apidata idmsLLK` 
- `php artisan fetch:apidata idmsMK` 
- `php artisan fetch:apidata idmsPRO` 
- `php artisan fetch:apidata idmsPY` 
- `php artisan fetch:apidata idmsTK` 
- `php artisan fetch:posdata` 
