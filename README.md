# Alice Store - E-Commerce Backend

This is a minimal e-commerce backend built with **Laravel**, featuring:

- Product listing
- Persistent cart without authentication (unique token per cart)
- Add and remove items from cart
- Order creation via email (no payment)
- Recommendation system based on past orders
- JSON import for products and orders
- API only (front-end not included)

---

## Prerequisites

You can run this project **with Docker**, so you don’t need PHP, Composer, or SQLite installed locally.  
If you prefer to run it without Docker, you will need:

- **PHP >= 8.1**
- **Composer**

- Optional: **Postman** or similar API client

---

## Installation with Docker

1. Build and start the Docker container: ```docker-compose up -d --build ```
2. Start the server: ```docker-compose exec app php artisan serve --host=0.0.0.0 --port=8000```
3. Visit api. Ex: http://localhost:8000/api/products
4. Run tests: ```docker-compose exec app php artisan test ```

## Installation without Docker

```bash
git clone https://github.com/decaluwepatrick/laravel_alice_store.git
cd repo
composer install
php artisan serve # start server
php artisan test  # start tests
```

## Features

### Products
- Retrieve all products

### Cart
- Create a cart (unique token)
- Add/remove products
- Retrieve full cart with items
- Persistent cart

### Recommendations
- Co-occurrence matrix based on historical orders
- Can be rebuilt upon each purchase or via Artisan command
- Recommends products based on cart contents

### Orders
- Checkout with email
- Cart is converted into order and emptied

---

## Tech Stack

- Laravel 11
- Eloquent ORM (SQLite by default. Compatible with MySQL / MariaDB)
- Postman collection included for API testing
- REST API with JSON

---

## Project Structure
```
app/
├── Http/
│   └── Controllers/
│       ├── ProductController.php
│       ├── CartController.php
│       └── OrderController.php
├── Models/
│   ├── Product.php
│   ├── Cart.php
│   └── CartItem.php
└── Services/
    └── RecommendationService.php
├── routes/
│   └── api.php
storage/
├── co_matrix.json        (generated)
├── products.json             (input)
└── orders.json               (input)

```
---

## Regenerate recommendation system 

```php artisan build-recommendation ```

when outside docker:

```docker-compose exec app php artisan build-recommendation ```

## Try it out with Postman

1. run the server from the working directory (with or without Docker, see above)
2. import the collection into Postman from the file: ```alice.postman_collection.json```
3. set the base url in your postman environment: ``` base_url = http://localhost:8000 ```
4. visit the endpoints of the collection

## Possible Improvements

- Authentication (customers/admin)
- Better and more scalable recommendation system than co-occurrence matrix (like Jaccard similarity)
- Cache layer for faster recommendations
- Admin UI for managing products, stock management, shipping etc.
- API versioning, rate limiting

## API endpoints:

### Products

| Method | Endpoint          | Description              |
|--------|-------------------|--------------------------|
| GET    | /api/products     | Retrieve all products    |

---

### Cart

| Method | Endpoint                                  | Description                         |
|--------|---------------------------------------------|-------------------------------------|
| POST   | /api/cart                                   | Create a new cart (returns token)   |
| GET    | /api/cart/{cart_token}                      | Retrieve cart with items            |
| GET    | /api/cart/{cart_token}/recommendation       | Get product recommendations          |
| POST   | /api/cart/{cart_token}                      | Add a product to the cart           |
| DELETE | /api/cart/{cart_token}                      | Remove a product from the cart      |

> All cart routes are protected by the `valid.cart` middleware.

---

### Orders

| Method | Endpoint      | Description                      |
|--------|---------------|----------------------------------|
| POST   | /api/orders   | Create an order (checkout cart)  |
