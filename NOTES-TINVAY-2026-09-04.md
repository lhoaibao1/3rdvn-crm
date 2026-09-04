# Tin Vay — tracking & trạng thái (2026-09-04)

Ghi chú production CRM (`/var/www/3rdvn-crm`) + portal affiliate (`/opt/3rdvn-affiliate`).

## 1. Bỏ landing form, đi thẳng AccessTrade

Link pub: `https://3rdvn.io.vn/affiliate/tinvay?ref={MÃ_NV}`

- User thật (Safari, Chrome, Zalo in-app, Facebook in-app, WhatsApp in-app) → **302** thẳng
  `https://fast.accesstrade.com.vn/deep_link/v6/...`
- Bot preview (facebookexternalhit, Googlebot, ZaloShare, WhatsApp crawler) → trang OG, không nhảy

**Attribution không nhảy user.** Query trên deep link:

| Param | Giá trị |
|---|---|
| `sub1` / `aff_sub1` | mã NV (`RD…`) |
| `utm_content` | mã NV |
| `utm_source` | `3rdvn` |
| `utm_medium` | `affiliate` |
| `utm_campaign` | `tinvay` |
| `sub4` | `oneatweb` (sẵn trên tracking URL) |

`url_enc` vẫn decode ra `https://tinvay.vietcredit.com.vn/`. AccessTrade tự gắn `utm_source=accesstrade` trên dest.

**Lưu ý Zalo:** UA `Zalo iOS/…` không còn bị nhận nhầm bot. Trước đó Zalo bị kẹt landing, Safari thì skip — lệch tracking.

File: `app/Http/Controllers/AffiliateCampaignRedirectController.php`

## 2. “Tạm duyệt” đối tác = Đã giải ngân

AccessTrade Tin Vay:

- status `0` = tạm duyệt, chờ đối soát
- status `1` = đã duyệt sau đối soát
- status `2` = từ chối

Trước đây status `0` + có số tiền → app hiện **Đã duyệt – Chờ giải ngân**.

Giờ **không bị từ chối** thì:

- `conversion_status` lưu `disbursed`
- label **Đã giải ngân**
- KPI tính vào đã giải ngân, không còn “chờ giải ngân”

Nhóm KH **luôn `High`** (kể cả AT trả Medium).

File:

- `app/Support/AffiliateConversionStatus.php`
- `app/Console/Commands/SyncAccessTradeOrders.php`
- `app/Http/Requests/StoreAffiliatePostbackRequest.php`
- `app/Http/Controllers/Api/AffiliatePortalApiController.php`

Backfill 2026-09-04: 10 đơn Tin Vay cũ `pending` → `disbursed` + `product_category=High`.

## 3. Portal mobile — vuốt ngang

`overflow-x: clip` trên `html/body/.pv3` làm bảng/chip **khựng**, không xem cột bên phải.

Đã gỡ clip, table-wrap `overflow-x: auto` + `-webkit-overflow-scrolling: touch`. Trang chủ / báo cáo tổng vẫn card, không cần vuốt.

File portal: `public/style.css`, cache `?v=20260904_hscroll_v1`

## Test nhanh

```bash
# 302 + sub1
curl -sI -A "Mozilla/5.0 (iPhone…Safari/604.1)" \
  "https://3rdvn.io.vn/affiliate/tinvay?ref=RD260175"
# Location phải chứa sub1=RD260175&utm_source=3rdvn

cd /var/www/3rdvn-crm && php artisan test --filter=AffiliateConversionStatusTest
```
