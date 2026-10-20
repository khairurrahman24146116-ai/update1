---
name: Stitch Student Portal
colors:
  surface: '#faf9f5'
  surface-dim: '#dadad6'
  surface-bright: '#faf9f5'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f4f4f0'
  surface-container: '#eeeeea'
  surface-container-high: '#e8e8e4'
  surface-container-highest: '#e2e3df'
  on-surface: '#1a1c1a'
  on-surface-variant: '#44474e'
  inverse-surface: '#2f312e'
  inverse-on-surface: '#f1f1ed'
  outline: '#75777f'
  outline-variant: '#c4c6cf'
  surface-tint: '#4a5e86'
  primary: '#000d27'
  on-primary: '#ffffff'
  primary-container: '#0b2347'
  on-primary-container: '#778bb5'
  inverse-primary: '#b2c7f4'
  secondary: '#455e8f'
  on-secondary: '#ffffff'
  secondary-container: '#adc7fe'
  on-secondary-container: '#395282'
  tertiary: '#010e22'
  on-tertiary: '#ffffff'
  tertiary-container: '#162439'
  on-tertiary-container: '#7d8ba5'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#d7e2ff'
  primary-fixed-dim: '#b2c7f4'
  on-primary-fixed: '#011b3f'
  on-primary-fixed-variant: '#32476c'
  secondary-fixed: '#d8e2ff'
  secondary-fixed-dim: '#adc7fe'
  on-secondary-fixed: '#001a41'
  on-secondary-fixed-variant: '#2c4675'
  tertiary-fixed: '#d5e3ff'
  tertiary-fixed-dim: '#b9c7e2'
  on-tertiary-fixed: '#0d1c30'
  on-tertiary-fixed-variant: '#3a475e'
  background: '#faf9f5'
  on-background: '#1a1c1a'
  surface-variant: '#e2e3df'
typography:
  display-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 40px
    fontWeight: '700'
    lineHeight: 48px
    letterSpacing: -0.03em
  display-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.025em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 36px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 22px
    fontWeight: '600'
    lineHeight: 30px
    letterSpacing: -0.015em
  headline-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 26px
    letterSpacing: -0.01em
  body-lg:
    fontFamily: DM Sans
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 26px
    letterSpacing: -0.01em
  body-md:
    fontFamily: DM Sans
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 22px
    letterSpacing: 0em
  body-sm:
    fontFamily: DM Sans
    fontSize: 12px
    fontWeight: '400'
    lineHeight: 18px
    letterSpacing: 0.01em
  label-md:
    fontFamily: DM Sans
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.02em
  label-sm:
    fontFamily: DM Sans
    fontSize: 11px
    fontWeight: '500'
    lineHeight: 14px
    letterSpacing: 0.04em
  label-lg:
    fontFamily: DM Sans
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
    letterSpacing: 0em
  telemetry-tag:
    fontFamily: DM Sans
    fontSize: 11px
    fontWeight: '500'
    lineHeight: 14px
    letterSpacing: 0.04em
  telemetry-code:
    fontFamily: DM Sans
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 22px
    letterSpacing: 0em
rounded:
  DEFAULT: 1rem
  lg: 2rem
  xl: 3rem
  full: 9999px
spacing:
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2.5rem
  gutter: 1.5rem
  gutter-mobile: 1rem
  margin: 3rem
  margin-mobile: 1.25rem
  container-max: 80rem
---

## Brand & Style

This design system expresses a "Premium Sovereign Identity" vernacular engineered for an Indonesian modern Islamic senior high school. It synthesizes institutional prestige, calm authority, and a modern premium product sensibility.

The aesthetic is governed by a calibrated tension:
- **Sovereign Navy (`#0b2347`)**: Anchors primary touchpoints — buttons, primary containers, and navigation. Signals academic rigor, institutional integrity, and a quiet premium feel.
- **Warm Canvas (`#faf9f5`)**: Warm off-white surfaces provide a soft, editorial backdrop that reduces glare and reads as premium.
- **Soft Elevation**: Depth is expressed with soft diffuse shadows (`0 1px 3px rgba(11,35,71,0.04)`, hover `0 6px 16px rgba(11,35,71,0.08)`) rather than hard offsets.
- **Pill Geometry**: Buttons, inputs, badges, and chips use full pill radii (`rounded-full`); cards and sections use `1rem`–`3rem` radii.
- **Telemetry Labels**: NISN/NSS and code identifiers use DM Sans with `font-variant-numeric: tabular-nums` for clean aligned numerals.

