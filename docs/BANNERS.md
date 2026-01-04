# Banners Documentation

This document explains how banners work in Simple Add Banners. It is written for anyone to understand, regardless of technical background.

---

## What is a Banner?

A **banner** is an advertisement image that appears on your website. Each banner contains:
- A title (for your reference)
- Images (one for desktop, optionally one for mobile)
- A destination URL (where visitors go when they click)
- Optional scheduling (when the banner should appear)
- A status (active, paused, or scheduled)
- A weight (priority level for rotation)

---

## Banner Lifecycle

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   CREATE    │────▶│   ACTIVE    │────▶│   PAUSED    │────▶│   DELETE    │
│             │     │             │     │             │     │             │
│ Fill form   │     │ Displaying  │     │ Hidden      │     │ Removed     │
│ Save        │     │ on website  │     │ temporarily │     │ permanently │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
                           │                   │
                           │                   │
                           ▼                   ▼
                    ┌─────────────┐     ┌─────────────┐
                    │    EDIT     │     │  REACTIVATE │
                    │             │     │             │
                    │ Update any  │     │ Set status  │
                    │ field       │     │ to active   │
                    └─────────────┘     └─────────────┘
```

---

## Creating a Banner

### Step-by-Step Flow

```
START
  │
  ▼
┌──────────────────────────────────────────┐
│  1. Go to Banners section in admin       │
└──────────────────────────────────────────┘
  │
  ▼
┌──────────────────────────────────────────┐
│  2. Click "Add Banner" button            │
└──────────────────────────────────────────┘
  │
  ▼
┌──────────────────────────────────────────┐
│  3. Fill in the form:                    │
│     • Title (required)                   │
│     • Desktop URL (required)             │
│     • Desktop Image (optional)           │
│     • Mobile Image (optional)            │
│     • Mobile URL (optional)              │
│     • Status (default: Active)           │
│     • Weight (default: 1)                │
│     • Start Date (optional)              │
│     • End Date (optional)                │
└──────────────────────────────────────────┘
  │
  ▼
┌──────────────────────────────────────────┐
│  4. Click "Save" button                  │
└──────────────────────────────────────────┘
  │
  ▼
┌──────────────────────────────────────────┐
│  5. Banner is created and ready to use   │
└──────────────────────────────────────────┘
  │
  ▼
 END
```

### Required vs Optional Fields

| Field | Required? | What it does |
|-------|-----------|--------------|
| Title | **Yes** | Internal name to identify the banner |
| Desktop URL | **Yes** | Where visitors go when they click |
| Desktop Image | No | The image shown on desktop computers |
| Mobile Image | No | The image shown on mobile devices |
| Mobile URL | No | Different destination for mobile users (uses Desktop URL if empty) |
| Status | No | Controls if banner displays (default: Active) |
| Weight | No | Priority in rotation (default: 1) |
| Start Date | No | When the banner starts displaying |
| End Date | No | When the banner stops displaying |

---

## Banner Fields Explained

### Title

The **title** is a name for your banner that only you see. It helps you identify banners in the list.

Examples:
- "Holiday Sale 2024"
- "Newsletter Signup - Header"
- "Partner Ad - Sidebar"

### Desktop URL

The **desktop URL** is the web address where visitors are taken when they click the banner.

Examples:
- `https://yoursite.com/sale`
- `https://partner.com/promo?ref=yoursite`

### Mobile URL (Optional)

If you want mobile visitors to go somewhere different, set a **mobile URL**. If left empty, mobile visitors use the desktop URL.

Use cases:
- Different landing pages for mobile
- App store links for mobile users
- Mobile-optimized promotional pages

### Desktop Image

The **desktop image** is the creative shown on desktop computers and tablets. You select images from the WordPress Media Library.

```
┌─────────────────────────────────────────┐
│  Click "Select Image"                   │
│         │                               │
│         ▼                               │
│  ┌───────────────────────────────────┐  │
│  │   WordPress Media Library opens   │  │
│  │                                   │  │
│  │   • Upload new image              │  │
│  │   • Select existing image         │  │
│  │                                   │  │
│  │   Click "Select"                  │  │
│  └───────────────────────────────────┘  │
│         │                               │
│         ▼                               │
│  Image preview appears in form          │
└─────────────────────────────────────────┘
```

### Mobile Image (Optional)

The **mobile image** is shown on mobile devices. This lets you use a different creative optimized for smaller screens.

If no mobile image is set, the desktop image may be used as fallback.

---

## Banner Status

Every banner has a **status** that controls whether it displays on your website.

### Status Types

