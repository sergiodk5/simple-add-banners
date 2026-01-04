# Placements Documentation

This document explains how placements work in Simple Add Banners. It is written for anyone to understand, regardless of technical background.

---

## What is a Placement?

A **placement** is a designated location on your website where banners appear. Think of it as a "slot" or "container" that holds one or more banners.

Each placement has:
- A **name** (for your reference)
- A **slug** (a unique identifier used in shortcodes)
- A **rotation strategy** (how banners take turns appearing)
- **Assigned banners** (which banners can appear in this slot)

---

## How Placements and Banners Work Together

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           YOUR WEBSITE                                  │
│                                                                         │
│   ┌─────────────────────────────────────────────────────────────────┐   │
│   │                     HEADER PLACEMENT                            │   │
│   │                                                                 │   │
│   │   Contains: Banner A, Banner B, Banner C                        │   │
│   │   Strategy: Random                                              │   │
│   │                                                                 │   │
│   │   → Each page load shows ONE of these banners randomly          │   │
│   └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│   ┌───────────────────────┐     ┌───────────────────────┐              │
│   │   SIDEBAR PLACEMENT   │     │   FOOTER PLACEMENT    │              │
│   │                       │     │                       │              │
│   │   Contains: Banner D  │     │   Contains: Banner A  │              │
│   │   Strategy: Weighted  │     │            Banner E   │              │
│   │                       │     │   Strategy: Ordered   │              │
│   └───────────────────────┘     └───────────────────────┘              │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

Note: Banner A appears in TWO placements (Header and Footer)
      This is allowed - banners can be reused across placements!
```

---

## Placement Lifecycle

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   CREATE    │────▶│   ASSIGN    │────▶│    USE      │────▶│   DELETE    │
│             │     │   BANNERS   │     │             │     │             │
│ Set name,   │     │             │     │ Add to      │     │ Remove      │
│ slug, and   │     │ Choose      │     │ website     │     │ permanently │
│ strategy    │     │ banners     │     │ via         │     │             │
│             │     │             │     │ shortcode   │     │             │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
                           │                   │
                           │                   │
                           ▼                   ▼
                    ┌─────────────┐     ┌─────────────┐
                    │   MODIFY    │     │    EDIT     │
                    │   BANNERS   │     │             │
                    │             │     │ Change      │
                    │ Add/remove  │     │ name or     │
                    │ banners     │     │ strategy    │
                    └─────────────┘     └─────────────┘
```

---

## Creating a Placement

### Step-by-Step Flow

```
START
  │
  ▼
┌──────────────────────────────────────────┐
│  1. Go to Placements section in admin    │
└──────────────────────────────────────────┘
  │
  ▼
┌──────────────────────────────────────────┐
│  2. Click "Add Placement" button         │
└──────────────────────────────────────────┘
  │
  ▼
┌──────────────────────────────────────────┐
│  3. Fill in the form:                    │
│     • Name (required)                    │
│     • Slug (required, auto-generated)    │
│     • Rotation Strategy (default: Random)│
└──────────────────────────────────────────┘
  │
  ▼
┌──────────────────────────────────────────┐
│  4. Click "Save" button                  │
└──────────────────────────────────────────┘
  │
  ▼
┌──────────────────────────────────────────┐
│  5. Placement is created!                │
│     Now assign banners to it             │
└──────────────────────────────────────────┘
  │
  ▼
 END
```

### Required Fields

| Field | Required? | What it does |
|-------|-----------|--------------|
| Name | **Yes** | Display name for your reference (e.g., "Header Banner Slot") |
| Slug | **Yes** | Unique identifier used in shortcodes (e.g., "header-banner") |
| Rotation Strategy | No | How banners rotate (default: Random) |

---

## Placement Fields Explained

### Name

The **name** is a human-readable label that helps you identify the placement.

Examples:
- "Header Banner Slot"
- "Sidebar Advertisement"
- "Footer Promotional Area"
- "Between Posts Banner"

### Slug

The **slug** is a unique technical identifier used in shortcodes to display the placement on your website.

**Slug Rules:**
```
┌─────────────────────────────────────────────────────────────────────────┐
│  ALLOWED                          │  NOT ALLOWED                        │
├───────────────────────────────────┼─────────────────────────────────────┤
│  ✓ lowercase letters (a-z)        │  ✗ UPPERCASE letters                │
│  ✓ numbers (0-9)                  │  ✗ spaces                           │
│  ✓ hyphens (-)                    │  ✗ underscores (_)                  │
│                                   │  ✗ special characters (!@#$%^&*)    │
└───────────────────────────────────┴─────────────────────────────────────┘
```