The interface evokes trustworthy institutional authority with a contemporary premium product feel — authoritative, calm, and legible without being bureaucratic.

## Colors

- **Primary Aura (`#000d27`) & Primary Container (`#0b2347` - Sovereign Navy):** Main brand touchpoints, primary CTAs, headers. On-container text `#778bb5`.
- **Secondary (`#455e8f` - Slate Indigo) / Container (`#adc7fe`):** Secondary actions, success states, academic pathways.
- **Tertiary (`#010e22`) / Container (`#162439`):** Accent layers and supporting surfaces.
- **Warm Neutrals:** `#faf9f5` base surface, `#ffffff` elevated cards, `#f4f4f0` → `#e2e3df` container steps.
- **Ink (`#1a1c1a`):** High-contrast on-surface text ensuring legibility.

## Typography

1. **Headlines (`Plus Jakarta Sans`):** display/headline/label hierarchy with negative letter-spacing for a tight, premium editorial density.
2. **Body & Interface (`DM Sans`):** Neutral, geometric-calm, optimized for dense schedules, fee tables, and official notices.
3. **Telemetry / Meta (`DM Sans` + `tabular-nums`):** NISN identifiers, room coordinates, accreditation chips — aligned numerals keep data tables clean.

## Layout & Spacing

- **Desktop (1024px+):** 12-col grid, `1.5rem` gutters, `3rem` page margins, max container `80rem`.
- **Tablet (640–1023px):** 8-col grid, `1.5rem` gutters, `1.25rem` margins.
- **Mobile (320–639px):** 4-col grid, `1rem` gutters, `1.25rem` safe margins.
- Spacing scale: `0.25/0.5/1/1.5/2.5rem` (xs/sm/md/lg/xl).
- Vertical cadence uses generous section separations to give academic content breathing room against soft card surfaces.

## Elevation & Depth

Elevation is expressed with soft diffuse shadows keyed to the navy tint:

- **Tier 0 (Canvas):** Flat warm base `#faf9f5`.
- **Tier 1 (Static Card):** White surface, `1px solid #c4c6cf` outline-variant, `box-shadow: 0 1px 3px rgba(11,35,71,0.04)`.
- **Tier 2 (Hover / Priority):** `box-shadow: 0 6px 16px rgba(11,35,71,0.08)`, translateY(-2px) on hover.
- **Tier 3 (Floating HUD / Nav):** `background: rgba(255,255,255,0.85); backdrop-filter: blur(12px); border-bottom: 1px solid var(--color-outline-variant)`.
- **Tactile Inset (Inputs):** `box-shadow: inset 0 1px 2px rgba(11,35,71,0.04)` with a soft 3px navy focus ring.

## Shapes

- Default radii: small `0.5rem`, DEFAULT `1rem`, lg `2rem`, xl `3rem`, `full 9999px`.
- Buttons, inputs, badges, chips: pill (`rounded-full`).
- Cards / sections: `rounded-DEFAULT` (1rem) to `rounded-[28px]`.
- Circular trims for avatar frames and seal stamps.

## Components

### Buttons
- **Primary Button:** `#0b2347` Sovereign Navy background, white text, pill (`rounded-full`), soft shadow. Hover deepen to `#000d27`, translateY(-1px).
- **Secondary Button:** `#adc7fe` container background, `#395282` text, pill.
- **Ghost / Glass Button:** white translucency, `backdrop-blur`, outline-variant border, `#1a1c1a` text.

### Form Inputs & Search Fields
- **Text Inputs:** White, `1px solid #c4c6cf`, soft inset shadow, pill. Focus: navy border + soft 3px ring.

### Cards & Panels
- **Standard Card:** White `#ffffff`, `1px solid #c4c6cf`, `1rem` radius, `0 1px 3px rgba(11,35,71,0.04)`, 24px padding.

### Chips & Badges
- **Status Pill:** pill-shaped, outline-variant border, DM Sans 11px uppercase with tracking.

### Navigation Bars
- **Desktop Header:** sticky glass (white/85 + blur), `1px` outline-variant bottom border. Brand left, primary nav with active state in navy, pill accent actions.


