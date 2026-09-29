# BYC GROWTH 2.0 — Global Project Rules & Architectural Reference

This document serves as the **single general reference and source of truth** for all development and implementation sessions for BYC GROWTH 2.0. Every future implementation session or AI agent prompt **must read and adhere to this file** before making any architectural or code changes.

---

## 1. Language Rules
- **UI & Content Language**: All website UI text, buttons, headings, descriptions, labels, placeholders, tooltips, modals, notifications, and error messages must be written in **English**.
- **Tone & Quality**: Grammar and wording must be natural, clear, professional, and consistent across all pages.
- **Developer Communication**: Developers and agents may communicate in Indonesian (or English) during planning and discussions, but any implemented UI, public-facing copy, or user-visible content must strictly be in English.

---

## 2. Device & Responsiveness Rules
- **Primary Target**: Desktop and laptop screens are the primary priority.
- **Excluded Devices**: Mobile and tablet responsiveness are **NOT** part of the current scope.
- **Viewport Adaptation**: Desktop layouts must gracefully adapt to standard desktop and laptop screen viewports (ranging from 1024px up to 1920px+).
- **No-Scroll Rule for Gameplay**: Gameplay screens (e.g. Guess Me!, BYC Growth 100, and any future gameplay arenas) must be compact and fit within the viewport so the host/game master does not need to scroll during an active game session.
- **Scroll Allowance for Long Content**: The Homepage, Members page, and Cash Management transaction tables are explicitly permitted to scroll vertically, as their records and content naturally grow in length.

---

## 3. Design System & Visual Foundation
The existing BYC GROWTH earth-tone visual aesthetic is the primary design foundation. All pages, modules, and components must feel like one unified, cohesive product.

### Core Design Tokens
- **Palette**:
  - Ink (Text / Primary Headings): `#203029` (`var(--ink)`)
  - Muted (Subtitles / Captions): `#68756f` (`var(--muted)`)
  - Paper (Main Background): `#f8f5ec` (`var(--paper)`)
  - Cream (Secondary Surfaces / Cards): `#eee8d3` (`var(--cream)`)
  - White (Card Surfaces / Form Elements): `#fffef9` (`var(--white)`)
  - Forest (Primary Brand Color): `#284e3b` (`var(--forest)`)
  - Forest Dark (Hover / Deep Accent): `#173426` (`var(--forest-dark)`)
  - Lime (Accent / Highlights / Status Active): `#bad259` (`var(--lime)`)
  - Gold (Special Badges / Accents): `#e7bd52` (`var(--gold)`)
  - Red / Red Soft (Red Team / Danger / Strike): `#bd4c42` / `#f3e0da`
  - Blue / Blue Soft (Blue Team / Info): `#315e89` / `#dfe9f2`
  - Line / Border: `#dcd7c8` (`var(--line)`)
  - Shadow: `0 18px 55px rgba(33, 48, 41, .12)` (`var(--shadow)`)

### Typography
- **Primary Body & UI Font**: `"DM Sans", sans-serif` (weights: 400, 500, 600, 700).
- **Display & Numerical Font**: `"Manrope", sans-serif` (weights: 600, 700, 800) for headlines, scores, numbers, and brand typography.

### UI Components & Standards
- **Buttons**:
  - Height & Radius: Minimum 46px height, `border-radius: 12px`, bold font weight (`700`), flex alignment with 9px icon gap.
  - Variants:
    - Primary (`.button-primary`): Forest green background with white text.
    - Secondary (`.button-secondary`): White surface with subtle `--line` border and dark ink text.
    - Ghost (`.button-ghost`): Transparent background with border, hover tint.
    - Danger (`.button-danger`): Soft red background with red text.
  - Interactive States: Smooth hover elevation (`transform: translateY(-2px)`, enhanced box-shadow), active press, and disabled state (`opacity: .4; cursor: not-allowed`).
- **Cards & Surfaces**:
  - Crisp border (`1px solid var(--line)`), rounded corners (`16px` to `24px`), soft elevation shadow (`var(--shadow)`), and white/cream backgrounds.
- **Modals & Overlays**:
  - Backdrop: `rgba(32, 48, 41, 0.45)` with subtle blur or clean opacity.
  - Centered dialog container, consistent padding (`28px` to `36px`), clear title, structured actions, and explicit dismissal (close button / backdrop click / ESC key).
