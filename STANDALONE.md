# App2 CRM — bản độc lập, tạm dừng sử dụng

Mã nguồn được chụp từ app2 đang triển khai ngày 28/10/2026, giữ các thay đổi chưa commit trước đó. Đăng nhập và đăng xuất dùng tài khoản CRM trong database, không chuyển qua SSO. Cookie SSO không đăng nhập được vào CRM. Không cần Mail, FEOL, Guest hay LOS để mở trang đăng nhập.

## Khởi chạy trên máy riêng

Yêu cầu PHP 8.3+, Composer, PostgreSQL, Node.js theo package-lock.json. Dùng database, session cookie và APP_KEY riêng. Không dùng database HUB.

```sh
composer install --no-interaction
cp .env.example .env
php artisan key:generate
# Cấu hình DB_CONNECTION=pgsql và DB_* trong .env.
# Khôi phục database crm_laravel bằng pg_restore nếu cần dữ liệu cũ.
# Nếu khôi phục dữ liệu có cột mã hóa, giữ APP_KEY cũ từ bản lưu riêng.
# Với database mới: php artisan migrate --seed
npm ci --ignore-scripts
npm run build
php artisan storage:link
php artisan serve --host=127.0.0.1 --port=8080
```

Tài khoản cũ được giữ trong bản database riêng, không có mật khẩu mặc định trong Git. Quản trị viên cần đặt tài khoản hợp lệ nếu khởi tạo database trống. Cấu hình queue worker, scheduler, Reverb và SMTP riêng nếu bật các tính năng tương ứng. Các tích hợp đối tác vẫn cần khóa và endpoint của riêng chúng; không tự gọi được nếu chưa cấu hình. `MAIL_MAILER=log` không gửi email thật.

## Kiểm tra

```sh
php artisan test --filter=Standalone
```

Trên VPS cũ: app2 và CRM UAT được tạm dừng; HUB production và HUB UAT vẫn chạy. Nginx/service/crontab cũ và bản chụp source được giữ tại `/root/retired-projects-20261028`. Database, `.env`, APP_KEY và file upload được giữ riêng trên VPS, không đưa lên Git. Không chạy migrate:fresh, không tạo APP_KEY mới khi mở lại dữ liệu mã hóa cũ.

Khi khôi phục DB cũ, chạy `php artisan migrate` sau khi sao lưu để bổ sung các migration còn thiếu; không chạy migrate:fresh. Đã kiểm tra 12 test đăng nhập/đăng xuất với database SQLite cô lập, gồm đăng nhập Admin thành công, sai mật khẩu và tài khoản ngừng hoạt động bị chặn.
