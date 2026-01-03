# Project Initiation Document

## Project Name

Custom WordPress Banner & Statistics Plugin

---

## 1. Background & Context

- The website displays third‑party promotional banners from partner companies.
- Partners require transparent and reliable statistics for:
    - Banner views (impressions)
    - Banner clicks

- Existing third‑party plugins introduced licensing and usage restrictions for commercial use.
- A first‑party solution is required to remove licensing risk, retain data ownership, and allow controlled evolution.

---

## 2. Purpose & Vision

The purpose of this project is to build a **self‑hosted WordPress plugin** that:

- Displays commercial banners in defined locations
- Tracks impressions and clicks accurately
- Works reliably under caching and CDN setups
- Produces advertiser‑ready statistics
- Remains lightweight, auditable, and extensible

The long‑term vision is a **publisher‑grade banner system**, not a full ad network.

---

## 3. Objectives

### Primary Objectives

- Support banner placement via shortcodes and widgets
- Allow desktop and mobile banner variants
- Define banner runtime via start/end dates
- Track impressions and clicks
- Provide aggregated reporting

### Secondary Objectives

- Cache‑safe tracking
- GDPR‑aware by default
- No external dependencies or SaaS services
- Clear separation of concerns (rendering, tracking, reporting)

---

## 4. Scope Definition

### In Scope (MVP)

- Banner CRUD (create, edit, pause, schedule)
- Placement CRUD (define where banners can appear)
- Banner rotation per placement
- Shortcode rendering
- Widget support
- Impression tracking
- Click tracking with redirect
- Aggregated daily statistics
- Admin reporting UI
- CSV export

### Explicitly Out of Scope (v1)

- Real‑time bidding (RTB)
- Advertiser self‑service accounts
- Payments or invoicing
- User profiling or behavioral targeting
- Cross‑site ad serving

---

## 5. Functional Requirements

### Banner Management

- Desktop and mobile creative assets
- Destination URL per banner (optionally per device)
- Start and end dates
- Manual enable/disable
- Weight or priority value

### Placement Management

- Unique placement identifier (slug)
- Multiple banners per placement
- Rotation strategies:
    - Random
    - Weighted
    - Ordered

- Device breakpoint configuration

### Rendering

- Shortcode interface
- Widget interface
- Single shared rendering pipeline

### Tracking

- Impression tracking (client‑side)
- Click tracking via redirect endpoint
- Aggregation by day, banner, and placement

---

## 6. Non‑Functional Requirements

- **Performance:**
    - No blocking requests during page load
    - Minimal JavaScript footprint

- **Reliability:**
    - Accurate metrics under page caching

- **Security:**
    - Signed tracking tokens
    - Basic rate limiting on click endpoints

- **Privacy:**
    - Aggregated statistics only
    - No storage of raw IP addresses

- **Maintainability:**
    - Modular architecture
    - Clear boundaries between components

---

## 7. Data & Metrics Principles

- Statistics are **first‑party and authoritative**
- Impressions must be clearly defined (e.g., viewport‑based)
- CTR = clicks / impressions
- Metrics definitions must be stable and documented

---

## 8. Success Criteria

- Banners render correctly across devices
- Scheduling activates and expires banners automatically
- Stats increase consistently with traffic
- Advertisers accept provided reports
- No licensing or compliance risks

---

## 9. Risks & Mitigations

| Risk                      | Mitigation                     |
| ------------------------- | ------------------------------ |
| Page caching breaks stats | Client‑side impression beacons |
| Metric disputes           | Clear metric definitions       |
| Scope creep               | Strict MVP boundaries          |

---

## 10. Assumptions & Constraints

- Single WordPress installation
- Moderate traffic volume
- Limited number of placements
- Managed by site administrators only

---

## 11. Open Questions (To Be Resolved Before Implementation)

- Final definition of an impression
- Custom tables vs WordPress post types
- Frequency capping requirements
- Gutenberg block inclusion

---

## 12. Document Role

This document is the **source of truth** for:

- Project intent
- Scope boundaries
- High‑level requirements

It must be consulted before architectural or implementation decisions are made.