**Good slug examples:**
- `header-banner`
- `sidebar-ad-1`
- `footer-promo`
- `post-bottom`

**Bad slug examples:**
- `Header Banner` (has spaces and uppercase)
- `sidebar_ad` (has underscore)
- `footer@promo` (has special character)

### Auto-Generated Slug

When you type a name, the slug is automatically generated:

```
Name you type:          Slug generated:
─────────────────────────────────────────
"Header Banner Slot" →  "header-banner-slot"
"Sidebar Ad #1"      →  "sidebar-ad-1"
"FOOTER Promo"       →  "footer-promo"
```

You can edit the slug manually if you prefer a different one.

---

## Rotation Strategies

The **rotation strategy** determines how multiple banners take turns appearing in a placement.

### Strategy Comparison

```
┌─────────────────────────────────────────────────────────────────────────┐
│                              RANDOM                                     │
│                                                                         │
│  How it works: Each page load picks a random banner                     │
│                                                                         │
│  Page Load 1:  [Banner B]                                               │
│  Page Load 2:  [Banner A]                                               │
│  Page Load 3:  [Banner B]                                               │
│  Page Load 4:  [Banner C]                                               │
│  Page Load 5:  [Banner A]                                               │
│                                                                         │
│  Best for: Variety, A/B testing, equal exposure                         │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                             WEIGHTED                                    │
│                                                                         │
│  How it works: Banners with higher weight appear more often             │
│                                                                         │
│  Banner A (weight: 3)  ████████████████████████  75%                    │
│  Banner B (weight: 1)  ████████                  25%                    │
│                                                                         │
│  Best for: Prioritizing certain banners, premium advertisers            │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                             SEQUENTIAL                                  │
│                                                                         │
│  How it works: Banners display in a fixed order, cycling through        │
│                                                                         │
│  Position 1: [Banner A] → Position 2: [Banner B] → Position 3: [Banner C]
│       │                                                    │            │
│       └────────────────────────────────────────────────────┘            │
│                         (cycles back)                                   │
│                                                                         │
│  Best for: Guaranteed order, scheduled rotations                        │
└─────────────────────────────────────────────────────────────────────────┘
```

### Choosing a Strategy

| Use Case | Recommended Strategy |
|----------|---------------------|
| Equal exposure for all banners | Random |
| Testing which banner performs better | Random |
| Premium advertiser gets more visibility | Weighted |
| Some banners are more important | Weighted |
| Banners must appear in specific order | Sequential |
| Day-of-week or time-based rotation | Sequential |

### Strategy Details

#### Random Strategy

```
Placement has: Banner A, Banner B, Banner C

Visitor 1 sees: Banner C (randomly selected)
Visitor 2 sees: Banner A (randomly selected)
Visitor 3 sees: Banner C (randomly selected)
Visitor 4 sees: Banner B (randomly selected)

Over time: Each banner gets roughly equal display time
```

#### Weighted Strategy

```
Placement has:
  Banner A (weight: 5)
  Banner B (weight: 3)
  Banner C (weight: 2)

Total weight: 10

Display probability:
  Banner A: 5/10 = 50% of impressions
  Banner B: 3/10 = 30% of impressions
  Banner C: 2/10 = 20% of impressions
```

#### Sequential Strategy

```
Placement has (in order):
  Position 0: Banner A
  Position 1: Banner B
  Position 2: Banner C

Display pattern:
  First impression:  Banner A
  Second impression: Banner B
  Third impression:  Banner C
  Fourth impression: Banner A (cycles back)
  ...and so on
```

---

## Assigning Banners to a Placement

After creating a placement, you need to assign banners to it.

### Assignment Flow

