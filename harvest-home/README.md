# Harvest & Home

An online shop and admin website for Harvest & Home, a farm stall in the Western Cape. Customers can browse products, place orders for home delivery or collection, and follow their order history. Staff manage products, stock, orders, promotions and user accounts from a separate admin website.

Built with HTML5, CSS3, JavaScript, PHP 8 (PDO) and MySQL, using Bootstrap 5 for layout and Bootstrap Icons. No content management system or PHP framework is used.

## Run it locally

Requirements: PHP 8.0 or newer, and MySQL 5.7 or newer (or MariaDB 10.3 or newer).

1. Create a database named `harvest_home` and import `database/harvest_home.sql`.
2. Set the database details in `config/db.php`, or use the environment variables `HH_DB_HOST`, `HH_DB_NAME`, `HH_DB_USER` and `HH_DB_PASS`.
3. From the `harvest-home` folder, start the built-in server:

   ```
   php -S localhost:8000
   ```

4. Open http://localhost:8000. The admin website is at http://localhost:8000/admin/.

## Deploy to a free host

This example uses InfinityFree.

1. Create a hosting account and a website.
2. In the control panel, open **MySQL Databases** and create a database. Note the host name, database name, user name and password.
3. Open **phpMyAdmin**, select the database, choose **Import** and upload `database/harvest_home.sql`.
4. Edit `config/db.php` on your computer and enter your database details. Do not upload the placeholder values.
5. Upload the contents of this folder to `htdocs` with the File Manager or FTP.
6. Open your site address. The admin website is at `/admin/`.

## Folder structure

| Path | Contents |
|---|---|
| `index.php`, `shop.php`, `product.php` | Home page, shop (search, filter and sort) and product details |
| `cart.php`, `checkout.php`, `payment.php`, `confirm.php` | Cart, checkout, simulated payment and order confirmation |
| `login.php`, `register.php`, `logout.php` | Customer accounts |
| `account.php`, `details.php`, `address.php`, `orders.php`, `order.php` | My Account area |
| `about.php`, `contact.php`, `terms.php` | Information pages |
| `admin/` | Admin website: dashboard, products, categories, inventory, orders, customers, promotions |
| `includes/` | Shared PHP helpers, header and footer |
| `config/` | Database connection settings |
| `database/` | `harvest_home.sql`: tables and sample data |
| `assets/` | CSS, JavaScript, logo and uploaded product images |

## Security

- Passwords are hashed with `password_hash()` and checked with `password_verify()`.
- Every database query uses PDO prepared statements.
- Every form that changes data includes a CSRF token.
- Output is escaped with `htmlspecialchars()`.
- Session IDs are regenerated on login. Session cookies are HttpOnly and SameSite.
- Every admin page checks the user's role against the database. Customers can only see their own orders.
- Logins are blocked for 5 minutes after 5 failed attempts.
- Uploaded images are checked by their real file type and size. PHP cannot run from the uploads folder.
- `.htaccess` blocks direct web access to `config`, `includes` and `database`.

## Business rules

- Delivery is a flat R50.00 within the Western Cape. Collection is free.
- Delivery orders start as *Processing*. Collection orders start as *Collection Pending*.
- Admins can only choose statuses that match the order's fulfilment method. Cancelling an order returns its items to stock.
- An active promotion lowers the shop price. If a product is in two active promotions, the lower price applies.
- Order items store the price actually paid, so later price changes do not affect past orders.
- A product that appears in past orders is set to inactive instead of being deleted.
- Only the main administrator (the original admin account) can change other administrator accounts.

## Payments

Payments are simulated for this project. Card details are checked for format but are never stored or charged. Use the test card `4111 1111 1111 1111` with any future expiry date and any three-digit CVV.

## Test accounts

Test accounts are not listed in this repository. They are provided separately with the project submission.
