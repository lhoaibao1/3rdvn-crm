# 3RDVN CRM - Handoff Notes

Ngay cap nhat: 2026-06-28
May chu: VPS production
Thu muc app: `/var/www/3rdvn-crm`
URL hien tai: `http://103.216.118.24`
Login route: `/authen/login`
Stack: Laravel 13.17, PHP 8.3, PostgreSQL, Filament v5

> Luu y bao mat: khong luu mat khau, token, `.env` secret vao file nay. Neu can credential, hoi owner.
> Yeu cau cua owner: thao tac tren VPS, khong ghi file/code vao Mac local.

## Nguyen tac san pham

CRM nay thay the cac huong cu da bo:

- Khong dung NocoBase nua.
- Khong dung Logto nua.
- Khong dung Supabase nua.
- Khong dung demo/fake data.
- Database that tren VPS bang PostgreSQL.
- UI can theo huong CRM/ERP gon, ro, uu tien nghiep vu sale/quan ly.

## Hien trang he thong

Da build CRM Laravel + Filament, chay tren VPS tai `/var/www/3rdvn-crm`.

Cac chuc nang chinh dang co:

- Login custom tai `/authen/login`.
- Filament admin panel tai root `/` sau khi login.
- Module nguoi dung co nhieu field nhan su/sale.
- Phan quyen theo cap quan ly: Admin, ZD, AM, Team Leader, Direct Sale, Telesale, CTV.
- Quan ly topbar/sidebar/theme/login settings o muc co ban.
- Upload logo/favicon trong settings da tung lam, khong dung URL thuan cho branding.
- User menu topbar chi giu `Thay doi mat khau` va `Dang xuat`.
- Co trang `Thay doi mat khau` rieng.
- Co `Sales Channel` va `Sales Project` rieng.

## Login / Topbar / Layout

Da lam:

- Login route dung `/authen/login`, khong dung `/signin`.
- Login custom 2 buoc:
  - Buoc nhap `User/UID`.
  - Sau khi tim thay user thi hien loi chao + nhap mat khau.
  - Co `Quay lai`, `Quen mat khau?`, `Quen ten dang nhap?`.
- Topbar user menu:
  - `Thay doi mat khau`
  - `Dang xuat`
- Da an cac muc khong can thiet trong user menu.
- Da them Change Password page.

File lien quan:

- `app/Providers/Filament/AdminPanelProvider.php`
- `app/Filament/Pages/Auth/Login.php`
- `resources/views/filament/pages/auth/login.blade.php`
- `app/Filament/Pages/ChangePassword.php`
- `resources/views/filament/pages/change-password.blade.php`

Dang do / can tiep tuc:

- Login UI owner van chua that su hai long. Can polish lai neu co design moi.
- Can kiem tra lai mobile/responsive login.
- Forgot password/forgot username can noi luong gui mail/SMS that neu owner yeu cau.

## User module

Da lam user module co cac nhom thong tin:

- Ho so chinh: ho ten, UID, Employee Code, email, SDT, CCCD/CMND/ho chieu, ngay sinh, gioi tinh, noi cap/ngay cap.
- Cong viec: phong ban, trang thai, ngay vao lam, office, contract type.
- Du an & kenh: du an ban hang, ma ban hang theo tung du an, cong ty/chi nhanh/kenh.
- Thong tin nguoi quan ly: ZD, AM, Team Leader.
- Dia chi: dia chi chi tiet, tinh/huyen/xa.
- Ngan hang: bank, STK, ten chu TK, chi nhanh.
- Thue/bao hiem/lien he khan cap.
- Phan quyen: password khi tao, role duy nhat.

Quan trong:

- `UID` va `Employee Code` tu sinh, bi khoa tren form, non-admin khong duoc sua.
- Role chi chon 1.
- `position/chuc danh` nham nho da bo ve logic role chinh.
- Ngay thang hien theo `dd/mm/yyyy` trong form.
- Form tao/sua/view da tach tabs.
- List user da co bo loc, columns, action tao/xuat bao cao o muc co ban.

File lien quan:

- `app/Models/User.php`
- `app/Filament/Resources/Users/UserResource.php`
- `app/Filament/Resources/Users/Schemas/UserForm.php`
- `app/Filament/Resources/Users/Schemas/UserInfolist.php`
- `app/Filament/Resources/Users/Tables/UsersTable.php`
- `app/Filament/Resources/Users/Pages/CreateUser.php`
- `app/Filament/Resources/Users/Pages/EditUser.php`
- `app/Filament/Resources/Users/Pages/ViewUser.php`
- `app/Support/UserSpecOptions.php`