```
START
  │
  ▼
┌──────────────────────────────────────────┐
│  1. Go to Placements list                │
└──────────────────────────────────────────┘
  │
  ▼
┌──────────────────────────────────────────┐
│  2. Find your placement                  │
└──────────────────────────────────────────┘
  │
  ▼
┌──────────────────────────────────────────┐
│  3. Click the "Manage Banners" icon      │
│     (image/gallery icon)                 │
└──────────────────────────────────────────┘
  │
  ▼
┌──────────────────────────────────────────┐
│  4. Banner selection screen appears:     │
│                                          │
│     ☑ Banner A  [Active]   Weight: 1     │
│     ☐ Banner B  [Paused]   Weight: 2     │
│     ☑ Banner C  [Active]   Weight: 5     │
│     ☐ Banner D  [Active]   Weight: 1     │
│                                          │
│     2 banner(s) selected                 │
└──────────────────────────────────────────┘
  │
  ▼
┌──────────────────────────────────────────┐
│  5. Check/uncheck banners you want       │
└──────────────────────────────────────────┘
  │
  ▼
┌──────────────────────────────────────────┐
│  6. Click "Save" button                  │
└──────────────────────────────────────────┘
  │
  ▼
┌──────────────────────────────────────────┐
│  7. Banners are now assigned!            │
│     Placement is ready to use            │
└──────────────────────────────────────────┘
  │
  ▼
 END
```

### Important Notes About Banner Assignment

```
┌─────────────────────────────────────────────────────────────────────────┐
│                                                                         │
│  ⚠️  SAVE REPLACES ALL ASSIGNMENTS                                      │
│                                                                         │
│  When you click Save, the placement will have ONLY the banners          │
│  you have checked. Any previously assigned banners that are             │
│  now unchecked will be REMOVED from this placement.                     │
│                                                                         │
│  Example:                                                               │
│  Before: Placement has Banner A, Banner B                               │
│  You check: Banner B, Banner C                                          │
│  After save: Placement has Banner B, Banner C (Banner A removed)        │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### What You See in the Assignment Screen

| Column | Description |
|--------|-------------|
| Checkbox | Check to include banner in this placement |
| Banner Title | The name of the banner |
| Status | Active (green), Paused (yellow), or Scheduled (blue) |
| Weight | The banner's weight value (used for weighted rotation) |

---

## Using Placements on Your Website

Once a placement is created and has banners assigned, display it using a **shortcode**.

### Shortcode Format

```
[sab_banner placement="your-slug-here"]
```

### Examples

```
Placement slug: header-banner
Shortcode:      [sab_banner placement="header-banner"]

Placement slug: sidebar-ad-1
Shortcode:      [sab_banner placement="sidebar-ad-1"]

Placement slug: footer-promo
Shortcode:      [sab_banner placement="footer-promo"]
```

### Where to Use Shortcodes

You can place the shortcode in:
- **Pages**: In the page content editor
- **Posts**: In the post content editor
- **Widgets**: In a text widget or shortcode widget
- **Theme files**: Using PHP `do_shortcode()` function

### Shortcode Flow

```
┌─────────────────────────────────────────┐
│  Visitor loads your webpage             │
└─────────────────────────────────────────┘
            │
            ▼
┌─────────────────────────────────────────┐
│  WordPress finds shortcode:             │
│  [sab_banner placement="header-banner"] │
└─────────────────────────────────────────┘
            │
            ▼
┌─────────────────────────────────────────┐
│  Plugin looks up "header-banner"        │
│  placement                              │
└─────────────────────────────────────────┘
            │
            ▼
┌─────────────────────────────────────────┐
│  Gets all assigned banners              │
│  that are active and within schedule    │
└─────────────────────────────────────────┘
            │
            ▼
┌─────────────────────────────────────────┐
│  Selects ONE banner based on            │
│  rotation strategy:                     │
│                                         │
│  • Random: Pick randomly                │
│  • Weighted: Pick by weight probability │
│  • Sequential: Pick next in order       │
└─────────────────────────────────────────┘
            │
            ▼
┌─────────────────────────────────────────┐
│  Displays the selected banner           │
│  (appropriate image for device)         │
└─────────────────────────────────────────┘
```

---

## Managing Placements

### Viewing All Placements

The placement list shows all your placements in a table:

```
┌────────────────────────────────────────────────────────────────────────────┐
│  Placements                                              [Add Placement]   │
├────────────────────────────────────────────────────────────────────────────┤
│  ID │ Name                │ Slug            │ Strategy   │ Created │ Actions│
├────────────────────────────────────────────────────────────────────────────┤
│  1  │ Header Banner Slot  │ header-banner   │ [Random]   │ Jan 1   │ ✏️ 🖼️ 🗑️│
│  2  │ Sidebar Ad          │ sidebar-ad      │ [Weighted] │ Jan 2   │ ✏️ 🖼️ 🗑️│
│  3  │ Footer Promo        │ footer-promo    │ [Sequential]│ Jan 3   │ ✏️ 🖼️ 🗑️│
└────────────────────────────────────────────────────────────────────────────┘

