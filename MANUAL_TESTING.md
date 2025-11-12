# Manual Testing Checklist - Standalone Admin Page

This document provides a step-by-step manual testing checklist to verify the plugin is working correctly with the new standalone admin page implementation.

## Pre-Testing Requirements

- WordPress admin access with `manage_woocommerce` capability (admin or shop manager)
- WooCommerce 5.0+ installed and activated
- WooCommerce Stripe Payment Gateway 7.0+ installed and activated
- Browser developer console access (for checking console errors)

## Test Phase 1: Plugin Activation & Visibility

### Test 1.1: Admin Menu Appears
**Steps:**
1. Log in to WordPress admin
2. Look at the left sidebar under WooCommerce menu

**Expected Result:**
- New submenu item: **"Stripe Metadata"** appears under WooCommerce

**Pass/Fail:** ☐ Pass ☐ Fail

**Notes:** _______________________________________________________________

---

### Test 1.2: Admin Page Loads
**Steps:**
1. Click "WooCommerce → Stripe Metadata"
2. Wait for page to load
3. Check browser console (F12) for JavaScript errors

**Expected Result:**
- Page loads without errors
- No red errors in console
- Page title shows "Stripe Custom Metadata Configuration"
- No PHP warnings or notices

**Pass/Fail:** ☐ Pass ☐ Fail

**Console Errors:** ☐ None ☐ Some (note them below)

**Notes:** _______________________________________________________________

---

## Test Phase 2: Admin Page UI Elements

### Test 2.1: Multi-Product Method Radio Buttons
**Steps:**
1. On the admin page, locate "Multi-Product Handling Method" section
2. Check that both radio button options are visible:
   - "Delimited Values (e.g., SKU1,SKU2,SKU3)"
   - "Numbered Keys (e.g., product_1_sku, product_2_sku)"
3. Try selecting each option
4. Verify the option toggle works

**Expected Result:**
- Both radio options visible and selectable
- One option can be selected at a time
- No JavaScript errors on selection

**Pass/Fail:** ☐ Pass ☐ Fail

**Notes:** _______________________________________________________________

---

### Test 2.2: Cart & Order Metadata Checkboxes
**Steps:**
1. Locate "Cart & Order Metadata" section
2. Count the visible checkboxes
3. Verify labels are readable
4. Try checking/unchecking a few boxes

**Expected Result:**
- Section is visible and expandable
- Multiple checkboxes visible (should be around 12)
- Each has a clear label
- Checkboxes toggle on/off correctly
- No JavaScript errors

**Pass/Fail:** ☐ Pass ☐ Fail

**Checkbox Count:** ____________

**Notes:** _______________________________________________________________

---

### Test 2.3: User Metadata Section
**Steps:**
1. Locate "User Metadata" section
2. Check if any checkboxes appear (depends on your database)

**Expected Result:**
- Section visible and properly labeled
- If custom user metadata exists: checkboxes shown
- If no user metadata: message says "No custom user metadata fields found"
- Static metadata suggestion shown

**Pass/Fail:** ☐ Pass ☐ Fail

**User Metadata Found:** ☐ Yes ☐ No

**Notes:** _______________________________________________________________

---

### Test 2.4: Product Metadata Section
**Steps:**
1. Locate "Product Metadata" section
2. Check if any checkboxes appear (depends on your database)

**Expected Result:**
- Section visible and properly labeled
- If custom product metadata exists: checkboxes shown
- If no product metadata: message says "No custom product metadata fields found"
- Static metadata suggestion shown

**Pass/Fail:** ☐ Pass ☐ Fail

**Product Metadata Found:** ☐ Yes ☐ No

**Notes:** _______________________________________________________________

---

### Test 2.5: Static Metadata Pairs Table
**Steps:**
1. Locate "Static Metadata Pairs" section
2. Verify table structure with "Key" and "Value" columns
3. Look for "Add Row" button below the table
4. Check initial state (empty row or populated with existing data)

**Expected Result:**
- Table visible with proper column headers
- "Add Row" button visible and accessible
- Input fields have correct placeholders
- At least one empty row visible (if no data)

**Pass/Fail:** ☐ Pass ☐ Fail

**Initial Rows:** ____________

**Notes:** _______________________________________________________________

---

## Test Phase 3: Form Functionality

### Test 3.1: Add Static Metadata Row
**Steps:**
1. Click "Add Row" button in Static Metadata Pairs section
2. Verify a new row appears
3. Click again to add another row
4. Repeat 3-4 times

**Expected Result:**
- Each click adds a new row immediately
- New rows appear with empty input fields
- No page reload required
- No JavaScript errors
- "Remove" button (if implemented) appears in new rows

