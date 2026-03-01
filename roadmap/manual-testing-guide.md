# Manual Testing Guide

Step-by-step walkthrough for human testers. Covers every user-facing flow, organised by user type.

**Base URL:** `https://heybertie.test`

**Pre-requisites:**
- Dev server running (`composer run dev` or `npm run dev`)
- Seed data loaded (`php artisan migrate:fresh --seed`)
- Use a modern browser (Chrome/Firefox/Safari)

---

## Flow 1: Customer / Visitor Journey

The customer journey covers discovery: finding a groomer, searching by location, browsing listings, and reading reviews.

### 1.1 Homepage

1. Visit `https://heybertie.test`
2. Verify the page loads with:
   - [ ] Hero heading: "Find trusted pet services near you"
   - [ ] Search form with three inputs: **Service dropdown** (Dog Grooming, Dog Walking, Cat Sitting), **Location text field** with placeholder "e.g. London, SW1A", **Date picker**
   - [ ] Search button labelled "Search"
3. Below the search form:
   - [ ] "Popular:" quick links row — London, Manchester, Birmingham, Leeds, Bristol
   - [ ] Each link points to `/dog-grooming-in-{city}` (hover to check)
4. Scroll down and verify these sections exist:
   - [ ] **Trust bar** — "2,500+ Verified Professionals", "50,000+ Happy Pets Served", "4.9 Average Rating"
   - [ ] **How it works** — 3 steps: Search, Compare, Book
   - [ ] **Popular services** — Dog Grooming, Dog Walking, Cat Sitting cards with links
   - [ ] **Popular cities** — Grid of 12 cities (London, Manchester, Birmingham, Leeds, Bristol, Liverpool, Edinburgh, Glasgow, Sheffield, Newcastle, Cardiff, Nottingham)
   - [ ] **Why choose heyBertie** — 4 feature cards (Instant Booking, Verified Pros, Secure Payments, Real Reviews)
   - [ ] **Professional CTA** — "Are you a dog groomer?" with "Join as a Professional" and "Learn More" buttons

### 1.2 Location Autocomplete

1. Click into the location field on the homepage
2. Type `Lon` (3 characters)
   - [ ] Dropdown appears with location suggestions
   - [ ] "London" appears in the list
3. Use **arrow keys** to navigate suggestions
   - [ ] Highlighted item changes as you arrow up/down
4. Press **Enter** on a highlighted suggestion
   - [ ] Input fills with the location name (e.g. "London")
   - [ ] Dropdown closes
5. Clear the input, type `Ful`
   - [ ] "Fulham, London" appears in suggestions
6. **Click** on "Fulham, London"
   - [ ] Input fills with "Fulham, London", dropdown closes
7. Clear the input, type `GU7`
   - [ ] Postcode-sector matches appear (e.g. towns in the GU7 area)
8. Press **Escape**
   - [ ] Dropdown closes
9. Click outside the dropdown
   - [ ] Dropdown closes
10. Type just 1 character (e.g. `L`)
    - [ ] No dropdown appears (frontend enforces minimum 2 characters)

### 1.3 Search to SEO Landing Page (Redirect)

1. On the homepage, select **Dog Grooming** in the service dropdown
2. Type `London` in the location field (use autocomplete or type it manually)
3. Click **Search**
   - [ ] Browser redirects to `https://heybertie.test/dog-grooming-in-london`
   - [ ] URL bar shows `/dog-grooming-in-london` (clean SEO URL, no query params)
4. On the landing page, verify:
   - [ ] Heading reads **"Dog Grooming in London"**
   - [ ] Page title (in browser tab) contains "Dog Grooming in London"
   - [ ] Search bar at the top is pre-populated with current query
   - [ ] Business results are shown (seeded businesses near London — Muddy Paws Grooming should appear)
   - [ ] Results count shown (e.g. "X dog groomers in London")

### 1.4 Search with Postcode (No Redirect)

