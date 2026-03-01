# Phase 9: Legal & Compliance

## Context

As HeyBertie handles bookings, payments, and personal data (customer details, pet info, business banking), we need proper legal documentation and compliance measures in place before public launch.

## What's Needed

### Terms & Conditions

- **Platform Terms of Service** — governs use of HeyBertie by both businesses and customers
- **Business Terms** — specific terms for businesses listing on the platform (commission, payment terms, cancellation policies, liability)
- **Customer Terms** — terms covering the booking process, deposits, refunds, and dispute resolution

### Privacy Policy

- Data collection and processing (customer info, pet details, booking history)
- Third-party data sharing (Stripe for payments, any analytics/email providers)
- Data retention and deletion policies
- GDPR compliance (UK GDPR post-Brexit) — right to access, right to erasure, data portability
- Cookie policy (if applicable)

### Payment-Specific Compliance

- Clear disclosure of the 2.5% platform fee to businesses
- Refund policy documentation (automatic deposit refunds on cancellation)
- PCI compliance — handled by Stripe but our terms should reference this

### Business Verification

- Consider whether businesses need to agree to specific terms during onboarding
- Terms acceptance checkbox + timestamp storage for audit trail

## Implementation Considerations

- Legal pages should be accessible from the footer on all public pages
- Terms acceptance during registration/onboarding should be recorded with timestamps
- Consider a versioning system for terms so we can track which version each user agreed to
- May need professional legal review before going live

## Key Pages

| Page | URL | Purpose |
|------|-----|---------|
| Terms of Service | `/terms` | Platform terms for all users |
| Privacy Policy | `/privacy` | Data handling and GDPR |
| Cookie Policy | `/cookies` | Cookie usage (if needed) |
| Business Terms | `/business-terms` | Terms specific to listed businesses |

## Priority

High — should be completed before public launch and before onboarding real businesses.