---
name: Stitch Student Portal
colors:
  surface: '#faf9f5'
  surface-dim: '#dadad6'
  surface-bright: '#faf9f5'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f4f4f0'
  surface-container: '#eeeeea'
  surface-container-high: '#e8e8e4'
  surface-container-highest: '#e2e3df'
  on-surface: '#1a1c1a'
  on-surface-variant: '#44474e'
  inverse-surface: '#2f312e'
  inverse-on-surface: '#f1f1ed'
  outline: '#75777f'
  outline-variant: '#c4c6cf'
  surface-tint: '#4a5e86'
  primary: '#000d27'
  on-primary: '#ffffff'
  primary-container: '#0b2347'
  on-primary-container: '#778bb5'
  inverse-primary: '#b2c7f4'
  secondary: '#455e8f'
  on-secondary: '#ffffff'
  secondary-container: '#adc7fe'
  on-secondary-container: '#395282'
  tertiary: '#010e22'
  on-tertiary: '#ffffff'
  tertiary-container: '#162439'
  on-tertiary-container: '#7d8ba5'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#d7e2ff'
  primary-fixed-dim: '#b2c7f4'
  on-primary-fixed: '#011b3f'
  on-primary-fixed-variant: '#32476c'
  secondary-fixed: '#d8e2ff'
  secondary-fixed-dim: '#adc7fe'
  on-secondary-fixed: '#001a41'
  on-secondary-fixed-variant: '#2c4675'
  tertiary-fixed: '#d5e3ff'
  tertiary-fixed-dim: '#b9c7e2'
  on-tertiary-fixed: '#0d1c30'
  on-tertiary-fixed-variant: '#3a475e'
  background: '#faf9f5'
  on-background: '#1a1c1a'
  surface-variant: '#e2e3df'
typography:
  display-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 40px
    fontWeight: '700'
    lineHeight: 48px
    letterSpacing: -0.03em
  display-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
    letterSpacing: -0.025em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 36px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 22px
    fontWeight: '600'
    lineHeight: 30px
    letterSpacing: -0.015em
  headline-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 26px
    letterSpacing: -0.01em
  body-lg:
    fontFamily: DM Sans
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 26px
    letterSpacing: -0.01em
  body-md:
    fontFamily: DM Sans
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 22px
    letterSpacing: 0em
  body-sm:
    fontFamily: DM Sans
    fontSize: 12px
    fontWeight: '400'
    lineHeight: 18px
    letterSpacing: 0.01em
  label-md:
    fontFamily: DM Sans
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 16px
    letterSpacing: 0.02em
  label-sm:
    fontFamily: DM Sans
    fontSize: 11px
    fontWeight: '500'
    lineHeight: 14px
    letterSpacing: 0.04em
  label-lg:
    fontFamily: DM Sans
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
    letterSpacing: 0em
  telemetry-tag:
    fontFamily: DM Sans
    fontSize: 11px
    fontWeight: '500'
    lineHeight: 14px
    letterSpacing: 0.04em
  telemetry-code:
    fontFamily: DM Sans
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 22px
    letterSpacing: 0em
rounded:
  DEFAULT: 1rem
  lg: 2rem
  xl: 3rem
  full: 9999px
spacing:
  space-xs: 0.25rem
  space-sm: 0.5rem
  space-md: 1rem
  space-lg: 1.5rem
  space-xl: 2.5rem
  gutter: 1.5rem
  gutter-mobile: 1rem
  margin: 3rem
  margin-mobile: 1.25rem
  container-max: 80rem
---

## Brand & Style

This design system expresses a "Premium Sovereign Identity" vernacular engineered for an Indonesian modern Islamic senior high school. It synthesizes institutional prestige, calm authority, and a modern premium product sensibility.

The aesthetic is governed by a calibrated tension:
- **Sovereign Navy (`#0b2347`)**: Anchors primary touchpoints — buttons, primary containers, and navigation. Signals academic rigor, institutional integrity, and a quiet premium feel.
- **Warm Canvas (`#faf9f5`)**: Warm off-white surfaces provide a soft, editorial backdrop that reduces glare and reads as premium.
- **Soft Elevation**: Depth is expressed with soft diffuse shadows (`0 1px 3px rgba(11,35,71,0.04)`, hover `0 6px 16px rgba(11,35,71,0.08)`) rather than hard offsets.
- **Pill Geometry**: Buttons, inputs, badges, and chips use full pill radii (`rounded-full`); cards and sections use `1rem`–`3rem` radii.
- **Telemetry Labels**: NISN/NSS and code identifiers use DM Sans with `font-variant-numeric: tabular-nums` for clean aligned numerals.

