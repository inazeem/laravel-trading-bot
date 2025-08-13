# KuCoin API Troubleshooting Guide

## 🔍 **Issue: "Connection failed - no balance data received"**

This error occurs when the KuCoin API authentication fails or returns no data.

## 🚨 **Common Causes & Solutions:**

### **1. Invalid API Key**
**Error:** `"KC-API-KEY not exists"`

**Solution:**
- Go to KuCoin → API Management
- Create a new API key
- Copy the new API key exactly
- Update your Laravel app with the new key

### **2. Wrong Passphrase**
**Error:** `"Invalid KC-API-PASSPHRASE"`

**Solution:**
- The passphrase is set when you create the API key
- It's NOT your KuCoin login password
- Check your API key creation notes
- If forgotten, create a new API key

### **3. Incorrect API Secret**
**Error:** `"Invalid signature"`

**Solution:**
- Copy the API secret exactly from KuCoin
- Don't add extra spaces or characters
- Make sure it's the correct secret for this API key

### **4. Insufficient Permissions**
**Error:** `"Insufficient permissions"`

**Solution:**
- API key needs these permissions:
  - ✅ **Read** (for balance checking)
  - ✅ **Trade** (for trading)
  - ✅ **Transfer** (for transfers)

### **5. IP Restrictions**
**Error:** `"IP not allowed"`

**Solution:**
- Check if IP restrictions are set
- Add your server IP to allowed list
- Or disable IP restrictions temporarily

## 🔧 **Step-by-Step Fix:**

### **Step 1: Create New API Key**
1. Log into KuCoin
2. Go to **API Management**
3. Click **Create API Key**
4. Set these options:
   - **Name:** Laravel Trading Bot
   - **Permissions:** Read, Trade, Transfer
   - **IP Restrictions:** None (or add your server IP)
   - **Note down the passphrase!**

### **Step 2: Update Your App**
1. Go to your Laravel app
2. Navigate to **API Keys**
3. Edit your KuCoin API key
4. Update with new credentials:
   - **API Key:** [new key from KuCoin]
   - **API Secret:** [new secret from KuCoin]
   - **Passphrase:** [passphrase you set]

### **Step 3: Test Connection**
1. Click **"Test"** button on your API key
2. Check the response
3. If successful, you'll see your balances

## 📋 **API Key Requirements:**

### **For Portfolio View:**
- ✅ **Read** permission (minimum)

### **For Trading:**
- ✅ **Read** permission
- ✅ **Trade** permission

### **For Transfers:**
- ✅ **Read** permission
- ✅ **Transfer** permission

## 🔍 **Debugging Steps:**

### **1. Check API Key Status**
```bash
# Check if API key is active in KuCoin
# Go to KuCoin → API Management
# Verify the key is "Active"
```

### **2. Test with Simple Request**
```bash
# Use KuCoin's API testing tool
# Or test with curl/Postman
```

### **3. Check Laravel Logs**
```bash
tail -f storage/logs/laravel.log
# Look for KuCoin-related errors
```

### **4. Verify Permissions**
- Make sure API key has "Read" permission
- Check if any restrictions are set

## 🎯 **Quick Test:**

1. **Create a test API key** with only "Read" permission
2. **Test the connection** in your app
3. **If it works**, add other permissions
4. **If it fails**, check credentials again

## ⚠️ **Security Notes:**

- Never share your API keys
- Use IP restrictions if possible
- Regularly rotate your API keys
- Monitor API usage in KuCoin

## 📞 **Still Having Issues?**

If you're still getting errors after following these steps:

1. **Double-check all credentials**
2. **Create a completely new API key**
3. **Test with minimal permissions first**
4. **Check KuCoin's API status page**
5. **Contact KuCoin support if needed**

## ✅ **Success Indicators:**

When your API key is working correctly, you should see:
- ✅ "Connection successful" message
- ✅ List of your assets/balances
- ✅ Assets appearing in your portfolio
- ✅ No authentication errors in logs
