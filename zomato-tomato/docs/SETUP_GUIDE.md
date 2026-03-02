# 🚀 Quick Setup Guide - Tomato Food Delivery

Follow these steps to get your Tomato website running:

## Step 1: Install XAMPP
1. Download XAMPP from https://www.apachefriends.org/
2. Install XAMPP
3. Open XAMPP Control Panel
4. Start **Apache** and **MySQL** services

## Step 2: Setup Database
1. Open browser and go to: `http://localhost/phpmyadmin`
2. Click "New" to create a database
3. Database name: `tomato_db`
4. Click "Create"
5. Select `tomato_db` from left sidebar
6. Click "Import" tab
7. Choose file: `database/database.sql`
8. Click "Go" to import

## Step 3: Configure Razorpay
1. Go to https://razorpay.com and sign up
2. Login to dashboard
3. Go to Settings → API Keys
4. Copy your **Key ID** and **Key Secret**
5. Open `config/config.php` in a text editor
6. Replace these lines:
   ```php
   define('RAZORPAY_KEY_ID', 'YOUR_RAZORPAY_KEY_ID');
   define('RAZORPAY_KEY_SECRET', 'YOUR_RAZORPAY_KEY_SECRET');
   ```
   With your actual keys:
   ```php
   define('RAZORPAY_KEY_ID', 'rzp_test_xxxxxxxxxxxxx');
   define('RAZORPAY_KEY_SECRET', 'xxxxxxxxxxxxxxxxxxxxxxxx');
   ```

## Step 4: Update Site URL (if needed)
If your folder is not named `zomato-tomato`, update in `config/config.php`:
```php
define('SITE_URL', 'http://localhost/YOUR_FOLDER_NAME');
```

## Step 5: Access the Website
1. Open browser
2. Go to: `http://localhost/zomato-tomato`
3. You should see the homepage!

## Step 6: Create Your First Account
1. Click "Signup" button
2. Fill in your details
3. You'll get a welcome offer!
4. Start ordering food 🍕

---

## 🧪 Testing Payment

Use these test card details in Razorpay:
- **Card Number**: 4111 1111 1111 1111
- **CVV**: Any 3 digits (e.g., 123)
- **Expiry**: Any future date (e.g., 12/25)
- **Name**: Your name

---

## 🤖 Testing Chatbot

1. Click the chat bubble (💬) at bottom-right
2. Try these messages:
   - "Southern Avenue"
   - "Park Street"
   - "I want Chinese food"
   - "Help me find food"

---

## 📱 Sample Data Included

The database comes with:
- ✅ 5 Restaurants
- ✅ 15+ Menu Items
- ✅ 8 Localities
- ✅ 3 Special Offers

---

## ❓ Troubleshooting

### Database Connection Error?
- Make sure MySQL is running in XAMPP
- Check database name is `tomato_db`
- Verify credentials in `config/config.php`

### Page Not Found?
- Check Apache is running
- Verify folder is in `C:\xampp\htdocs\`
- Check SITE_URL in config

### Payment Not Working?
- Verify Razorpay keys are correct
- Make sure you're using TEST mode keys
- Check browser console for errors

### Can't Login?
- Create a new account first
- Check if database was imported correctly
- Clear browser cookies

---

## 🎉 You're All Set!

Your Tomato food delivery website is ready to use!

**Default Features Available**:
- ✅ User Registration & Login
- ✅ Browse Restaurants
- ✅ Search Food Items
- ✅ Add to Cart
- ✅ Apply Offers
- ✅ Razorpay Payment
- ✅ Order History
- ✅ AI Chatbot
- ✅ User Profile

**Enjoy! 🍕🍔🍜**
