# Phase 11: Brand Identity & Visual Polish

## Context

heyBertie is structurally solid but visually generic. All colors are grayscale (black/white/gray-50), the logo is plain text, the favicon is the default Laravel diamond, the dashboard sidebar says "Laravel Starter Kit", and marketing icons use Unicode emoji. This phase establishes a brand identity from scratch and applies it across the full site — marketing pages, dashboard, booking flow, and CMS pages — targeting the visual quality of Fresha and Treatwell. Priority is visual polish first (colors, typography, consistency).

## Current State

- **Colors**: Pure grayscale OKLCH values with zero chroma. `--primary: oklch(0.205 0 0)` (black).
- **Font**: Instrument Sans (400–700) from Bunny Fonts CDN.
- **Logo**: Text "heyBertie" in header; favicon.svg is the Laravel geometric diamond.
- **Dashboard**: shadcn/ui themed via CSS variables in `resources/css/app.css`. Logo component says "Laravel Starter Kit".
- **Marketing icons**: HTML entity emoji (`&#128054;`, `&#9889;`, etc.) in `bg-gray-200` circles.
- **Buttons**: All use `bg-gray-900 text-white hover:bg-gray-800` — no brand color anywhere.

---

## Phase 11a: Brand Foundation — Color Palette, Logo & Favicon

**Goal:** Establish brand tokens so all subsequent phases reference them.

### Brand Color Palette

Warm teal primary with amber accent — friendly, trustworthy, differentiated from Fresha (cool teal) and Treatwell (plum).

| Token | Light Mode | Dark Mode | Usage |
|-------|-----------|-----------|-------|
| `--primary` | `oklch(0.55 0.15 165)` | `oklch(0.75 0.15 165)` | Brand teal — CTAs, links, selected states |
| `--primary-foreground` | `oklch(0.99 0 0)` | `oklch(0.15 0 0)` | Text on primary |
| `--accent` | `oklch(0.75 0.12 55)` | `oklch(0.65 0.12 55)` | Warm amber — secondary highlights |
| `--ring` | `oklch(0.70 0.12 165)` | `oklch(0.55 0.10 165)` | Focus rings, tinted primary |
| `--sidebar-primary` | `oklch(0.55 0.15 165)` | `oklch(0.75 0.15 165)` | Sidebar active item |

All other tokens (`secondary`, `muted`, `border`, `card`) remain neutral gray — the brand color comes through CTAs, links, and interactive states only.

### Logo & Favicon

- **Wordmark**: "heyBertie" text with the "B" in brand primary color — inline SVG in both Blade header and React sidebar
- **Icon**: Simplified paw print or "B" monogram — works at 16px favicon size
- Replace: `public/favicon.svg`, `public/favicon.ico`
- Update: `app-logo.tsx` ("Laravel Starter Kit" → "heyBertie"), `app-logo-icon.tsx` (Laravel diamond → heyBertie icon)

### Files to modify

| File | Changes |
|------|---------|
| `resources/css/app.css` | Replace all OKLCH values in `:root` and `.dark` with brand palette |
| `resources/js/components/app-logo.tsx` | Replace "Laravel Starter Kit" with "heyBertie" |
| `resources/js/components/app-logo-icon.tsx` | Replace Laravel diamond SVG with heyBertie icon |
| `public/favicon.svg` | New brand icon |
| `public/favicon.ico` | Regenerated |

### Verification
- `npm run build` — CSS compiles
- Visually inspect a dashboard page and a marketing page — brand colors visible
- `php artisan test --compact` — no breakage

---

## Phase 11b: Marketing Layout — Header & Footer

**Goal:** Brand the site shell that wraps every public page.

### Header changes (`layouts/marketing.blade.php`)
- Replace text "heyBertie" with inline SVG wordmark
- "Sign Up" button: `bg-gray-900` → `bg-primary hover:bg-primary/90`
- Nav link hover: add `hover:text-primary transition-colors duration-150`
- Border: `border-b-2 border-gray-200` → `border-b border-border` (thinner, subtler)
- Logged-in avatar circle: `bg-gray-200` → `bg-primary/10 text-primary`

### Footer changes
- Background: `bg-gray-50` → `bg-primary/[0.03]` (subtle brand tint)
- Border: `border-t-2` → `border-t border-border`
- Add SVG logo above footer columns
- Link hover: `hover:text-primary`

### Files
- `resources/views/layouts/marketing.blade.php`

---

## Phase 11c: Marketing Pages — Heroes, CTAs, Icons

**Goal:** Transform marketing pages from flat grayscale to branded and premium.

### Blade icon component (new)
Create `resources/views/components/icon.blade.php` — accepts `name` prop, renders inline SVG from a map of Lucide-compatible icons (Scissors, Dog, Zap, CheckCircle, Star, Search, Calendar, Users, MapPin, Clock, Heart, Shield, Sparkles). Replaces all emoji usage.

### Shared partials updates
- **`hero.blade.php`**: `bg-gray-50` → `bg-gradient-to-b from-primary/5 to-white`; CTAs → `bg-primary`
- **`feature-grid.blade.php`**: Emoji → `<x-icon>` component; icon circles → `bg-primary/10 text-primary`
- **`how-it-works.blade.php`**: Step circles → `border-primary text-primary`
- **`testimonials.blade.php`**: Add `shadow-sm`, decorative quote SVG in `text-primary/20`
- **`cta-banner.blade.php`**: Border/button → `border-primary`, `bg-primary`
- **`faq.blade.php`**: Question hover → `hover:text-primary`