Dang do / can tiep tuc:

- UI view/create/edit user owner van chua hoan toan ung y. Dang tam quay ve style gon, chia tab.
- Can tiep tuc chuan hoa list/table UX: sticky filter, scroll columns rieng, action button, color.
- Can them audit log cho thay doi user/manager/role.
- Can them import/export Excel that cho user neu owner yeu cau.
- Can tach Office thanh CRUD rieng neu khong muon dung lookup/options.

## Phan quyen va cay quan ly

Role hierarchy hien tai:

```text
Admin
  -> ZD
      -> AM
          -> Team Leader
              -> Direct Sale / Telesale / CTV
```

Rule tao user hien tai:

- Admin tao duoc: Admin, ZD, AM, Team Leader, Direct Sale, Telesale, CTV.
- ZD tao duoc: AM, Team Leader, Direct Sale, Telesale, CTV.
- AM tao duoc: Team Leader, Direct Sale, Telesale, CTV.
- Team Leader tao duoc: Direct Sale, Telesale, CTV.
- Direct Sale/Telesale/CTV khong tao user.

Rule sua user hien tai:

- ZD/AM/Team Leader duoc sua user duoi nhanh cua minh.
- ZD/AM/Team Leader duoc sua thong tin cua chinh minh.
- Direct Sale/Telesale/CTV khong sua user/self trong module user.
- Non-admin khi sua khong duoc doi role.
- Non-admin khong duoc sua cac field nhay cam:
  - `uid`
  - `employee_code`
  - `password`
  - `email_verified_at`
- Khi sua user duoi nhanh, duoc map lai quan ly trong pham vi tuyen hop le.

Manager mapping:

- `zd_id`: ZD quan ly.
- `am_id`: AM quan ly.
- `team_leader_id`: Team Leader quan ly.
- Tao sale role bat buoc co Team Leader.
- Chon Team Leader se tu suy ra AM/ZD.
- AM chi map vao Team Leader thuoc AM do.
- ZD chi map trong tuyen cua ZD do.

File lien quan:

- `app/Support/RoleHierarchy.php`
- `app/Policies/UserPolicy.php`
- `app/Policies/RolePolicy.php`
- `app/Filament/Resources/Roles/RoleResource.php`

Test rollback da chay thanh cong:

```text
ZD update self=yes
AM update self=yes
Team Leader update self=yes
Direct Sale update self=no
ZD update own sale=yes
ZD update other sale=no
AM update own sale=yes
AM update other sale=no
TL update own sale=yes
TL update other sale=no
AM change sale role=no
protected keys kept=name
```

Dang do / can tiep tuc:

- Can viet PHPUnit/Pest test that cho RoleHierarchy va UserPolicy, hien moi test bang script rollback thu cong.
- Can audit log khi doi manager/role.
- Can xem lai viec Admin co duoc tao Admin khac hay khong, owner chua chot.

## Dự án bán hàng - phần vừa sửa quan trọng

Van de cu:

- Form user truoc do lay `CrmModule` lam `Dự án bán hàng`, sai ban chat.
- `CrmModule` la module/man hinh/chuc nang, khong phai du an kinh doanh.
- Owner noi ro: sau nay co `Lead Lotte` la du an moi, user duoc add du an do thi moi co quyen truy cap/su dung.

Da sua:

- Tao bang rieng `sales_projects`.
- Tao model `SalesProject`.
- Tao Filament resource quan tri `Dự án bán hàng`.
- User form `Dự án bán hàng` lay tu `sales_projects`, khong lay tu `crm_modules` nua.
- Moi SalesProject co the gan voi 1 CrmModule.
- Module nao co project active gan vao thi user phai co it nhat 1 project cua module do moi thay/dung module.
- Neu module chua co project active thi van chay theo permission cu de khong lam vo he thong hien tai.

Route quan tri du an:

```text
/sales-projects
/sales-projects/create
/sales-projects/{record}
/sales-projects/{record}/edit
```

Cach dung dung cho vi du `Lead Lotte`:

1. Vao `Dự án bán hàng`.
2. Tao project:
   - Ten du an: `Lead Lotte`
   - Ma du an/slug: `lead-lotte`
   - Module su dung: `Lead`
   - Bat `Dang dung`
3. Vao User.
4. Tab `Dự án & kênh`.
5. Add `Lead Lotte` cho user.
6. User do moi thay/dung module Lead neu module Lead dang bi rang buoc boi project active.