**Pass/Fail:** ☐ Pass ☐ Fail

**Rows Added:** ____________

**Notes:** _______________________________________________________________

---

### Test 3.2: Enter Metadata Pairs
**Steps:**
1. In the static metadata rows, enter:
   - Key: "test_key_1" (20 chars or less)
   - Value: "test_value_1" (100 chars or less)
2. In another row:
   - Key: "store_location" (40 chars max test)
   - Value: "main_warehouse" (500 chars max test)
3. Add a third row with:
   - Key: "max_key_test_1234567890" (exactly 40 chars to test max)
   - Value: (enter very long text, up to 500 chars)

**Expected Result:**
- Text enters without issues
- Fields accept values up to their limits
- No character truncation visible in input fields
- No JavaScript errors

**Pass/Fail:** ☐ Pass ☐ Fail

**Notes:** _______________________________________________________________

---

### Test 3.3: Select Cart Metadata Fields
**Steps:**
1. Under "Cart & Order Metadata":
   - Check: "Order ID"
   - Check: "Customer Email"
   - Check: "Order Total"
   - Leave others unchecked
2. Verify checked state persists

**Expected Result:**
- Selected checkboxes show checked state
- Unchecked boxes remain unchecked
- No page reload when clicking
- Selection is stable

**Pass/Fail:** ☐ Pass ☐ Fail

**Notes:** _______________________________________________________________

---

### Test 3.4: Select Multi-Product Method
**Steps:**
1. Select "Delimited Values" radio button
2. Verify it's selected
3. Click "Numbered Keys"
4. Verify selection changed

**Expected Result:**
- Radio buttons toggle correctly
- Only one can be selected at a time
- Selection persists on the page
- No JavaScript errors

**Pass/Fail:** ☐ Pass ☐ Fail

**Notes:** _______________________________________________________________

---

## Test Phase 4: Form Submission & Persistence

### Test 4.1: Save Settings
**Steps:**
1. Ensure you have:
   - Multi-product method selected
   - Some cart metadata checked
   - Some static metadata pairs filled in
2. Scroll to bottom of page
3. Click "Save Changes" button
4. Wait for response

**Expected Result:**
- Page reloads or shows success message
- Green success notice appears: "Settings saved successfully"
- No error messages
- No PHP notices/warnings
- No JavaScript errors

**Pass/Fail:** ☐ Pass ☐ Fail

**Success Message Shown:** ☐ Yes ☐ No

**Error Messages:** ☐ None ☐ Some (list below)

**Notes:** _______________________________________________________________

---

### Test 4.2: Verify Settings Persistence
**Steps:**
1. After saving (from Test 4.1), refresh the page (F5 or Cmd+R)
2. Check that all your selections are still there:
   - Multi-product method
   - Checked cart metadata
   - Static metadata pairs
3. Verify nothing was lost

**Expected Result:**
- Page reloads with all previous selections intact
- All checkboxes remain checked as saved
- Static metadata pairs still present
- Multi-product method selection preserved
- No errors

**Pass/Fail:** ☐ Pass ☐ Fail

**Settings Lost:** ☐ Yes ☐ No (which ones?)

**Notes:** _______________________________________________________________

---

### Test 4.3: Modify and Re-save
**Steps:**
1. Make changes to existing configuration:
   - Uncheck a previously checked box
   - Check a previously unchecked box
   - Modify a static metadata value
   - Change multi-product method
2. Click "Save Changes"
3. Refresh the page
4. Verify changes persisted

**Expected Result:**
- Changes save without error
- Success message appears
- All changes persist after refresh
- No data corruption

**Pass/Fail:** ☐ Pass ☐ Fail

**Notes:** _______________________________________________________________

---

## Test Phase 5: Security & Validation

### Test 5.1: Permission Check
**Steps:**
1. Log out of admin
2. Try to directly access: `/wp-admin/admin.php?page=wc-stripe-custom-meta`
3. Verify you're redirected to login

**Expected Result:**
- Cannot access page when not logged in
- Redirected to WordPress login page
- No settings visible

**Pass/Fail:** ☐ Pass ☐ Fail

**Notes:** _______________________________________________________________

---

### Test 5.2: Nonce Validation
**Steps:**
1. Open page source (Ctrl+U or Cmd+U)
2. Search for "wc_stripe_custom_meta_nonce"
3. Verify a nonce field is present in the form

**Expected Result:**
- Nonce field exists in HTML
- Name: "wc_stripe_custom_meta_nonce"
- Non-empty value present
- Different on each page load (verify by refreshing)

