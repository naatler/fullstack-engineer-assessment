# Task 1 - Online Store API

This project is part of the Fullstack Engineer Assessment Test.

The goal of this task is to build a simple Online Store API that can handle product ordering during a flash sale situation. The main focus of this project is not only creating basic CRUD endpoints, but also making sure the inventory system is safe when many customers try to buy the same product at the same time.

## Overview

In this API, a customer can create an order that contains one or more order items. Each order item is connected to a product and has its own quantity, unit price, and subtotal.

The important part of this implementation is inventory protection. When a product is being purchased, the system checks the available stock before creating the order. If the requested quantity is greater than the available stock, the API will return an error response and the order will not be created.

To handle flash sale conditions, this project uses database transactions and row-level locking. This prevents multiple requests from reducing the same product stock at the same time and helps ensure that the inventory quantity never becomes negative.

## Tech Stack

* PHP
* Laravel
* MySQL
* REST API
* JSON response format

## Main Features

* Product API
* Order API
* Order must contain at least one order item
* Stock validation before creating an order
* Inventory protection to prevent negative stock
* Race condition handling during flash sale
* Command-line stress test for concurrent order requests
* Public API support using ngrok

## Project Structure

```text
task-1-online-store/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       ├── ProductController.php
│   │   │       └── OrderController.php
│   │   └── Requests/
│   │       └── StoreOrderRequest.php
│   ├── Models/
│   │   ├── Product.php
│   │   ├── Order.php
│   │   └── OrderItem.php
│   ├── Services/
│   │   └── OrderService.php
│   └── Console/
│       └── Commands/
│           └── FlashSaleStressTest.php
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php
└── README.md
```

## Database Design

This project uses three main tables:

### products

Stores product information and available stock.

Main fields:

```text
id
name
price
stock
created_at
updated_at
```

### orders

Stores order data.

Main fields:

```text
id
total_price
created_at
updated_at
```

### order_items

Stores each item inside an order.

Main fields:

```text
id
order_id
product_id
quantity
unit_price
subtotal
created_at
updated_at
```

## API Endpoints

### Products

#### Get all products

```http
GET /api/products
```

Example response:

```json
{
  "message": "Products retrieved successfully",
  "data": []
}
```

#### Create product

```http
POST /api/products
```

Example request body:

```json
{
  "name": "Flash Sale Product",
  "price": 10000,
  "stock": 10
}
```

Example response:

```json
{
  "message": "Product created successfully",
  "data": {
    "id": 1,
    "name": "Flash Sale Product",
    "price": "10000.00",
    "stock": 10
  }
}
```

#### Get product detail

```http
GET /api/products/{id}
```

---

### Orders

#### Get all orders

```http
GET /api/orders
```

Example response:

```json
{
  "message": "Orders retrieved successfully",
  "data": []
}
```

#### Create order

```http
POST /api/orders
```

Example request body:

```json
{
  "items": [
    {
      "product_id": 1,
      "quantity": 1
    }
  ]
}
```

Example success response:

```json
{
  "message": "Order created successfully",
  "data": {
    "id": 1,
    "total_price": "10000.00",
    "items": [
      {
        "product_id": 1,
        "quantity": 1,
        "unit_price": "10000.00",
        "subtotal": "10000.00"
      }
    ]
  }
}
```

#### Get order detail

```http
GET /api/orders/{id}
```

## Validation Rules

When creating an order, the API requires at least one order item.

Required request format:

```json
{
  "items": [
    {
      "product_id": 1,
      "quantity": 1
    }
  ]
}
```

Validation rules:

* `items` is required
* `items` must be an array
* `items` must contain at least one item
* `product_id` is required and must exist in the products table
* `quantity` is required and must be at least 1

If the request is invalid, the API will return a `422 Unprocessable Entity` response.

## Race Condition Handling

During a flash sale, many customers may try to buy the same product at almost the same time. Without proper handling, two or more requests could read the same stock value and reduce it incorrectly. This can cause the stock to become negative.

To prevent this issue, the order creation process is wrapped inside a database transaction.

Inside the transaction, the selected product row is locked using `lockForUpdate()` before the stock is checked and reduced.

Simplified flow:

```text
1. Start database transaction
2. Lock the selected product row
3. Check available stock
4. Reject the order if stock is not enough
5. Create order
6. Create order items
7. Reduce product stock
8. Commit transaction
```

This approach ensures that only one request can update the selected product stock at a time.

## How to Run Locally

Clone the repository:

```bash
git clone https://github.com/naatler/fullstack-engineer-assessment.git
```

Go to the project folder:

```bash
cd fullstack-engineer-assessment/task-1-online-store
```

Install dependencies:

```bash
composer install
```

Copy the environment file:

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Configure your database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fullstack_assessment
DB_USERNAME=root
DB_PASSWORD=
```

Run migration and seeder:

```bash
php artisan migrate:fresh --seed
```

Start the Laravel server:

```bash
php artisan serve
```

The API will be available at:

```text
http://127.0.0.1:8000
```

## Example API Testing with cURL

### Get products

```bash
curl http://127.0.0.1:8000/api/products \
-H "Accept: application/json"
```

### Create product

```bash
curl -X POST http://127.0.0.1:8000/api/products \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d '{"name":"Flash Sale Product","price":10000,"stock":10}'
```

### Create order

```bash
curl -X POST http://127.0.0.1:8000/api/orders \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-d '{"items":[{"product_id":1,"quantity":1}]}'
```

## Public API Access

Base URL:

https://ragingly-subramous-shawna.ngrok-free.dev

Available endpoints:

GET /api/products  
POST /api/products  
GET /api/products/{id}  

GET /api/orders  
POST /api/orders  
GET /api/orders/{id}

GET https://ragingly-subramous-shawna.ngrok-free.dev/api/products
POST https://ragingly-subramous-shawna.ngrok-free.dev/api/orders

## Flash Sale Race Condition Test

This project includes a command-line stress test to simulate multiple customers trying to buy the same product at the same time.

Before running the test, start the Laravel server:

```bash
php artisan serve
```

Then open another terminal and run:

```bash
php artisan flash-sale:stress-test 1 --requests=100 --stock=10 --quantity=1
```

Expected result:

```text
Total requests: 100
Success orders: 10
Failed orders: 90
Final stock: 0
PASSED: Race condition handled successfully.
```

This means only 10 orders are allowed to succeed because the available stock is 10. The remaining requests should fail because the product is out of stock.

The most important result is that the final stock must never be negative.

## Response Codes

This API uses proper HTTP response codes:

```text
200 OK                  Successful GET request
201 Created             Product or order created successfully
404 Not Found           Resource not found
422 Unprocessable Entity Validation error or insufficient stock
405 Method Not Allowed  Wrong HTTP method used for the endpoint
500 Server Error        Unexpected server error
```

## Notes

The main focus of this task is to make the order process safe during a flash sale. The implementation uses Laravel database transactions and row-level locking to prevent overselling.

This project was built with readability and maintainability in mind. The business logic for creating orders is separated into a service class, while controllers are kept simple and focused on handling HTTP requests and responses.
