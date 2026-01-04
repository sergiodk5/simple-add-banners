# Impressions Documentation

This document explains how impression tracking works in Simple Add Banners. It is written for anyone to understand, regardless of technical background.

---

## What is an Impression?

An **impression** is recorded when a visitor actually sees your banner. Unlike simple page loads, impressions measure real visibility.

Simple Add Banners uses a strict definition of an impression:

```
┌─────────────────────────────────────────────────────────────────────────┐
│                                                                         │
│   An IMPRESSION is counted when ALL of these conditions are met:       │
│                                                                         │
│   ┌─────────────────────────────────────────────────────────────────┐   │
│   │  1. VISIBILITY                                                  │   │
│   │     The banner is at least 50% visible in the browser window    │   │
│   └─────────────────────────────────────────────────────────────────┘   │
│                              +                                          │
│   ┌─────────────────────────────────────────────────────────────────┐   │
│   │  2. DURATION                                                    │   │
│   │     The banner remains visible for at least 1 second            │   │
│   └─────────────────────────────────────────────────────────────────┘   │
│                              +                                          │
│   ┌─────────────────────────────────────────────────────────────────┐   │
│   │  3. UNIQUENESS                                                  │   │
│   │     Only one impression per banner per browser session          │   │
│   └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## Why This Definition Matters

Traditional tracking counts a page load as an impression, but this is inaccurate:

```
TRADITIONAL TRACKING                    SIMPLE ADD BANNERS
──────────────────────                  ──────────────────────

Page loads = Impression                 Actual view = Impression

Problems:                               Benefits:
• Banner below fold? Counted anyway     • Only counts real views
• Visitor scrolls past quickly? Counted • Ensures meaningful exposure
• Page cached? May double count         • Works with page caching
• Bot traffic? Often counted            • Resistant to bot inflation
```

### Real-World Example

```
Visitor lands on your page
         │
         ▼
┌─────────────────────────────────────────┐
│  Page Header                            │
│                                         │
│  ┌───────────────────────────────────┐  │
│  │         BANNER HERE               │  │  ← Banner is visible
│  │                                   │  │
│  └───────────────────────────────────┘  │
│                                         │
│  Article content...                     │
│  ...                                    │
│  ...                                    │
└─────────────────────────────────────────┘

Scenario A: Visitor reads article for 30 seconds
            → Banner visible for 30 seconds
            → 1 impression counted ✓

Scenario B: Visitor immediately scrolls down
            → Banner visible for 0.5 seconds
            → NO impression counted ✗

Scenario C: Visitor scrolls down, then back up
            → Banner visible again for 2 seconds
            → 1 impression counted ✓
```

---

## How Impression Tracking Works

### The Tracking Flow

```
┌─────────────────────────────────────────┐
│  1. Page loads with banner              │
│     Banner includes secure token        │
└─────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────┐
│  2. Tracking script observes banner     │
│     Monitors visibility in viewport     │
└─────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────┐
│  3. Banner becomes 50%+ visible         │
│     Timer starts (1 second countdown)   │
└─────────────────────────────────────────┘
                    │
         ┌─────────┴─────────┐
         │                   │
         ▼                   ▼
┌─────────────────┐  ┌─────────────────┐
│ Banner scrolled │  │ Timer completes │
│ away before 1s  │  │ (1 second)      │
│                 │  │                 │
│ Timer cancelled │  │ Check: already  │
│ No impression   │  │ tracked this    │
│                 │  │ session?        │
└─────────────────┘  └─────────────────┘
                              │
                    ┌────────┴────────┐
                    │                 │
                    ▼                 ▼
          ┌─────────────────┐  ┌─────────────────┐
          │ Already tracked │  │ Not yet tracked │
          │ this session    │  │                 │
          │                 │  │ Send impression │
          │ Skip (no        │  │ to server       │
          │ duplicate)      │  │                 │
          └─────────────────┘  └─────────────────┘
                                       │
                                       ▼
                          ┌─────────────────────┐
                          │  4. Server validates │
                          │     security token   │
                          └─────────────────────┘
                                       │
                                       ▼
                          ┌─────────────────────┐
                          │  5. Impression saved │
                          │     to statistics    │
                          └─────────────────────┘