Action icons:
  ✏️  = Edit placement settings
  🖼️  = Manage assigned banners
  🗑️  = Delete placement
```

### Editing a Placement

```
┌──────────────────────┐
│ 1. Find placement    │
│    in the list       │
└──────────────────────┘
         │
         ▼
┌──────────────────────┐
│ 2. Click the edit    │
│    icon (✏️)         │
└──────────────────────┘
         │
         ▼
┌──────────────────────┐
│ 3. Make changes:     │
│    • Name            │
│    • Slug            │
│    • Strategy        │
└──────────────────────┘
         │
         ▼
┌──────────────────────┐
│ 4. Click Save        │
└──────────────────────┘
         │
         ▼
┌──────────────────────┐
│ 5. Success!          │
└──────────────────────┘
```

**Note:** If you change the slug, you must also update any shortcodes using the old slug!

### Deleting a Placement

```
┌──────────────────────┐
│ 1. Find placement    │
│    in the list       │
└──────────────────────┘
         │
         ▼
┌──────────────────────┐
│ 2. Click the delete  │
│    icon (🗑️)         │
└──────────────────────┘
         │
         ▼
┌──────────────────────────────────────────┐
│ 3. Confirmation dialog appears:          │
│                                          │
│    "Are you sure you want to delete      │
│     this placement?"                     │
│                                          │
│    [Cancel]  [Delete]                    │
└──────────────────────────────────────────┘
         │
         ▼
┌──────────────────────┐
│ 4. Click Delete to   │
│    confirm           │
└──────────────────────┘
         │
         ▼
┌──────────────────────┐
│ 5. Placement is      │
│    permanently       │
│    removed           │
└──────────────────────┘
```

**What happens when you delete a placement:**
- The placement is permanently removed
- All banner assignments to this placement are removed
- The banners themselves are NOT deleted (they still exist)
- Any shortcodes using this placement will stop working

---

## Common Use Cases

### Use Case 1: Simple Header Banner

**Scenario:** One rotating banner area at the top of your site.

```
Placement Settings:
• Name: "Header Banner"
• Slug: "header-banner"
• Strategy: Random

Assigned Banners:
• Holiday Sale Banner
• Newsletter Signup Banner
• New Product Banner

Shortcode: [sab_banner placement="header-banner"]

Result: Each page load shows one of the three banners randomly
```

### Use Case 2: Priority Advertiser

**Scenario:** One advertiser pays more for extra visibility.

```
Placement Settings:
• Name: "Sidebar Premium Spot"
• Slug: "sidebar-premium"
• Strategy: Weighted

Assigned Banners:
• Premium Partner (weight: 10)
• Regular Ad 1 (weight: 2)
• Regular Ad 2 (weight: 2)

Result:
  Premium Partner: ~71% of impressions
  Regular Ad 1:    ~14% of impressions
  Regular Ad 2:    ~14% of impressions
```

### Use Case 3: Rotating Sponsor Banners

**Scenario:** Three sponsors who must get equal, sequential exposure.

```
Placement Settings:
• Name: "Sponsor Rotation"
• Slug: "sponsor-rotation"
• Strategy: Sequential

Assigned Banners (in order):
• Position 0: Sponsor A
• Position 1: Sponsor B
• Position 2: Sponsor C

Result: Sponsors appear in strict A → B → C → A → B → C order
```

### Use Case 4: Multiple Placements, Same Banner

**Scenario:** Show your newsletter banner in multiple locations.

```
Banner: "Subscribe to Newsletter"

Placement 1: "Header Banner" (slug: header-banner)
  → Newsletter banner assigned here

Placement 2: "Sidebar Widget" (slug: sidebar-widget)
  → Newsletter banner assigned here

Placement 3: "Post Footer" (slug: post-footer)
  → Newsletter banner assigned here

Result: Same banner can appear in three different website locations
```

### Use Case 5: A/B Testing

**Scenario:** Test two versions of a promotional banner.

```
Placement Settings:
• Name: "Promo Test"
• Slug: "promo-test"
• Strategy: Random