The interface evokes trustworthy institutional authority with a contemporary premium product feel — authoritative, calm, and legible without being bureaucratic.

## Colors

- **Primary Aura (`#000d27`) & Primary Container (`#0b2347` - Sovereign Navy):** Main brand touchpoints, primary CTAs, headers. On-container text `#778bb5`.
- **Secondary (`#455e8f` - Slate Indigo) / Container (`#adc7fe`):** Secondary actions, success states, academic pathways.
- **Tertiary (`#010e22`) / Container (`#162439`):** Accent layers and supporting surfaces.
- **Warm Neutrals:** `#faf9f5` base surface, `#ffffff` elevated cards, `#f4f4f0` → `#e2e3df` container steps.
- **Ink (`#1a1c1a`):** High-contrast on-surface text ensuring legibility.

## Typography

1. **Headlines (`Plus Jakarta Sans`):** display/headline/label hierarchy with negative letter-spacing for a tight, premium editorial density.
2. **Body & Interface (`DM Sans`):** Neutral, geometric-calm, optimized for dense schedules, fee tables, and official notices.
3. **Telemetry / Meta (`DM Sans` + `tabular-nums`):** NISN identifiers, room coordinates, accreditation chips — aligned numerals keep data tables clean.

## Layout & Spacing

- **Desktop (1024px+):** 12-col grid, `1.5rem` gutters, `3rem` page margins, max container `80rem`.
- **Tablet (640–1023px):** 8-col grid, `1.5rem` gutters, `1.25rem` margins.
- **Mobile (320–639px):** 4-col grid, `1rem` gutters, `1.25rem` safe margins.
- Spacing scale: `0.25/0.5/1/1.5/2.5rem` (xs/sm/md/lg/xl).
- Vertical cadence uses generous section separations to give academic content breathing room against soft card surfaces.

## Elevation & Depth

Elevation is expressed with soft diffuse shadows keyed to the navy tint:

- **Tier 0 (Canvas):** Flat warm base `#faf9f5`.
- **Tier 1 (Static Card):** White surface, `1px solid #c4c6cf` outline-variant, `box-shadow: 0 1px 3px rgba(11,35,71,0.04)`.
- **Tier 2 (Hover / Priority):** `box-shadow: 0 6px 16px rgba(11,35,71,0.08)`, translateY(-2px) on hover.
- **Tier 3 (Floating HUD / Nav):** `background: rgba(255,255,255,0.85); backdrop-filter: blur(12px); border-bottom: 1px solid var(--color-outline-variant)`.
- **Tactile Inset (Inputs):** `box-shadow: inset 0 1px 2px rgba(11,35,71,0.04)` with a soft 3px navy focus ring.

## Shapes

- Default radii: small `0.5rem`, DEFAULT `1rem`, lg `2rem`, xl `3rem`, `full 9999px`.
- Buttons, inputs, badges, chips: pill (`rounded-full`).
- Cards / sections: `rounded-DEFAULT` (1rem) to `rounded-[28px]`.
- Circular trims for avatar frames and seal stamps.

## Components

### Buttons
- **Primary Button:** `#0b2347` Sovereign Navy background, white text, pill (`rounded-full`), soft shadow. Hover deepen to `#000d27`, translateY(-1px).
- **Secondary Button:** `#adc7fe` container background, `#395282` text, pill.
- **Ghost / Glass Button:** white translucency, `backdrop-blur`, outline-variant border, `#1a1c1a` text.

### Form Inputs & Search Fields
- **Text Inputs:** White, `1px solid #c4c6cf`, soft inset shadow, pill. Focus: navy border + soft 3px ring.

### Cards & Panels
- **Standard Card:** White `#ffffff`, `1px solid #c4c6cf`, `1rem` radius, `0 1px 3px rgba(11,35,71,0.04)`, 24px padding.

### Chips & Badges
- **Status Pill:** pill-shaped, outline-variant border, DM Sans 11px uppercase with tracking.

### Navigation Bars
- **Desktop Header:** sticky glass (white/85 + blur), `1px` outline-variant bottom border. Brand left, primary nav with active state in navy, pill accent actions.