File moi/sua lien quan:

- `database/migrations/2026_06_28_090000_create_sales_projects_table.php`
- `app/Models/SalesProject.php`
- `app/Policies/SalesProjectPolicy.php`
- `app/Support/Permissions/SalesProjectAccess.php`
- `app/Filament/Resources/SalesProjects/SalesProjectResource.php`
- `app/Filament/Resources/SalesProjects/Schemas/SalesProjectForm.php`
- `app/Filament/Resources/SalesProjects/Schemas/SalesProjectInfolist.php`
- `app/Filament/Resources/SalesProjects/Tables/SalesProjectsTable.php`
- `app/Filament/Resources/SalesProjects/Pages/*`
- `app/Models/CrmModule.php`
- `app/Support/UserSpecOptions.php`
- `app/Support/Permissions/RoleAccess.php`
- `app/Support/Filament/ModuleNavigation.php`
- `app/Policies/LeadPolicy.php`
- `app/Filament/Resources/Users/Schemas/UserForm.php`
- `app/Filament/Resources/Users/UserResource.php`

Permission moi:

```text
sales_project.view
sales_project.create
sales_project.update
sales_project.delete
```

Da cap cho Admin.

Test rollback da chay thanh cong:

```text
without project navigation=no
without project role access=no
without project lead policy=no
with project navigation=yes
with project role access=yes
with project lead policy=yes
can access project helper=yes
```

Dang do / can tiep tuc rat quan trong:

- Moi chi gate module Lead theo project. Lead records chua co `sales_project_id` hoac `sales_project_slug`.
- Neu co nhieu project cung dung module Lead, user co `Lead Lotte` co the vao module Lead, nhung bang Lead chua filter row theo project.
- Can them field vao leads:
  - `sales_project_id` nullable/foreign key hoac `sales_project_slug`.
  - form Lead bat buoc chon project ma user duoc gan.
  - query ListLeads filter theo project cua user.
  - policy view/update/delete check record project.
- Can ap dung pattern project-aware cho `SaleProfile`, approval, API mapping neu cac module do cung chia theo du an.
- Can them UI hien project trong Lead list/form.
- Can them index DB cho project filtering.

## CRM Modules

`CrmModule` hien dung de dieu khien menu/module UI:

- label
- slug
- icon
- route_name
- sort_order
- is_active
- required_roles
- required_permissions

Module khong con la du an ban hang.

File lien quan:

- `app/Models/CrmModule.php`
- `app/Filament/Resources/CrmModules/*`
- `app/Support/Filament/ModuleNavigation.php`
- `resources/views/partials/sidebar.blade.php`

Dang do:

- Can tao chuan mapping module <-> project <-> permission ro hon neu CRM co nhieu product/module sau nay.
- Can tranh de owner nham `Modules` voi `Dự án bán hàng` trong UI, co the doi label/module description.

## Sales Channel / Kênh bán hàng

Da co resource `SalesChannel`:

- company_name
- branch_name
- branch_code
- channel_name
- note
- is_active

Form user neu chon sales channel co san thi company/branch/code duoc fill va disable.

File lien quan:

- `app/Models/SalesChannel.php`
- `app/Filament/Resources/SalesChannels/*`

Dang do:

- Owner hoi Office/Cong ty/Van phong. Hien office van nam trong lookup/options, SalesChannel la kenh/chi nhanh.
- Neu can Office that, nen tao bang `offices` rieng va resource quan tri.

## UI Settings / Branding

Da lam o muc co ban:

- App name.
- Logo/fav upload path trong settings.
- Topbar height/settings.
- Sidebar width/collapsed width/settings.
- Theme color/font density mot phan.
- Runtime CSS trong `AdminPanelProvider` doc tu `UiSetting::current()`.

File lien quan:

- `app/Models/UiSetting.php`
- `app/Filament/Resources/UiSettings/*`
- `app/Providers/Filament/AdminPanelProvider.php`
- migrations lien quan ui_settings.

Dang do:

- Settings UI con can tach section ro hon.
- Preview font/theme can lam lai cho dep va realtime hon.
- Upload logo/favicon can test lai end-to-end theo UI hien tai.

## Realtime / Chat / Notifications

Da tung thu chat realtime nhung owner yeu cau xoa team chat.

Hien trang:

- Team chat da bo theo yeu cau owner.
- Filament database notifications dang bat theo setting.
- Chua co realtime enterprise hoan chinh cho moi thao tac.

Dang do:

- Neu owner muon realtime that, nen chon mot huong ro:
  - Laravel Reverb + Echo + broadcasting.
  - Hoac polling nhe cho cac bang quan trong.
- Can dinh nghia event nao can realtime: user created, approval, lead assigned, status changed.

## Database / migrations dang co

Cac migration dang co trong `database/migrations` gom nhieu phan:

- users/cache/jobs mac dinh Laravel.
- leads, sale_profiles, approval_logs, api_mappings.
- ui_settings.
- Spatie permissions.
- crm_modules, crm_teams.
- extend users/profile/settings.
- notifications.
- chat migrations cu, nhung chat UI da bo.
- user profile/address fields.
- crm_lookups.
- sales_projects moi.

Can can than:

- Khong co git repo trong `/var/www/3rdvn-crm` hien tai (`git status` bao khong phai git repo).
- Nen init git hoac dua code vao remote repo som de tranh mat code.

## Lenh van hanh hay dung

```bash
cd /var/www/3rdvn-crm
php artisan migrate --force
php artisan optimize:clear
systemctl restart php8.3-fpm
php artisan route:list --name=sales-project
php artisan about --only=environment
```

Lint nhanh:

```bash
cd /var/www/3rdvn-crm
php -l app/Support/RoleHierarchy.php
php -l app/Support/UserSpecOptions.php
php -l app/Support/Permissions/SalesProjectAccess.php
php -l app/Support/Permissions/RoleAccess.php
php -l app/Support/Filament/ModuleNavigation.php
php -l app/Policies/UserPolicy.php
php -l app/Policies/LeadPolicy.php
php -l app/Policies/SalesProjectPolicy.php
php -l app/Filament/Resources/Users/Schemas/UserForm.php
php -l app/Filament/Resources/Users/UserResource.php
```

## Viec nen lam tiep theo - uu tien cao

1. Them project field vao Lead records

Muc tieu:

- Lead nao thuoc du an nao phai luu trong DB.
- User chi thay lead cua project minh duoc gan.
- Admin thay tat ca.
- ZD/AM/TL van bi rang buoc theo nhanh quan ly + project.

De xuat:

- Migration them `sales_project_id` nullable vao `leads`.
- Relation `Lead::salesProject()`.
- Lead form select project:
  - Admin chon tat ca active projects gan module Lead.
  - Non-admin chi chon projects trong `user.sales_projects` va gan module Lead.
- Lead table query filter theo project va manager scope.
- LeadPolicy check record-level project.

2. Viet test tu dong cho permission

Can test:

- RoleHierarchy assignable.
- UserPolicy update/create/delete.
- SalesProjectAccess module gating.
- LeadPolicy project gating.

3. Audit log

Can log:

- Tao/sua user.
- Doi role.
- Doi manager chain.
- Gan/bo du an ban hang.
- Doi trang thai lead/profile.

4. Git/repo

Can init git hoac push repo:

```bash
cd /var/www/3rdvn-crm
git init
git add .
git commit -m "Initial Laravel CRM handoff"
```

Can kiem tra `.env`, `storage`, vendor, node_modules truoc khi add.

5. UI polish

Owner rat nhay voi UI. Can lam bang cach:

- Chot 1 design reference truoc.
- Lam tung man: login -> layout -> user list -> user view/edit -> lead.
- Browser screenshot truoc khi bao xong.

## Nhung dieu khong nen lam

- Khong gom `CrmModule` lam `Dự án bán hàng` nua.
- Khong tao fake/demo user/data neu owner khong yeu cau.
- Khong ghi code vao Mac local cua owner.
- Khong luu password/API key vao markdown/repo.
- Khong reset/xoa VPS neu owner khong noi ro.
- Khong sua UI bang cach copy y nguyen screenshot mau; chi lay concept.

## Trang thai kiem tra cuoi

Da chay:

- `php artisan migrate --force` cho `sales_projects`.
- Sync permission `sales_project.*` cho Admin.
- `php artisan optimize:clear`.
- Restart `php8.3-fpm`.
- `php artisan route:list --name=sales-project` thay 4 routes.
- Lint cac file PHP lien quan OK.
- Rollback DB test cho permission user OK.
- Rollback DB test cho sales project gating OK.

Ket luan:

Nen tiep tuc tu nen hien tai, khong quay lai NocoBase/Logto/Supabase. Phan dang can lam nhat la project-aware data model cho Lead va cac module nghiep vu, vi hien da co project gating o menu/policy module nhung record Lead chua gan project de filter du lieu chi tiet.