```

### Cache-Safe Design

Traditional tracking breaks with page caching. Simple Add Banners is designed to work correctly:

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         PAGE CACHING SCENARIO                           │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   Cached Page                           Tracking                        │
│   ───────────                           ────────                        │
│                                                                         │
│   ┌─────────────────────┐               ┌─────────────────────┐         │
│   │ HTML with banner    │               │ JavaScript runs     │         │
│   │ (served from cache) │──────────────▶│ fresh each time     │         │
│   │                     │               │                     │         │
│   │ Contains:           │               │ Observes banner,    │         │
│   │ • Banner image      │               │ sends impression    │         │
│   │ • Security token    │               │ only when viewed    │         │
│   └─────────────────────┘               └─────────────────────┘         │
│                                                                         │
│   Result: Accurate counts even with full-page caching                   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## Security Features

### Token-Based Verification

Each banner includes a secure token that prevents fake impressions:

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           SECURITY TOKEN                                │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   Token = HMAC-SHA256( banner_id + placement_id + date, secret_key )    │
│                                                                         │
│   Properties:                                                           │
│   ───────────                                                           │
│   • Cannot be forged without server secret                              │
│   • Valid only for specific banner/placement combination                │
│   • Expires at end of day (prevents replay attacks)                     │
│   • Verified server-side before counting impression                     │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### What This Prevents

| Attack Type | Protection |
|-------------|------------|
| Fake impressions from scripts | Token validation rejects invalid requests |
| Replaying old requests | Tokens expire daily |
| Inflating specific banners | Token tied to specific banner/placement |
| Cross-site request forgery | WordPress nonce verification |

---

## Statistics Storage

### Data Model

Impressions are stored as daily aggregates, not individual events:

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          STATISTICS TABLE                               │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   banner_id │ placement_id │ stat_date  │ impressions │ clicks         │
│   ──────────┼──────────────┼────────────┼─────────────┼────────        │
│      1      │      1       │ 2024-01-15 │     523     │   12           │
│      1      │      1       │ 2024-01-16 │     487     │   15           │
│      1      │      2       │ 2024-01-15 │     234     │    8           │
│      2      │      1       │ 2024-01-15 │     891     │   23           │
│                                                                         │
│   Note: Same banner tracked separately per placement                    │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### Why Daily Aggregation?

```
Individual Events (NOT used)          Daily Aggregates (used)
────────────────────────              ──────────────────────

Store every impression:               Store daily totals:
• Timestamp                           • Date
• Banner ID                           • Banner ID
• Placement ID                        • Placement ID
• IP address (privacy concern)        • Impression count
• User agent                          • Click count
• Session ID
                                      Benefits:
Problems:                             ✓ Privacy-friendly
✗ Massive data storage               ✓ Fast queries
✗ Privacy concerns                   ✓ Small storage footprint
✗ Slow analytics queries             ✓ GDPR-compatible
✗ Complex cleanup
```

---

## Privacy Considerations

Simple Add Banners is designed with privacy in mind:

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          PRIVACY FEATURES                               │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   ✓ NO IP addresses stored                                              │
│     Only aggregate counts, never individual visitor data                │
│                                                                         │
│   ✓ NO cookies required                                                 │
│     Uses session storage (cleared when browser closes)                  │
│                                                                         │
│   ✓ NO cross-site tracking                                              │
│     Data stays on your WordPress site only                              │
│                                                                         │
│   ✓ NO external services                                                │
│     All data processing happens on your server                          │
│                                                                         │
│   ✓ Aggregated data only                                                │
│     Cannot identify individual visitors from statistics                 │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### GDPR Compliance

| Requirement | How We Meet It |
|-------------|----------------|
| Data minimization | Only store aggregate counts |
| Purpose limitation | Data used only for banner statistics |
| Storage limitation | Daily aggregates, no raw events |
| No personal data | No IP, device, or user information stored |

---

## Understanding Your Statistics

### Key Metrics

```
┌─────────────────────────────────────────────────────────────────────────┐
│                                                                         │
│   IMPRESSIONS                                                           │
│   ───────────                                                           │
│   Number of times the banner was actually viewed                        │
│   (50% visible for 1+ second)                                           │
│                                                                         │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   CLICKS (future feature)                                               │
│   ──────                                                                │
│   Number of times visitors clicked the banner                           │
│                                                                         │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   CTR (Click-Through Rate)                                              │
│   ───                                                                   │
│   Percentage of impressions that resulted in clicks                     │
│                                                                         │
│   Formula: (Clicks / Impressions) × 100                                 │
│                                                                         │
│   Example: 15 clicks / 500 impressions = 3% CTR                         │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### Reading Statistics

```
Example Statistics Report:

Banner: "Holiday Sale 2024"
─────────────────────────────────────────────────────────

Date       │ Placement      │ Impressions │ Clicks │ CTR
───────────┼────────────────┼─────────────┼────────┼──────
2024-01-15 │ header-banner  │    523      │   12   │ 2.3%
2024-01-15 │ sidebar-ad     │    234      │    8   │ 3.4%
2024-01-16 │ header-banner  │    487      │   15   │ 3.1%
2024-01-16 │ sidebar-ad     │    198      │    5   │ 2.5%
───────────┼────────────────┼─────────────┼────────┼──────
Total      │                │   1,442     │   40   │ 2.8%

Insights:
• Sidebar placement has higher CTR (more targeted audience?)
• Weekend (Jan 15) had more traffic than weekday
• Overall CTR of 2.8% is healthy for display banners
```

---

## Session-Based Deduplication

### How It Works

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        SESSION STORAGE                                  │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   Browser Session Storage:                                              │
│   ────────────────────────                                              │
│                                                                         │
│   Key: "sab_tracked"                                                    │
│   Value: ["1:1", "2:1", "3:2"]                                          │
│              │                                                          │
│              └── Format: "banner_id:placement_id"                       │
│                                                                         │
│   Meaning: This session has already counted impressions for:            │
│            • Banner 1 in Placement 1                                    │
│            • Banner 2 in Placement 1                                    │
│            • Banner 3 in Placement 2                                    │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### Session Scenarios