```
┌─────────────────────────────────────────────────────────────────────┐
│                                                                     │
│   ACTIVE (Green)                                                    │
│   ─────────────                                                     │
│   The banner is live and will display on your website.              │
│   This is the default status for new banners.                       │
│                                                                     │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│   PAUSED (Yellow)                                                   │
│   ──────────────                                                    │
│   The banner exists but won't display. Use this to                  │
│   temporarily hide a banner without deleting it.                    │
│                                                                     │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│   SCHEDULED (Blue)                                                  │
│   ────────────────                                                  │
│   The banner has scheduling dates set. It will only                 │
│   display within its scheduled time window.                         │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

### Changing Status

```
Current Status: ACTIVE
       │
       ▼
┌──────────────────────┐
│ Edit the banner      │
│       │              │
│       ▼              │
│ Change Status field  │
│       │              │
│       ▼              │
│ Click Save           │
└──────────────────────┘
       │
       ▼
New Status: PAUSED (or your choice)
```

---

## Scheduling Banners

Scheduling lets you control **when** a banner displays automatically.

### How Scheduling Works

```
           Jan 1                    Jan 31
Timeline:  ───────────────────────────────────────────▶
                    │               │
           Start Date               End Date
                    │               │
                    └───────────────┘
                          │
                    Banner displays
                    during this window
```

### Scheduling Scenarios

**Scenario 1: Campaign with Start and End Dates**
```
Start Date: January 15, 2024 at 9:00 AM
End Date:   January 31, 2024 at 11:59 PM

Result: Banner displays only between these dates/times
```

**Scenario 2: Start Date Only**
```
Start Date: February 1, 2024 at 12:00 AM
End Date:   (empty)

Result: Banner starts displaying on Feb 1 and continues indefinitely
```

**Scenario 3: End Date Only**
```
Start Date: (empty)
End Date:   March 15, 2024 at 5:00 PM

Result: Banner displays immediately and stops on March 15
```

**Scenario 4: No Dates**
```
Start Date: (empty)
End Date:   (empty)

Result: Banner displays continuously (controlled only by status)
```

### Setting Schedule Dates

```
┌─────────────────────────────────────────┐
│  1. Edit the banner                     │
└─────────────────────────────────────────┘
           │
           ▼
┌─────────────────────────────────────────┐
│  2. Click the Start Date field          │
│     • Calendar picker opens             │
│     • Select date                       │
│     • Select time (optional)            │
└─────────────────────────────────────────┘
           │
           ▼
┌─────────────────────────────────────────┐
│  3. Click the End Date field            │
│     • Calendar picker opens             │
│     • Select date                       │
│     • Select time (optional)            │
└─────────────────────────────────────────┘
           │
           ▼
┌─────────────────────────────────────────┐
│  4. Click Save                          │
└─────────────────────────────────────────┘
```

---

## Banner Weight (Priority)

The **weight** determines how often a banner appears compared to other banners in the same placement.

### Understanding Weight

- Weight is a number from **1 to 100**
- Higher weight = **more frequent** display
- Default weight is **1**

### Weight Examples

**Example 1: Equal Weight**
```
Banner A: Weight 1
Banner B: Weight 1
────────────────────
Each banner shows 50% of the time
```

**Example 2: Different Weights**
```
Banner A: Weight 1
Banner B: Weight 3
────────────────────
Banner A shows 25% of the time (1 out of 4)
Banner B shows 75% of the time (3 out of 4)
```

**Example 3: Multiple Banners**
```
Banner A: Weight 2
Banner B: Weight 2
Banner C: Weight 1
────────────────────
Total weight: 5
Banner A: 40% (2/5)
Banner B: 40% (2/5)
Banner C: 20% (1/5)
```

### When to Use Weight

| Situation | Recommendation |
|-----------|----------------|
| All banners equally important | Use weight 1 for all |
| One banner is a priority | Give it higher weight (e.g., 5) |
| Testing a new banner | Start with weight 1, increase if successful |
| Premium advertiser | Higher weight = more exposure |

---

## Managing Banners

### Viewing All Banners

The banner list shows all your banners in a table:

```
┌────────────────────────────────────────────────────────────────────────┐
│  Banners                                              [Add Banner]     │
├────────────────────────────────────────────────────────────────────────┤
│  ID │ Title              │ Status   │ Weight │ Schedule    │ Actions  │
├────────────────────────────────────────────────────────────────────────┤
│  1  │ Holiday Sale       │ [Active] │   5    │ Dec 1-31    │ ✏️  🗑️   │
│  2  │ Newsletter CTA     │ [Active] │   1    │ -           │ ✏️  🗑️   │
│  3  │ Partner Ad         │ [Paused] │   2    │ -           │ ✏️  🗑️   │
│  4  │ Spring Campaign    │ [Sched]  │   3    │ Mar 1-Apr 30│ ✏️  🗑️   │
└────────────────────────────────────────────────────────────────────────┘
```

### Editing a Banner

```
┌──────────────────────┐
│ 1. Find the banner   │
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
│ 3. Make your changes │
│    in the form       │
└──────────────────────┘
         │
         ▼