1. Go back to the homepage
2. Type `SW6 1UD` in the location field
3. Click **Search**
   - [ ] Browser navigates to `https://heybertie.test/search?location=SW6+1UD` (no redirect — postcodes don't have SEO landing pages)
   - [ ] Results page loads showing businesses near that postcode
   - [ ] Location autocomplete still works on the results page search bar (type a new location to verify)

### 1.5 SEO Landing Pages (Direct URLs)

Visit each URL directly and check the response:

| URL | Expected |
|-----|----------|
| `/dog-grooming-in-london` | 200 OK, results shown, heading "Dog Grooming in London" |
| `/dog-grooming-in-fulham-london` | 200 OK, results shown, heading includes "Fulham, London" |
| `/dog-grooming-in-manchester` | 200 OK, results shown |
| `/dog-walking-in-london` | 200 OK, results shown for Dog Walking |
| `/cat-sitting-in-london` | 200 OK, results shown for Cat Sitting |
| `/dog-grooming-in-invalid-city` | **404 Not Found** |
| `/invalid-service-in-london` | **404 Not Found** |

### 1.6 Search Results Page

On any landing page (e.g. `/dog-grooming-in-london`):

1. **Result cards** — each card shows:
   - [ ] Business name (as a clickable link)
   - [ ] Star rating and review count
   - [ ] Location type badge (Salon / Mobile / Home-based)
   - [ ] Town, city, and distance in km
   - [ ] Top services with prices
   - [ ] Verification badge (if verified)
   - [ ] "View" link to the business listing

2. **Filters sidebar** (left side on desktop, drawer on mobile):
   - [ ] Location Type checkboxes: Salon, Mobile, Home-based
   - [ ] Min Rating radio buttons: 4+, 3+, Any
   - [ ] Distance radio buttons: 5 km, 10 km, 25 km, 50 km
   - [ ] Changing a filter auto-submits the form
   - [ ] Filter values appear in URL as query params (e.g. `?type=salon&sort=rating`)
   - [ ] "Clear All" resets filters

3. **Sort dropdown**:
   - [ ] Options: Distance, Rating, Price (low-high), Price (high-low)
   - [ ] Changing sort updates results

4. **Pagination** (if enough results):
   - [ ] Page numbers shown at the bottom
   - [ ] Clicking a page loads more results

### 1.7 Filter Preservation

1. On the homepage, search for "London" with Dog Grooming selected
   - [ ] Redirects to `/dog-grooming-in-london`
2. Apply filters: set type to "salon", sort by "rating"
   - [ ] URL updates to `/dog-grooming-in-london?type=salon&sort=rating`
3. Go back to homepage, search again with the same filters already in the form
   - [ ] Redirect preserves filters: `/dog-grooming-in-london?sort=rating&type=salon`

### 1.8 Popular Links Navigation

1. On the homepage, click **"London"** in the "Popular:" quick links row
   - [ ] Navigates to `/dog-grooming-in-london`
2. Go back, click **"Manchester"** in the Popular Cities grid
   - [ ] Navigates to `/dog-grooming-in-manchester`

### 1.9 Business Listing Page

1. From search results, click on **Muddy Paws Grooming** (or any result card)
   - [ ] Listing page loads at `/{handle}/{location-slug}` (e.g. `/muddy-paws/fulham-london`)
2. Verify the listing page shows:
   - [ ] **Business name** and description
   - [ ] **Location details** — address, city, postcode
   - [ ] **Services** — list of active services with prices and durations
   - [ ] **Reviews** — star rating summary, individual review cards with text
   - [ ] **Opening hours** (if configured)
   - [ ] **Contact information** (phone, email, website if available)

### 1.10 Multi-Location Business (Hub Page)

1. Visit `https://heybertie.test/muddy-paws` (handle only, no location slug)
   - [ ] Hub page loads showing all locations for Muddy Paws
   - [ ] Both "Fulham Salon" and "Chelsea Branch" are listed
   - [ ] Each location links to its specific listing page
2. Click on "Chelsea Branch"
   - [ ] Navigates to `/muddy-paws/chelsea-london`
   - [ ] Shows services and details specific to that location

### 1.11 Edge Cases

1. **Empty search** — Submit the search form with an empty location field
   - [ ] Validation prevents submission (field turns red, error message appears)
   - [ ] Form does not submit
2. **Unknown location** — Type "xyznonexistent" in the location field and search
   - [ ] Results page shows a friendly error: "We couldn't find that location"
   - [ ] Suggestions are shown (try a UK postcode, city or town name)
   - [ ] No crash or 500 error

---

## Flow 2: Business Owner Journey

The business owner journey covers registration, onboarding, and managing a business profile.

### 2.1 Join / For Professionals Page

1. Visit `https://heybertie.test/for-dog-groomers`
   - [ ] Marketing page loads with information about joining as a professional
   - [ ] CTA button links to the join/register page
2. Visit `https://heybertie.test/join`
   - [ ] Registration form loads with business registration intent
   - [ ] This is the same registration form but pre-configured for business sign-up

### 2.2 Registration

1. On the join page, fill in:
   - Name: `Test Business Owner`
   - Email: `testbiz@example.com`
   - Password: `password123456`
   - Confirm Password: `password123456`
2. Click Register
   - [ ] Account is created and you are logged in
   - [ ] Redirected to the onboarding flow (not the regular dashboard)
3. For comparison: registering from the normal `/register` page (without going through `/join` first) should redirect to the regular dashboard, NOT onboarding

### 2.3 Email Verification

1. After registration, you may be shown an email verification notice
   - [ ] Check `storage/logs/laravel.log` for the verification email (in local dev, emails are logged instead of sent)
   - [ ] Find the verification link in the log and visit it
   - [ ] Email is verified, you can proceed

### 2.4 Onboarding Flow (7 Steps)

After registration via `/join`, you should be redirected to `/onboarding`. Walk through each step:

**Step 1 — Business Type:**
1. Visit `/onboarding` (should redirect to `/onboarding/step/1`)
   - [ ] Four business type options shown: Salon, Mobile, Home-based, Hybrid
   - [ ] Each option has an icon and brief description
2. Select **Salon**
3. Click Next
   - [ ] Redirected to step 2
   - [ ] Progress bar shows step 1 complete

**Step 2 — Business Details:**
1. Enter:
   - Business Name: `Test Grooming Studio`
   - Description: `A friendly grooming studio in South London.`
   - Phone: `020 7123 4567` (optional)
   - Email: `hello@testgrooming.co.uk` (optional)
2. Click Next
   - [ ] Redirected to step 3

**Step 3 — Handle:**
1. A handle should be auto-suggested from the business name (e.g. `test-grooming-studio`)
   - [ ] Handle input shows with `@` prefix
   - [ ] Real-time availability check (green tick if available)
2. You can type a custom handle (e.g. `test-studio`)
   - [ ] Availability check updates as you type (debounced)
3. If handle is taken, alternative suggestions should appear
4. Click Next
   - [ ] Redirected to step 4

**Step 4 — Location:**
1. Enter:
   - Address Line 1: `45 Lillie Road`
   - Town: `Fulham`
   - City: `London`
   - Postcode: `SW6 1UD`
2. If the postcode lookup is working, entering a postcode may auto-fill some fields
3. Click Next
   - [ ] Redirected to step 5

**Step 5 — Services:**
1. Suggested grooming services may be shown (Full Groom, Bath & Brush, Nail Trim, etc.)
   - [ ] Click a suggestion to add it to your list
2. Add at least one service:
   - Name: `Full Groom`
   - Duration: `90 minutes`
   - Price: `45.00`
   - Price Type: `Fixed`
3. You should be able to add multiple services and remove them
4. Click Next
   - [ ] Redirected to step 6
   - [ ] Minimum 1 service is enforced

**Step 6 — Verification:**
1. Upload a photo ID:
   - [ ] File upload zone accepts images (JPG, PNG) and PDF
   - [ ] Maximum file size: 5MB
   - [ ] Preview shown after upload
2. Optional documents: grooming qualification, insurance certificate
3. Click Next
   - [ ] Redirected to step 7

**Step 7 — Plan Selection:**
1. Three pricing plans shown:
   - [ ] **Free** (£0) — Business listing, handle URL
   - [ ] **Solo** (£29/mo) — Booking calendar, payments, CRM, SMS/email reminders, basic analytics. "Most Popular" badge.
   - [ ] **Salon** (£79/mo) — Up to 5 staff, 3 locations, loyalty program, advanced analytics, priority support
2. Select a plan (e.g. **Solo**)
3. Click Next
   - [ ] Redirected to review page

**Review & Submit:**
1. Review page shows all entered data:
   - [ ] Business type, name, handle
   - [ ] Location address
   - [ ] Services list
   - [ ] Verification document count
   - [ ] Selected plan
   - [ ] Each section has an "Edit" link back to its step
2. Click **"Create My Business"** (or equivalent submit button)
   - [ ] Business is created
   - [ ] Redirected to business dashboard

### 2.5 Step Navigation

1. During onboarding, after reaching step 4, try navigating back to step 2
   - [ ] Step 2 loads with previously entered data preserved
   - [ ] You can edit and re-save
2. Try skipping directly to step 5 (e.g. type `/onboarding/step/5` in the URL) without completing step 4
   - [ ] Redirected back to the current incomplete step (step 4)

### 2.6 Returning User (Resume Onboarding)

1. After completing steps 1 and 2, **log out**
2. Log back in
3. Visit `/onboarding`
   - [ ] Redirected to step 3 (the next incomplete step)
   - [ ] Previously entered data (business name, type) is preserved

### 2.7 Dashboard

1. After completing onboarding, the dashboard loads at `/{handle}/dashboard`
   - [ ] Shows business overview stats (today's bookings, weekly revenue, total customers, page views)
   - [ ] Upcoming bookings section
   - [ ] Recent activity feed
   - [ ] Quick actions panel

2. **Access control test:**
   - Log in as a **different user** (who has their own completed business)
   - Try visiting the first user's dashboard URL (e.g. `/test-studio/dashboard`)
   - [ ] **403 Forbidden** — strangers cannot access another business's dashboard

3. **Guest access test:**
   - Log out completely
   - Try visiting any `/{handle}/dashboard` URL
   - [ ] Redirected to login page

### 2.8 Public Visibility

1. In an **incognito/private** browser window (not logged in):
   - Visit `https://heybertie.test/{handle}` (the handle you created during onboarding)
   - [ ] Business listing page is publicly visible
   - Visit `https://heybertie.test/{handle}/{location-slug}`
   - [ ] Location-specific listing page loads with services and details

---

## Flow 3: Cross-Journey Verification

These tests verify that data flows correctly between the customer and business owner experiences.

### 3.1 New Business Appears in Search

1. After completing onboarding for a business in London (with coordinates set)
2. In a new browser/incognito window, search for "London"
   - [ ] The newly created business appears in search results
   - [ ] Result card shows correct name, services, and location

### 3.2 Inactive Businesses Excluded

1. Using seed data, there should be businesses with `is_active = false`
2. Search in their city/area
   - [ ] Inactive businesses do **NOT** appear in search results
3. Try visiting an inactive business's listing URL directly
   - [ ] **404 Not Found**

### 3.3 Draft Businesses Excluded

1. A business with `onboarding_completed = false` (someone who started but didn't finish onboarding)
2. Search in their area
   - [ ] Draft businesses do **NOT** appear in search results
3. Try visiting a draft business's listing URL directly
   - [ ] **404 Not Found**

### 3.4 Review Visibility

1. Visit a business listing page
2. Only **published** reviews should appear
   - [ ] Reviews with `is_published = false` are **NOT** visible
   - [ ] Star rating and count only reflect published reviews

### 3.5 Service Visibility

1. Visit a business listing page
2. Only **active** services should appear
   - [ ] Services with `is_active = false` are **NOT** visible

### 3.6 Data Isolation Between Businesses

1. Log in as the owner of Business A
2. Visit Business A's dashboard
   - [ ] Shows only Business A's data (customers, bookings, revenue)
3. Try accessing Business B's dashboard URL
   - [ ] **403 Forbidden** — cannot see another business's data

---

## Flow 4: SEO & Technical Verification

### 4.1 Schema Markup (JSON-LD)

1. Visit an SEO landing page (e.g. `/dog-grooming-in-london`)
2. View page source (Ctrl+U / Cmd+U)
   - [ ] `<script type="application/ld+json">` block present
   - [ ] Contains `SearchResultsPage` and/or `ItemList` schema
   - [ ] Business results are listed with name, URL, address, rating
3. Visit a business listing page
   - [ ] JSON-LD contains `LocalBusiness` schema with name, address, geo, aggregateRating

### 4.2 Meta Tags

1. On an SEO landing page (e.g. `/dog-grooming-in-london`):
   - [ ] `<title>` contains "Dog Grooming in London"
   - [ ] `<meta name="description">` present with result count
   - [ ] `<link rel="canonical">` points to the current page URL
2. On a business listing page:
   - [ ] `<title>` contains the business name and location

### 4.3 Canonical URLs & Redirects

1. Visit `/p/{id}-{slug}` for a known business
   - [ ] 301 redirect to `/{handle}` or `/{handle}/{location}`
2. If a business has changed its handle (check `handle_changes` table):
   - Visit the old handle URL
   - [ ] 301 redirect to the new handle URL

---

## Seeded Test Data Reference

The seed data includes these businesses for testing:

| Business | Handle | Tier | Location | City | Verified |
|----------|--------|------|----------|------|----------|
| Muddy Paws Grooming | `muddy-paws` | Salon | Fulham Salon + Chelsea Branch | London | Yes |
| The Dog House Spa | `dog-house-spa` | Solo | Manchester Salon | Manchester | Yes |
| Pampered Pooch Mobile | `pampered-pooch` | Solo | Mobile Service | Stockport | Yes |
| Bark & Beautiful | `bark-beautiful` | Free | Bristol Studio | Bristol | Pending |
| Wagging Tails Studio | `wagging-tails` | Free | Leeds Salon | Leeds | Rejected |

Muddy Paws has **two locations** (Fulham and Chelsea) — use it for multi-location testing.

---

## Mobile Testing Checklist

Repeat key flows on a mobile device or browser DevTools mobile emulation:

- [ ] Homepage search form stacks vertically
- [ ] Location autocomplete dropdown is usable on mobile
- [ ] Search results show in single column
- [ ] Filter sidebar opens as a slide-out drawer (not always visible)
- [ ] Business listing page is readable and scrollable
- [ ] Onboarding steps are mobile-friendly
- [ ] Sticky booking CTA bar appears on business listing pages

---

## Browser Compatibility

Test in:
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile Safari (iOS)
- [ ] Mobile Chrome (Android)