| Scenario | New Impression? | Why |
|----------|-----------------|-----|
| First view of banner in session | Yes | Not tracked yet |
| Same banner viewed again | No | Already in session storage |
| Same banner, different placement | Yes | Different placement = different tracking |
| New browser tab | Yes | Separate session storage |
| Browser closed and reopened | Yes | Session storage cleared |
| Page refresh | No | Session persists |

---

## Technical Requirements

### Browser Support

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        BROWSER REQUIREMENTS                             │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   Required Features:                                                    │
│   ──────────────────                                                    │
│                                                                         │
│   • Intersection Observer API                                           │
│     (all modern browsers since 2016+)                                   │
│                                                                         │
│   • Session Storage                                                     │
│     (all modern browsers)                                               │
│                                                                         │
│   • Fetch API                                                           │
│     (all modern browsers since 2015+)                                   │
│                                                                         │
│   Supported:                                                            │
│   ✓ Chrome 58+                                                          │
│   ✓ Firefox 55+                                                         │
│   ✓ Safari 12.1+                                                        │
│   ✓ Edge 16+                                                            │
│   ✓ All modern mobile browsers                                          │
│                                                                         │
│   Graceful Degradation:                                                 │
│   ─────────────────────                                                 │
│   On older browsers, tracking simply doesn't run.                       │
│   Banners still display; only statistics are affected.                  │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### JavaScript Disabled

```
If visitor has JavaScript disabled:
─────────────────────────────────

Banner Display:     ✓ Works (HTML/CSS only)
Impression Tracking: ✗ Does not run
Click Tracking:      ✗ Does not run (future)

This is expected behavior. Banners function without JavaScript;
only the analytics features require it.
```

---

## Troubleshooting

### Impressions Not Being Counted

```
Check these in order:
         │
         ▼
┌──────────────────────────────────────────┐
│ 1. Is JavaScript enabled?                │
│    Check browser developer console       │
│    for errors                            │
└──────────────────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────────┐
│ 2. Is the banner actually visible?       │
│    Must be 50%+ in viewport              │
│    for 1+ second                         │
└──────────────────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────────┐
│ 3. Was it already counted this session?  │
│    Only one impression per session       │
│    Try new private/incognito window      │
└──────────────────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────────┐
│ 4. Check browser console for errors      │
│    Open Developer Tools (F12)            │
│    Look for network or script errors     │
└──────────────────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────────┐
│ 5. Is the REST API accessible?           │
│    Some security plugins block REST API  │
│    Check /wp-json/sab/v1/ endpoint       │
└──────────────────────────────────────────┘
```

### Impression Counts Seem Low

**Possible reasons:**

| Issue | Explanation |
|-------|-------------|
| Banner below the fold | Visitors must scroll down to trigger impression |
| Fast scrollers | Banner not visible for full 1 second |
| Page caching | Normal - tracking works correctly with cache |
| Ad blockers | Some ad blockers may prevent tracking |
| Bot traffic filtered | Good! Bots don't count as real views |

### Impression Counts Seem High

**This is rare with our tracking method, but check:**

| Issue | Solution |
|-------|----------|
| Token being reused | Tokens expire daily, this shouldn't happen |
| Multiple placements | Same banner in multiple spots counts separately |
| High traffic site | Counts are likely accurate |

---

## Common Questions

### Q: Why don't page views equal impressions?

**A:** Page views count when a page loads. Impressions count when a banner is actually seen. If the banner is below the fold and visitors don't scroll, no impression is counted.

### Q: Why does the same banner have different impression counts in different placements?

**A:** Each placement is tracked separately. A banner in the header might get 500 impressions while the same banner in the sidebar gets 200, because visitors see different parts of the page.

### Q: Why did my impressions reset?

**A:** Impressions are counted daily. Each new day starts fresh. Historical data is preserved; you're looking at a new day's counts.

### Q: Can I see which specific visitors saw my banner?

**A:** No, by design. We don't store individual visitor data for privacy reasons. You only see aggregate counts.

### Q: Do bot visits count as impressions?

**A:** Generally no. Most bots don't execute JavaScript, so the tracking script never runs. This gives you more accurate human-visitor statistics.

---

## Quick Reference

### Impression Definition

| Requirement | Value |
|-------------|-------|
| Visibility threshold | 50% of banner in viewport |
| Duration threshold | 1 second minimum |
| Deduplication | One per banner per placement per session |

### Data Collected

| Data Point | Stored? | Notes |
|------------|---------|-------|
| Impression count | Yes | Daily aggregate |
| Click count | Yes | Daily aggregate (future) |
| IP address | No | Never stored |
| User agent | No | Never stored |
| Timestamps | No | Only date, not time |
| Individual events | No | Only aggregates |

### Session Behavior

| Action | New Session? | Impressions Reset? |
|--------|--------------|-------------------|
| Page refresh | No | No |
| New tab (same browser) | No | No |
| Close and reopen browser | Yes | Yes |
| Incognito/private window | Yes | Yes |
| Different browser | Yes | Yes |
| Different device | Yes | Yes |
