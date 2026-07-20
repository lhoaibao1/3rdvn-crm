# 3RDVN CRM UI Pack - TailAdmin x Flowbite Style

Copy đè vào Laravel project.

## Cài nếu chưa có auth/role

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

## Copy file

Copy các thư mục:

```txt
resources/
public/
```

vào project Laravel.

## Nếu đã dùng file routes/controllers cũ tao gửi

Gói này chủ yếu thay UI:
- Login
- Layout
- Sidebar
- Topbar
- Dashboard
- CSS

## Sau khi copy

```bash
php artisan optimize:clear
npm run build
php artisan serve
```

Mở:

```txt
/login
/dashboard
```