┌──────────────────────┐
│ 4. Click Save        │
└──────────────────────┘
         │
         ▼
┌──────────────────────┐
│ 5. Success! Banner   │
│    is updated        │
└──────────────────────┘
```

### Deleting a Banner

```
┌──────────────────────┐
│ 1. Find the banner   │
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
│     this banner?"                        │
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
│ 5. Banner is         │
│    permanently       │
│    removed           │
└──────────────────────┘
```

**Warning:** Deleting a banner is permanent and cannot be undone!

---

## Common Use Cases

### Use Case 1: Simple Banner (Always Active)

**Scenario:** You want a banner that's always visible.

```
Settings:
• Title: "Subscribe to Newsletter"
• Desktop URL: https://yoursite.com/subscribe
• Desktop Image: newsletter-banner.jpg
• Status: Active
• Weight: 1
• Start/End Date: (leave empty)
```

### Use Case 2: Time-Limited Promotion

**Scenario:** Black Friday sale running Nov 24-27.

```
Settings:
• Title: "Black Friday Sale 2024"
• Desktop URL: https://yoursite.com/black-friday
• Desktop Image: black-friday-banner.jpg
• Mobile Image: black-friday-mobile.jpg
• Status: Scheduled
• Start Date: November 24, 2024 12:00 AM
• End Date: November 27, 2024 11:59 PM
```

### Use Case 3: Mobile-Specific Campaign

**Scenario:** Promote your mobile app to mobile users.

```
Settings:
• Title: "Download Our App"
• Desktop URL: https://yoursite.com/mobile-app
• Mobile URL: https://apps.apple.com/yourapp
• Desktop Image: app-banner-wide.jpg
• Mobile Image: app-banner-square.jpg
• Status: Active
```

### Use Case 4: Priority Advertiser

**Scenario:** An advertiser pays more for higher visibility.

```
Settings:
• Title: "Premium Partner Ad"
• Desktop URL: https://partner.com/offer
• Desktop Image: partner-banner.jpg
• Status: Active
• Weight: 10 (shows 10x more often than weight-1 banners)
```

### Use Case 5: Seasonal Banner

**Scenario:** Holiday theme only during December.

```
Settings:
• Title: "Holiday Greetings"
• Desktop URL: https://yoursite.com/holidays
• Desktop Image: holiday-banner.jpg
• Status: Scheduled
• Start Date: December 1, 2024
• End Date: December 31, 2024
```

---

## Troubleshooting

### Banner Not Showing

```
Check these in order:
         │
         ▼
┌──────────────────────────────────────────┐
│ 1. Is status set to "Active"?            │
│    If "Paused", change to "Active"       │
└──────────────────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────────┐
│ 2. Check scheduling dates                │
│    Is today within the start/end range?  │
└──────────────────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────────┐
│ 3. Is banner assigned to a placement?    │
│    (See Placements documentation)        │
└──────────────────────────────────────────┘
         │
         ▼
┌──────────────────────────────────────────┐
│ 4. Clear your browser cache              │
│    and website cache                     │
└──────────────────────────────────────────┘
```

### Image Not Displaying

- Verify the image exists in WordPress Media Library
- Check that the image URL is accessible
- Ensure the image file wasn't deleted from the server

### Banner Showing Too Often/Rarely

- Adjust the **weight** value
- Higher weight = more frequent display
- Lower weight = less frequent display

---

## Quick Reference

### Banner Fields Summary

| Field | Required | Default | Description |
|-------|----------|---------|-------------|
| Title | Yes | - | Internal identifier |
| Desktop URL | Yes | - | Click destination |
| Mobile URL | No | Desktop URL | Mobile click destination |
| Desktop Image | No | - | Desktop creative |
| Mobile Image | No | - | Mobile creative |
| Status | No | Active | Display control |
| Weight | No | 1 | Rotation priority |
| Start Date | No | - | Schedule start |
| End Date | No | - | Schedule end |

### Status Quick Reference

| Status | Shows on Website? | Use When |
|--------|-------------------|----------|
| Active | Yes | Banner should display now |
| Paused | No | Temporarily hide banner |
| Scheduled | Depends on dates | Time-based campaigns |

### Weight Quick Reference

| Weight | Display Frequency |
|--------|-------------------|
| 1 | Normal (baseline) |
| 2-3 | Slightly more often |
| 5-10 | Noticeably more often |
| 50-100 | Much more often |
