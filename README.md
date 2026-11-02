# Nowgong Mart

This is the ordering website for a single kirana store that also sells mini ice cream, serving Nowgong, Madhya Pradesh. Customers browse products, fill a cart and check out with Cash on Delivery or a UPI payment that the shop verifies by hand. The owner manages the catalogue, stock and orders, and the delivery person uses a phone-friendly screen. The site can be installed on a phone as a Progressive Web App (PWA).

It was built from *Nowgong Mart – Complete Development and Live Deployment Guide* (v1.0, 02 Nov 2026).

## Stack

- Laravel 12 on PHP 8.2 or newer. The guide asks for Laravel 13, which needs PHP 8.3. This machine's XAMPP has PHP 8.2, so the project uses Laravel 12, which has the same structure. To move to Laravel 13 later, upgrade PHP to 8.3+, set `laravel/framework` to `^13.0` in `composer.json` and run `composer update`.
- Blade, Tailwind CSS 4 (built by Vite) and a little plain JavaScript.
- MySQL 8 or MariaDB 10.4+. Money columns are `DECIMAL(10,2)`, quantities are `DECIMAL(10,3)`, and all money arithmetic uses `bcmath`.
- Laravel session login, using a phone number and password.

## Run it locally

```powershell
composer install
npm install
copy .env.example .env          # then set DB_DATABASE=nowgong_mart, DB_USERNAME, DB_PASSWORD
php artisan key:generate
php artisan migrate --seed      # in local env: also seeds a demo catalogue and 3 dev logins
php artisan storage:link
npm run build                   # or `npm run dev` while editing views/CSS
php artisan serve
```

In the `local` environment the seeder creates three development accounts and prints a random password they share:

| Role | Phone |
|---|---|
| Admin / owner | 9000000001 |
| Delivery person | 9000000002 |
| Customer | 9000000003 |

In any other environment no accounts are seeded. Create the owner account with `php artisan mart:create-admin`, which asks for the name, phone and password.

### Tests

```powershell
php artisan test
```

The tests use a separate MySQL database called `nowgong_mart_test`, set in `phpunit.xml`. Create it before the first run. The suite covers the acceptance checklist in section 16 of the guide:

- role access
- product CRUD and image uploads
- out-of-stock items
- the last unit, which only one customer can buy
- price tampering
- order snapshots
- cancellation, which restores stock exactly once
- UPI verification
- delivery permissions
- the PWA files

## Where things live

| Area | Files |
|---|---|
| Business rules | `app/Services/OrderService.php` covers checkout, status transitions, delivery and payment review. `InventoryService.php` keeps the stock ledger, `CartService.php` the cart, and `StoreSettings.php` the store settings. |
| Statuses and roles | `app/Enums/*`. `OrderStatus::nextStatuses()` defines every allowed transition. |
| Access control | `app/Http/Middleware/RoleMiddleware.php` (`role:admin`, `role:customer`, `role:delivery`) and `app/Policies/OrderPolicy.php` |
| Routes | `routes/web.php`, following the route plan in section 12 of the guide |
| Screens | `resources/views/{customer,admin,delivery,auth,pages}` |
| PWA | `public/manifest.webmanifest`, `public/sw.js`, `public/offline.html`, `public/icons/` |

### How the store rules are enforced

- **Checkout** reloads prices from the database and locks the product rows (`lockForUpdate`). It then rechecks stock, takes snapshots of each item's name, unit and price, deducts stock and writes a ledger row, all in one transaction. Prices, totals or roles sent by the browser are ignored.
- **Stock** changes only through the inventory screen (counter sale, restock or count correction) or through orders. Every change writes a row to `inventory_movements` with the user, the quantity before and the quantity after. Editing a product cannot change its stock.
- **Cancelling** reverses exactly what the order deducted, using the ledger. A cancelled order cannot be cancelled again, so stock comes back only once.
- **Status changes** follow pending → confirmed → preparing → out_for_delivery → delivered, and an order can be cancelled from the first three:
  - Only the admin can confirm, prepare and assign an order.
  - Only the assigned delivery person can mark it picked up or delivered.
  - A customer can cancel only while the order is pending, and only if that is enabled in Settings.
  - Every change is recorded with who made it.
- **COD:** the delivery person enters the cash collected. If it matches the order total, the payment is marked paid. If it doesn't, the payment stays pending and the order appears on the admin dashboard as a COD cash mismatch.
- **UPI:** the payment stays pending until the owner checks the bank or UPI app and clicks *Mark received* or *Not received*. Customers can add their UPI reference (UTR) after placing the order.
- **Products in past orders** are never deleted. Deleting one deactivates it instead.

## Before launch: owner decisions

These come from section 20 of the guide. Set them in **Admin → Settings**:

- store phone and WhatsApp number
- opening hours
- delivery pincodes (default `471201`)
- minimum order, delivery fee and free-delivery threshold
- the store UPI ID and QR code image
- whether customers may cancel their own orders

The privacy, terms, refund and contact pages are templates. The owner should review them before launch.

## Live deployment

The guide's sections 13–15 give the full steps. In short:

1. Push the code to a **private** GitHub repository. Never commit `.env`.
2. Use PHP hosting that supports Laravel, with SSH, Composer, cron, SSL and backups. Point the web root at `public/` only.
3. Create a production database and a database user with only the permissions it needs. Set the environment to `APP_ENV=production`, `APP_DEBUG=false`, a new `APP_KEY` and an `https://` `APP_URL`.
4. Deploy with these commands:

   ```bash
   composer install --no-dev --prefer-dist --optimize-autoloader
   npm ci && npm run build
   php artisan down
   php artisan migrate --force        # take a DB backup first
   php artisan db:seed --force        # first deploy only: settings + categories, no demo data
   php artisan mart:create-admin      # first deploy only
   php artisan storage:link
   php artisan optimize
   php artisan up
   ```

5. Make `storage/` and `bootstrap/cache/` writable by the web server.
6. Turn on HTTPS before taking real orders. Service workers and PWA install require HTTPS.
7. Never run `migrate:fresh` on the live database. Back up both the database and `storage/app/public` (product images), and test that a restore actually works.

### Launch-day checklist

These come from section 16 of the guide:

1. Enter the real products, prices, units and stock counts.
2. Create the delivery person's account and test it on their phone.
3. Run one test COD order and one test UPI order all the way through delivery.
4. Print a QR code that links to the site and put it at the counter.
5. Train staff to record counter sales on the inventory screen right away.