Assigned Banners:
• Promo Version A (red design)
• Promo Version B (blue design)

Result: 50/50 split between versions
        Track clicks to see which performs better
```

---

## Relationship Between Banners and Placements

### Many-to-Many Relationship

```
┌─────────────────────────────────────────────────────────────────────────┐
│                                                                         │
│   BANNERS                              PLACEMENTS                       │
│   ───────                              ──────────                       │
│                                                                         │
│   ┌─────────────┐                      ┌─────────────────┐              │
│   │  Banner A   │─────────────────────▶│ Header          │              │
│   └─────────────┘                 ┌───▶│ (header-banner) │              │
│         │                         │    └─────────────────┘              │
│         │                         │                                     │
│         │    ┌────────────────────┘                                     │
│         │    │                                                          │
│         ▼    │                                                          │
│   ┌─────────────┐                      ┌─────────────────┐              │
│   │  Banner B   │─────────────────────▶│ Sidebar         │              │
│   └─────────────┘                 ┌───▶│ (sidebar-ad)    │              │
│         │                         │    └─────────────────┘              │
│         │                         │                                     │
│         └─────────────────────────┤                                     │
│                                   │                                     │
│   ┌─────────────┐                 │    ┌─────────────────┐              │
│   │  Banner C   │─────────────────┴───▶│ Footer          │              │
│   └─────────────┘                      │ (footer-promo)  │              │
│                                        └─────────────────┘              │
│                                                                         │
│   Note: Banner B appears in Sidebar AND Footer placements               │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### Key Points

| Concept | Explanation |
|---------|-------------|
| One banner, many placements | A single banner can be assigned to multiple placements |
| One placement, many banners | A placement can have multiple banners (they rotate) |
| Independent | Changing a banner's assignments doesn't affect the banner itself |
| No duplicates | A banner can only be assigned to a specific placement once |

---

## Troubleshooting

### Placement Shortcode Not Working

```
Check these in order:
         │
         ▼
┌──────────────────────────────────────────┐
│ 1. Is the slug spelled correctly?        │
│    Compare shortcode to placement list   │
└──────────────────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────────┐
│ 2. Does the placement exist?             │
│    Check Placements list in admin        │
└──────────────────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────────┐
│ 3. Are any banners assigned?             │
│    Empty placement shows nothing         │
└──────────────────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────────┐
│ 4. Are assigned banners active?          │
│    Paused banners don't display          │
└──────────────────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────────┐
│ 5. Check banner scheduling               │
│    Are banners within date range?        │
└──────────────────────────────────────────┘
```

### Can't Save Placement (Slug Error)

**Problem:** "Slug already exists" error

**Solution:**
- Each placement must have a unique slug
- Choose a different slug
- Check existing placements for conflicts

**Problem:** "Invalid slug format" error

**Solution:**
- Use only lowercase letters, numbers, and hyphens
- No spaces, underscores, or special characters

### Banners Not Rotating as Expected

**For Random strategy:**
- With few page loads, distribution may seem uneven
- Over many impressions, it will balance out

**For Weighted strategy:**
- Check that banner weights are set correctly
- Higher weight = more frequent display

**For Sequential strategy:**
- Banners cycle in their assigned position order
- Position 0 comes first, then 1, then 2, etc.

---

## Quick Reference

### Placement Fields Summary

| Field | Required | Default | Description |
|-------|----------|---------|-------------|
| Name | Yes | - | Display name for your reference |
| Slug | Yes | Auto-generated | Unique ID for shortcodes |
| Rotation Strategy | No | Random | How banners rotate |

### Rotation Strategy Summary

| Strategy | Behavior | Use When |
|----------|----------|----------|
| Random | Random selection each time | Equal exposure, testing |
| Weighted | Selection based on weight | Priority advertisers |
| Sequential | Fixed order rotation | Guaranteed sequence |

### Shortcode Quick Reference

```
Basic usage:
[sab_banner placement="your-slug"]

Examples:
[sab_banner placement="header-banner"]
[sab_banner placement="sidebar-ad"]
[sab_banner placement="footer-promo"]
```

### Slug Naming Rules

| Allowed | Not Allowed |
|---------|-------------|
| lowercase letters (a-z) | UPPERCASE |
| numbers (0-9) | spaces |
| hyphens (-) | underscores (_) |
| | special characters |
