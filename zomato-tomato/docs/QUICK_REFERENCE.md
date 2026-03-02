# Zomato-Tomato - Quick Reference Guide

## Project Structure Overview

```
zomato-tomato/
├── frontend/          # All user-facing code
│   ├── assets/       # CSS, JS, images
│   ├── pages/        # PHP pages (13 files)
│   └── components/   # Reusable UI components
├── backend/          # All server-side code
│   ├── api/         # API endpoints
│   ├── config/      # Configuration
│   ├── includes/    # Utilities
│   └── database/    # SQL schemas
├── docs/            # Documentation
└── index.php        # Entry point
```

## Quick Access URLs

### Development
- **Homepage**: http://localhost/zomato-tomato/
- **Direct**: http://localhost/zomato-tomato/frontend/pages/index.php

### Key Pages
- Restaurant: `/frontend/pages/restaurant.php?id=1`
- Cart: `/frontend/pages/cart.php`
- Checkout: `/frontend/pages/checkout.php`
- Orders: `/frontend/pages/orders.php`

## Path Constants (Use These!)

```php
SITE_URL      // http://localhost/zomato-tomato
ASSETS_URL    // /frontend/assets
PAGES_URL     // /frontend/pages
API_URL       // /backend/api
```

## Common Code Patterns

### In Frontend Pages
```php
require_once __DIR__ . '/../../backend/config/config.php';
require_once __DIR__ . '/../../backend/includes/auth.php';
require_once __DIR__ . '/../components/header.php';
```

### In Components
```php
<link href="<?php echo ASSETS_URL; ?>/css/style.css">
<a href="<?php echo PAGES_URL; ?>/index.php">Home</a>
```

### In JavaScript
```javascript
fetch('/zomato-tomato/backend/api/chatbot-handler.php', {
    method: 'POST',
    // ...
});
```

## File Locations

### Frontend
- **Pages**: `frontend/pages/*.php`
- **CSS**: `frontend/assets/css/*.css`
- **JS**: `frontend/assets/js/*.js`
- **Components**: `frontend/components/*.php`

### Backend
- **APIs**: `backend/api/*.php`
- **Config**: `backend/config/config.php`
- **Auth**: `backend/includes/auth.php`

## Testing Steps

1. Open: http://localhost/zomato-tomato/
2. Check: CSS loads, navigation works
3. Test: Login, add to cart, checkout
4. Verify: Chatbot, API calls, orders

## Benefits

✅ Clean separation of frontend/backend
✅ Easy to find files
✅ Professional structure
✅ Scalable architecture
✅ Team-friendly organization

---

**Status**: ✅ Implementation Complete
**Last Updated**: January 25, 2026