**Pass/Fail:** ☐ Pass ☐ Fail

**Nonce Found:** ☐ Yes ☐ No

**Notes:** _______________________________________________________________

---

### Test 5.3: Input Sanitization
**Steps:**
1. Try to enter HTML/JavaScript in a metadata value:
   - Key: "test_key"
   - Value: "<script>alert('xss')</script>"
2. Save the settings
3. Reload the page
4. Check database value (via phpMyAdmin or WP-CLI)

**Expected Result:**
- Input is sanitized/escaped
- No HTML tags execute
- Value stored safely in database
- Display shows escaped version (no script execution)

**Pass/Fail:** ☐ Pass ☐ Fail

**Notes:** _______________________________________________________________

---

## Test Phase 6: CSS & Styling

### Test 6.1: Page Layout
**Steps:**
1. Check page layout on desktop browser
2. Verify sections are properly organized
3. Check that all elements are visible
4. Verify no text is cut off

**Expected Result:**
- Clean, organized layout
- Sections clearly separated
- All form elements visible
- Good use of spacing
- Professional appearance

**Pass/Fail:** ☐ Pass ☐ Fail

**Layout Issues:** ☐ None ☐ Some (describe below)

**Notes:** _______________________________________________________________

---

### Test 6.2: Mobile Responsiveness
**Steps:**
1. Open page on mobile device or resize browser to mobile width
2. Check layout on 375px width (iPhone)
3. Verify all form elements are accessible
4. Test scrolling and readability

**Expected Result:**
- Layout adapts to mobile width
- Form elements still accessible
- Text remains readable
- No horizontal scrolling (or minimal)
- Buttons clickable on touch devices

**Pass/Fail:** ☐ Pass ☐ Fail

**Mobile Issues:** ☐ None ☐ Some (describe below)

**Notes:** _______________________________________________________________

---

## Test Phase 7: JavaScript Functionality

### Test 7.1: Browser Console Check
**Steps:**
1. Open admin page
2. Open Developer Console (F12)
3. Go to Console tab
4. Look for any error messages (red)
5. Look for warnings (yellow)

**Expected Result:**
- No errors in console
- No warnings related to plugin
- Standard WordPress warnings OK
- No jQuery errors

**Pass/Fail:** ☐ Pass ☐ Fail

**Errors Found:** ☐ None ☐ Some (list below)

**Warnings Found:** ☐ None ☐ Some (list below)

**Notes:** _______________________________________________________________

---

### Test 7.2: Add/Remove Rows JavaScript
**Steps:**
1. Open browser console Network tab
2. Click "Add Row" multiple times
3. Watch for any AJAX requests (should be none - this should be client-side only)
4. Verify rows add without page reload

**Expected Result:**
- Rows add via client-side JavaScript only
- No AJAX requests
- No page reload
- Rows appear immediately

**Pass/Fail:** ☐ Pass ☐ Fail

**Unexpected AJAX:** ☐ None ☐ Some (describe below)

**Notes:** _______________________________________________________________

---

## Test Phase 8: Stripe Payment Intent Integration

### Test 8.1: Metadata Handler Fires
**Steps:**
1. Configure metadata in the admin page:
   - Select some cart metadata fields
   - Add a static metadata pair (e.g., "test_source": "manual_test")
   - Save settings
2. Enable WordPress debug logging in wp-config.php:
   ```php
   define( 'WP_DEBUG', true );
   define( 'WP_DEBUG_LOG', true );
   define( 'WP_DEBUG_DISPLAY', false );
   ```
3. Create a test order and pay with Stripe (test card)
4. Check `wp-content/debug.log` for metadata handler output

**Expected Result:**
- Order processes successfully
- Payment goes through in Stripe
- Debug log shows metadata handler executing (if logging added to code)
- No errors in log

**Pass/Fail:** ☐ Pass ☐ Fail

**Payment Successful:** ☐ Yes ☐ No

**Notes:** _______________________________________________________________

---

### Test 8.2: Verify Metadata in Stripe Dashboard
**Steps:**
1. Go to Stripe Dashboard (test mode)
2. Navigate to Developers → Events (or Payments → Payment Intents)
3. Find the payment intent from your test payment
4. Click to view details
5. Scroll to "Metadata" section

**Expected Result:**
- Metadata section visible
- Contains your configured metadata fields
- Contains static metadata pairs you entered
- Values match what you configured
- No formatting errors

**Pass/Fail:** ☐ Pass ☐ Fail

**Metadata Found:** ☐ Yes ☐ No

**Metadata Examples Visible:**
- ________________________
- ________________________
- ________________________

**Notes:** _______________________________________________________________