### Page-specific updates
- **`home.blade.php`**: Replace all ~8 emoji icons with `<x-icon>`; branded buttons, card hovers, form focus states, trust bar numbers in `text-primary`
- **`for-dog-groomers.blade.php`**: Same icon replacement; pricing cards highlighted in `border-primary` with "Most Popular" badge; check marks → `text-primary`

### Files (9)

| File | Changes |
|------|---------|
| `resources/views/components/icon.blade.php` | **New** — Blade icon component |
| `resources/views/marketing/partials/hero.blade.php` | Brand gradient, CTAs |
| `resources/views/marketing/partials/feature-grid.blade.php` | SVG icons, branded circles |
| `resources/views/marketing/partials/how-it-works.blade.php` | Branded step circles |
| `resources/views/marketing/partials/testimonials.blade.php` | Shadow, quote decoration |
| `resources/views/marketing/partials/cta-banner.blade.php` | Branded border and button |
| `resources/views/marketing/partials/faq.blade.php` | Hover states |
| `resources/views/marketing/home.blade.php` | Emoji → SVG, branded elements |
| `resources/views/marketing/for-dog-groomers.blade.php` | Emoji → SVG, pricing highlight |

---

## Phase 11d: Listing, Search & Booking Flow

**Goal:** Bring the core user-facing flows to Fresha/Treatwell quality.

### Search pages
- Search button: `bg-gray-900` → `bg-primary`
- Input focus: `focus:border-primary focus:ring-primary/20`
- Result card hover: `hover:border-primary`

### Listing page (~6 partials)
- "Book Now" buttons: `bg-gray-900` → `bg-primary`
- Selected service state: `border-gray-900 ring-1 ring-gray-900` → `border-primary ring-1 ring-primary`
- Sticky booking bar button: → `bg-primary`

### Booking flow (highest UX impact)
- **Step indicator**: Active step `text-primary font-semibold`, completed `text-primary`, upcoming `text-gray-400`
- **Selected states** (services, staff, date, time): All `border-gray-900 bg-gray-900` → `border-primary bg-primary`
- **All buttons**: Continue/Confirm → `bg-primary`
- **Input focus states**: → `focus:border-primary focus:ring-primary/20`
- **Basket summary**: Add `shadow-sm` to sticky card

### Customer booking pages
- Reschedule flow: same branded date/time states

### Files (~17 files across `listing/`, `search/`, `booking/`, `customer/` partials)

---

## Phase 11e: Dashboard Re-theme

**Goal:** Apply brand identity to the business dashboard.

### Automatic via CSS variables
Most dashboard components (all shadcn/ui `Button`, `Badge`, `Input`, etc.) automatically pick up the new `--primary` from Phase 11a. No code changes needed for these.

### Manual adjustments

| File | Changes |
|------|---------|
| `resources/js/components/dashboard/stat-card.tsx` | Icon bg: `bg-muted` → `bg-primary/10`, icon color → `text-primary` |
| `resources/js/layouts/auth/auth-simple-layout.tsx` | Add "heyBertie" wordmark text below icon |

### Verification
- Inspect dashboard in both light and dark mode
- Verify sidebar collapsed icon displays correctly
- Test all auth pages (login, register, forgot-password)

---

## Phase 11f: CMS Pages & Micro-Polish

**Goal:** Final consistency sweep across remaining pages.

### CMS pages
- **Blog**: Category badges → `bg-primary/10 text-primary`; title hover → `group-hover:text-primary`
- **Guides**: Featured card border → `border-primary`; "Read guide" → `text-primary`
- **Help Centre**: Search focus → `focus:border-primary`; "Read article" → `text-primary`
- **Prose links**: Add `.prose a { color: var(--primary) }` to `app.css`

### Micro-polish
- **Transitions**: Audit all interactive elements for `transition-colors duration-150`
- **Shadows**: Resting cards → `shadow-sm`; hover → `hover:shadow-md`
- **Focus rings**: Consistent `focus:ring-2 focus:ring-primary/20 focus:border-primary` on Blade forms
- **Typography prose**: Brand-colored links in article content

### Files (~8 files: blog/index, blog/show, guides/index, guides/show, help/index, help/show, docs/index, docs/show, plus `app.css`)

---

## Execution Order

| Sub-phase | Scope | Depends on |
|-----------|-------|-----------|
| **11a** | Brand tokens, logo, favicon | — |
| **11b** | Marketing header/footer | 11a |
| **11c** | Marketing pages + icon system | 11a |
| **11d** | Search, listing, booking flow | 11a |
| **11e** | Dashboard re-theme | 11a |
| **11f** | CMS pages + micro-polish | 11a–11e |

Each sub-phase is independently committable. 11b–11e can be done in any order after 11a. 11f is the final sweep.

## Verification (per sub-phase)
1. `vendor/bin/pint --dirty --format agent`
2. `npm run build` — frontend compiles
3. `php artisan test --compact` — no regressions
4. Visual inspection of affected pages (manual testing guide per sub-phase)