- **Animations**:
  - Consistent micro-interactions: `.2s ease` transitions on buttons, cards, and interactive elements.
  - Specific interactions (card reveals, score increments, strikes) may have tailored keyframe animations, but must strictly conform to the visual earth-tone identity.

---

## 4. Navigation & Flow
- **Back Navigation**: Every relevant subpage, gameplay screen, management screen, and settings page must provide an intuitive "Back" button or link so users never have to manually edit the URL or return to the homepage from scratch.
- **Explicit Routes**: Routes must be structured logically, use clear naming conventions, and avoid dead routes or redundant endpoints.
- **Predictable Flow**: Users and game hosts should have seamless breadcrumbs or back-action targets based on their entry route.

---

## 5. Code Organization & Modularity
- **Separation of Concerns**: Keep files organized strictly by their domain of responsibility (Controllers, Services, Models, Blade views, CSS, JS components).
- **Flat & Clean Directory Structure**: Avoid unnecessary deeply nested folders. Do not create a single-file folder unless required by a framework convention or backed by a clear scalability justification.
- **1000-Line Limit**: If any file (Controller, Service, CSS file, JavaScript module, Blade template) approaches or exceeds approximately 1000 lines, it must be proactively refactored into logical, smaller, maintainable modules or partials.
- **Asset Structure**:
  - Stylesheet modularity: `resources/css/byc-growth/*.css` imported cleanly into `resources/css/app.css`.
  - Scripts modularity: `resources/js/components/*.js` imported into `resources/js/app.js`.
  - Blade templates: Common layout in `layouts/app.blade.php`, reusable UI in `resources/views/components/`, sub-sections in `resources/views/partials/`, and standalone screens in `resources/views/pages/`.

---

## 6. Code Comments & Documentation
- **Concise & Meaningful**: Comments must explain the "why" (architectural intent, business constraints, non-obvious algorithms) rather than restating the obvious syntax.
- **No Clutter**: Avoid verbose, line-by-line narrative comments that merely repeat what the code obviously does.
- **Docblocks**: Standard PHPDoc / JSDoc for public service methods, complex helper functions, and custom API interfaces.

---

## 7. Error Handling & Resilience
- **Input Validation**: Every form submission, JSON payload, and route parameter must have strict server-side validation (Laravel FormRequests or `$request->validate()`).
- **No Silent Logic Failures**: Catch and handle exceptions properly; do not use empty catch blocks or ignore critical failures.
- **Graceful UI Errors**: User-facing actions (AJAX requests, file uploads, score submissions) must display clear, non-intrusive feedback (e.g. toast notification, inline error message) when an error occurs.

---

## 8. Security & Routing Discipline
- **Server-Side Enforcement**: Authentication, authorization, permissions, and validation must always be verified and enforced at the backend level (middleware, policies, controllers).
- **No Security Through Obscurity**: Never rely solely on hiding frontend buttons or menus for access control.
- **Direct Access Prevention**: Prevent unauthorized direct URL access to restricted features, admin screens, or internal actions.
- **CSRF & Injection Protection**: Always verify CSRF tokens on state-modifying requests (`POST`, `PUT`, `DELETE`), use parameterized Eloquent / query builders, and sanitize rendered outputs.

---

## 9. Future Session & Agent Protocol
- **Mandatory First Step**: Every future implementation session and prompt must review `BYC_GROWTH_2.0_RULES.md` as its baseline reference before modifying or generating code.
- **Living Document**: If a new global rule or architectural decision is formally adopted, update this file immediately so subsequent sessions remain aligned.
- **Avoid Duplication**: Do not duplicate these entire rules into future individual task prompts; reference `BYC_GROWTH_2.0_RULES.md` directly.

---

## 10. Scope Discipline & Non-Negotiable Boundaries
- **Strict Scope Execution**: Implement ONLY what is explicitly specified for the active scope/session.
- **No Speculative Features**: Do not anticipate, pre-code, or inject speculative features for future phases.
- **Preserve Completed Work**: Do not redesign, rewrite, or break completed, working features without an explicit user requirement.
- **Zero Unrelated Refactoring**: Do not touch unrelated files, rename existing working routes, or restructure working logic outside the current session scope.
