# Tomato - Food Delivery Website

A comprehensive food delivery platform similar to Zomato, built with PHP, MySQL, HTML, CSS, and JavaScript.

## New Project Structure (v2.0)

The project has been restructured for better scalability and maintainability, separating frontend and backend logic.

```
zomato-tomato/
├── backend/                  # Server-side logic
│   ├── api/                  # API endpoints (cart, chatbot, orders)
│   ├── config/               # Configuration files
│   ├── database/             # Database schema
│   └── includes/             # Utility functions and helpers
├── docs/                     # Documentation
│   ├── QUICK_REFERENCE.md    # Guide for developers
│   ├── README.md             # This file
│   └── SETUP_GUIDE.md        # Detailed setup instructions
├── frontend/                 # Client-side code
│   ├── assets/               # CSS, JS, Images
│   ├── components/           # Reusable UI components (header, footer)
│   └── pages/                # User-facing PHP pages
└── index.php                 # Entry point (Redirects to frontend)
```

## Features

### ✅ **User Authentication**
- Secure signup and login system
- Password hashing with bcrypt
- Session management
- New user offers

### ✅ **Restaurant Browsing**
- Homepage with featured restaurants
- Restaurant detail pages with menus
- Search and filter functionality
- **Smart Chatbot**: AI-powered recommendations based on cuisine, price, and dietary preferences.

### ✅ **Shopping Cart**
- Add/remove items with real-time updates
- Quantity management
- Order summary with pricing

### ✅ **Checkout & Direct Payment**
- **Direct Order Processing**: Simplified checkout flow without external gateways.
- **Bill Generation**: Instant digital bill creation with print functionality.
- **Order History**: Track past orders and status.

## Installation & Setup

### Prerequisites
1. **XAMPP/WAMP/LAMP** (PHP 7.4+, MySQL 5.7+)
2. **Web Browser** (Chrome, Firefox, Edge)

### Setup Steps

1. **Clone/Download**
   Place the `zomato-tomato` folder in your `htdocs` directory (e.g., `C:\xampp\htdocs\zomato-tomato`).

2. **Database Setup**
   - Open phpMyAdmin.
   - Create a database named `tomato_db`.
   - Import `backend/database/database.sql`.

3. **Configuration**
   - file: `backend/config/config.php`
   - Default credentials are set (Root/Empty password). Update if needed.
   - **Note**: This version uses a direct payment simulation, so no API keys are required.

4. **Launch**
   - Open `http://localhost/zomato-tomato`
   - You will be automatically redirected to the frontend homepage.

## Key Features Guide

### 🤖 Smart Chatbot
The chatbot now supports advanced filtering:
- **Cuisines**: Italian, Mexican, Chinese, Healthy, etc.
- **Dietary**: Veg / Non-Veg / Both.
- **Price Range**: Budget, Mid-Range, Premium.

### 🧾 Direct Billing
- Proceed to checkout -> Enter Address -> Place Order.
- Instantly view and print the detailed bill at `bill.php`.

## Credentials (For Testing)
- **User**: Create a new account or use `test@example.com` (if imported).
- **Restaurants**: Valid data included in SQL import.

---
**Enjoy Tomato!** 🍕