---

## Test Phase 9: Edge Cases & Limits

### Test 9.1: Maximum Key Length (40 chars)
**Steps:**
1. In static metadata row, enter:
   - Key: "this_key_is_exactly_forty_chars_12345" (40 chars)
   - Value: "test value"
2. Save and verify

**Expected Result:**
- Key accepts 40 characters
- Displays correctly after save

**Pass/Fail:** ☐ Pass ☐ Fail

**Notes:** _______________________________________________________________

---

### Test 9.2: Maximum Value Length (500 chars)
**Steps:**
1. In static metadata row, enter:
   - Key: "max_value_test"
   - Value: (paste 500 character text block)
2. Save and verify

**Expected Result:**
- Value accepts 500 characters
- Displays correctly after save

**Pass/Fail:** ☐ Pass ☐ Fail

**Notes:** _______________________________________________________________

---

### Test 9.3: Many Metadata Pairs
**Steps:**
1. Add 30 static metadata pairs
2. Save settings
3. Reload page
4. Verify all persist and page performs well

**Expected Result:**
- Page handles many rows without issue
- Performance remains acceptable
- All rows visible and editable
- Save completes successfully

**Pass/Fail:** ☐ Pass ☐ Fail

**Performance Issues:** ☐ None ☐ Some (describe below)

**Notes:** _______________________________________________________________

---

## Test Phase 10: Error Scenarios

### Test 10.1: Invalid Settings Recovery
**Steps:**
1. Configure valid settings and save
2. Directly modify WordPress database option to add invalid data
3. Reload admin page
4. Try to save again

**Expected Result:**
- Page loads without crashing
- Graceful handling of invalid data
- Can still modify and save settings
- No PHP fatal errors

**Pass/Fail:** ☐ Pass ☐ Fail

**Notes:** _______________________________________________________________

---

### Test 10.2: Settings Corruption Recovery
**Steps:**
1. Delete the `wc_stripe_custom_meta_settings` option from database
2. Reload admin page
3. Verify page loads and form is empty
4. Configure new settings and save

**Expected Result:**
- Page loads without error
- Form shows empty/default state
- Can configure and save new settings
- No database errors

**Pass/Fail:** ☐ Pass ☐ Fail

**Notes:** _______________________________________________________________

---

## Test Phase 11: Compatibility

### Test 11.1: WooCommerce Compatibility Check
**Steps:**
1. Go to WooCommerce → Settings → General
2. Verify no compatibility warnings shown
3. Go to Plugins page
4. Look for the wc-stripe-custom-meta plugin
5. Verify no warnings/errors shown

**Expected Result:**
- No WooCommerce compatibility warnings
- Plugin shows as "Active" on Plugins page
- No error messages
- No dependency warnings

**Pass/Fail:** ☐ Pass ☐ Fail

**Warnings Found:** ☐ None ☐ Some (describe below)

**Notes:** _______________________________________________________________

---

### Test 11.2: Browser Compatibility
**Steps:**
1. Test in multiple browsers if possible:
   - Chrome/Chromium
   - Firefox
   - Safari
   - Edge
2. For each, verify basic functionality works

**Expected Result:**
- Works correctly in all modern browsers
- Form submits and saves in all browsers
- No JavaScript errors specific to browsers

**Pass/Fail:** ☐ Pass ☐ Fail

**Tested Browsers:**
- ☐ Chrome/Chromium ☐ Pass ☐ Fail
- ☐ Firefox ☐ Pass ☐ Fail
- ☐ Safari ☐ Pass ☐ Fail
- ☐ Edge ☐ Pass ☐ Fail

**Notes:** _______________________________________________________________

---

## Summary

### Overall Test Results

**Total Tests:** 30

**Passed:** _____ / 30

**Failed:** _____ / 30

**Pass Rate:** _____ %

---

### Critical Issues Found

List any issues that prevent the plugin from functioning:

1. ___________________________________________________________________
2. ___________________________________________________________________
3. ___________________________________________________________________

---

### Minor Issues Found

List any non-critical issues or improvements:

1. ___________________________________________________________________
2. ___________________________________________________________________
3. ___________________________________________________________________

---

### Recommendations

Based on testing, recommend:

1. ___________________________________________________________________
2. ___________________________________________________________________
3. ___________________________________________________________________

---

**Tester Name:** _________________________

**Date Tested:** _________________________

**WordPress Version:** _________________________

**PHP Version:** _________________________

**WooCommerce Version:** _________________________

**Stripe Gateway Version:** _________________________

**Overall Status:** ☐ Ready for Production ☐ Needs Fixes ☐ Major Issues

---
