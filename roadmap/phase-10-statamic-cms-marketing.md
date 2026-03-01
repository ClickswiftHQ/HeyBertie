# Phase 10: Statamic CMS for Marketing Pages

## Context

HeyBertie already has Statamic CMS installed (`statamic/cms` v5). This phase activates it for marketing content, starting with a blog. The goal is to allow non-technical content management — publishing blog posts, updating marketing copy, and managing SEO — without code deployments.

## What's Included

### Phase 10a: Blog

The first use case for Statamic — a blog for SEO, content marketing, and customer/business education.

**Content types:**
- Blog posts (title, body, featured image, author, categories/tags, excerpt, SEO meta)
- Blog categories (e.g. "Grooming Tips", "Business Guides", "Product News")

**Pages:**
- Blog listing page (`/blog`) with pagination
- Individual blog post page (`/blog/{slug}`)
- Category filter pages (`/blog/category/{slug}`)

**Features:**
- Rich text editor via Statamic CP for writing posts
- Featured image with automatic resizing/optimisation
- SEO fields (meta title, description, OG image)
- RSS feed
- Related posts suggestions
- Social sharing meta tags

**Technical approach:**
- Statamic collections for blog posts
- Antlers or Blade templates matching the existing marketing page design
- Statamic CP accessible at `/cp` for content editors
- Static caching for performance

### Phase 10b: Marketing Page Management (Future)

Extend Statamic to manage other marketing pages:
- Homepage hero content, testimonials, feature highlights
- "For Dog Groomers" landing page content
- FAQ pages
- Help/support articles

### Phase 10c: Email Templates (Future)

Consider using Statamic to manage email template copy so marketing can update email wording without deployments.

## Technical Considerations

- Statamic is already installed — need to configure collections, blueprints, and templates
- Statamic routes file already required in `routes/web.php` via `routes/statamic.php`
- Blog templates should match the existing marketing layout (`layouts.marketing`)
- CP user management — decide who gets access to the control panel
- Consider flat-file vs database driver (flat-file is default, works well for content)

## Priority

Medium — valuable for SEO and content marketing but not a launch blocker.
