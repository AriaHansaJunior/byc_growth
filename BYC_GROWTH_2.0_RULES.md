# BYC GROWTH 2.0 — Global Project Rules & Architectural Reference

This document serves as the **single general reference and source of truth** for all development and implementation sessions for BYC GROWTH 2.0. Every future implementation session or AI agent prompt **must read and adhere to this file** before making any architectural or code changes.

---

## 1. Language Rules
- **UI & Content Language**: All website UI text, buttons, headings, descriptions, labels, placeholders, tooltips, modals, notifications, and error messages must be written in **English**.
- **Tone & Quality**: Grammar and wording must be natural, clear, professional, and consistent across all pages.
- **Developer Communication**: Developers and agents may communicate in Indonesian (or English) during planning and discussions, but any implemented UI, public-facing copy, or user-visible content must strictly be in English.

---

## 2. Device & Responsiveness Rules
- **Primary Optimization Target**: **2560 × 1600** (user's primary laptop resolution).
- **Target Device Class**: Desktop and laptop displays only for now.
- **Excluded Devices**: Mobile and tablet responsiveness are **NOT** part of the current scope.
- **Viewport Adaptation**: Layouts must still adapt reasonably to other standard desktop and laptop screen viewports (e.g. 1024px, 1280px, 1440px, 1920px up to 2560px+).
- **No-Scroll Rule for Gameplay**: Gameplay screens (e.g. Guess Me!, BYC Growth 100, and any future gameplay arenas) must remain compact and fit within the viewport so the host/game master does not need to scroll during an active game session.
- **Scroll Allowance for Long Content**: The Homepage, Members page, and Cash Management transaction tables are explicitly permitted to scroll vertically, as their records and content naturally grow in length.
- **Scope Discipline**: Do not redesign unrelated UI components while maintaining responsiveness.

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
- **No Raw Exception Exposure**: User-facing exceptions, catch blocks, and HTTP error handlers must never expose raw SQL queries, database credentials, server stack traces, or internal filesystem paths. Generic, understandable English error messaging must be provided to the client while logging full traces internally via `Log::error()`.
- **Graceful UI Errors**: User-facing actions (AJAX requests, file uploads, score submissions) must display clear, non-intrusive feedback (e.g. toast notification, inline error message) when an error occurs.

---

## 8. Security & Routing Discipline
- **Server-Side Enforcement**: Authentication, authorization, permissions, and validation must always be verified and enforced at the backend level (middleware, policies, controllers).
- **No Security Through Obscurity**: Never rely solely on hiding frontend buttons or menus for access control.
- **Direct Access Prevention**: Prevent unauthorized direct URL access to restricted features, admin screens, or internal actions.
- **CSRF & Injection Protection**: Always verify CSRF tokens on state-modifying requests (`POST`, `PUT`, `DELETE`), use parameterized Eloquent / query builders, and sanitize rendered outputs.
- **Path Traversal & Upload Isolation**: File serving endpoints and media deletions must strictly sanitize filenames (e.g. `basename()`) and disallow directory traversal sequences (`..`, `/`, `\`). File uploads must restrict dangerous executable extensions and enforce server-authoritative MIME validation.
- **XSS & Template Escaping**: All dynamic data in Blade views must be escaped by default using `{{ }}`. When injecting dynamic server state into client-side `<script>` blocks, the `@json()` directive must always be used instead of unescaped `{!! json_encode() !!}` to prevent script breakout attacks.

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

---

## 11. Universal Game & Team Relational Architecture
- **Per-Game Team Isolation**: Dynamic team configurations belong strictly to an individual game context (`games` ↓ `teams` ↓ `game_scores`).
  - Guess Me! (`game1`) team configuration must never alter BYC GROWTH 100 (`game2`) team configuration, and vice-versa.
  - The `teams` table has a direct foreign key `game_id` referencing `games.id`.
- **Relational Integrity for Points & Scores**:
  - `game_rounds.awarded_team_id` must strictly reference a team belonging to the same game (`team.game_id === round.game_id`).
  - Cross-game point assignment is rejected at the service and controller layer with HTTP 422.
  - Team scores in `game_scores` belong to a specific `game_id` and `team_id` pair.
- **Dynamic Team Sizing**: Both games support 2, 3, 4, or more teams dynamically with persistent names, assigned colors/themes, and stable identities.

---

## 12. Modal Dialog Discipline & Text Normalization
- **Single Close/Cancel Control**: Host tools and dialog modals must provide exactly ONE clear close/dismiss control to avoid redundant controls (e.g. avoid having both a top-right 'X' button and a separate 'Cancel' button when they perform the exact same action).
- **Guess Me Text Normalization**:
  - Clues and correct answers must always be uppercase.
  - Normalization must occur on the frontend in real-time for immediate visual feedback.
  - Normalization must also be enforced at the backend service layer and Eloquent model mutators before database persistence.

---

## 13. Game Scoreboard & Survey Scoring Discipline
- **Single Source of Truth for Scoreboards**:
  - The Universal Game System topbar scoreboard is the single authoritative source of truth for team scores.
  - Individual games must NOT implement duplicate, redundant scoreboards or team score badges on gameplay action cards.
- **Authoritative Survey-Based Scoring**:
  - Growth 100 scoring is strictly derived from revealed survey answers: `sum(points of revealed answers)`.
  - Manual score adjustment buttons (+5, -5, or arbitrary score inputs) are forbidden in the Growth 100 host interface.
  - Backend validation must reject client attempts to submit arbitrary score overrides that do not match the server-calculated revealed answers sum.
- **Strike & Cross State Isolation**:
  - Wrong-answer/cross state is purely an indicator mechanism.
  - Adding, changing, or resetting strikes must never mutate revealed survey answers, alter team scores, or switch the active round.

---

## 14. Community Member Equality & Privacy Rules
- **Absolute Member Equality on Public Directory**: All fellowship members are equal. The public Members page must display ONLY member photo and full name. It is strictly forbidden to publicly display or imply member hierarchy, position, role, rank, status, or date of birth.
- **Internal Date of Birth Confidentiality**: Member `date_of_birth` is strictly internal application data used exclusively for the automated Birthday Popup system. It must never be exposed on public interfaces, member cards, or directory rosters.

---

## 15. Birthday Popup & 10-Second Lock Discipline
- **Local Timezone Authority**: Birthday calculations are determined by the application server based on Surabaya, Indonesia (`Asia/Jakarta`). Matching is performed by comparing `month + day`, ignoring the birth year.
- **10-Second Unclosable Modal Lock**: When a birthday popup appears, it MUST NOT be closable for the first 10 seconds. Close buttons, backdrop dismissal, and the ESC key must be strictly locked during this window. Clear countdown feedback must be communicated. After 10 seconds, `Write a Letter` and `Close` actions are unlocked.
- **Stateless Reappearance**: The birthday popup must never set a permanent "seen" flag or database column. Re-visiting or refreshing the website on the birthday will display the greeting again.
- **Multi-Member Non-Overlapping Presentation**: When multiple members celebrate birthdays on the same day, greetings must be navigated sequentially within a single modal without overlapping windows.

---

## 16. Cash Management & Financial Privacy Discipline
- **Strict Administrator Boundary**: Cash Management is accessible only to authenticated users with the `admin` role. Public visitors and standard users must never access financial ledgers or treasury statistics.
- **Internal Contribution Amount Confidentiality**: The internal fellowship monthly contribution amount (30k / 30,000) must NEVER be exposed or mentioned on any public-facing page or component. Public homepage cash cards must remain disabled with the English message: `Contact the admin to view your cash contribution.`.
- **Image Proof Integrity**: Payment proof uploads must be strictly validated as image formats (JPEG, PNG, JPG, WEBP). Non-image files (PDF, DOC, ZIP, etc.) must be rejected at the backend level.
- **Shortcut Flexibility**: Member payment shortcuts (auto-filling previous account type and amount) must always leave fields completely editable by the administrator.
- **Proof Freshness & Shortcut Isolation**: Member payment shortcuts populate ONLY the previous account type and amount. Shortcuts must NEVER auto-fill, reuse, or copy previous transfer proof images. Every new cash transaction strictly requires a fresh proof image upload.
- **Searchable Member Roster**: The transaction input provides a real-time searchable member selector filtering by member full name without exposing confidential financial history.
- **Authoritative Ledger & Dataset Aggregation**: Total cash contribution calculations must reflect the entire active filtered dataset from the database server, never restricted to the current paginated view slice. Filter changes must reset pagination to page 1 while preserving active page sizes (5, 10, 25, 50).

---

## 17. Relational Persistence & Legacy Elimination
- **MySQL/Eloquent as Single Source of Truth**: All game states, rounds, survey answers, scores, teams, member profiles, cash transactions, activities, and birthday letters are strictly persisted in the relational MySQL database via Eloquent models.
- **No JSON Persistence**: Filesystem storage must never be used for authoritative application data, round definitions, or game state. Filesystem directories (`storage/app/game/images/`, `public/assets/images/uploads/`) exist exclusively for uploaded binary media assets.
- **Dead & Legacy Code Elimination**: Obsolete endpoints, dead controller actions, unrendered legacy templates, and old JSON persistence remnants must remain completely eliminated from the codebase.

---

## 18. Birthday System Foundation & Access Control
- **One User Account = One Member**: Each user account maps to at most one fellowship member via a unique foreign key constraint (`users.member_id`). The `Member` model remains the single source of member identity and profile data, while `User` represents authentication credentials.
- **Server-Authoritative Sender Identity**: Birthday letter sender identity is derived strictly from the authenticated user (`Auth::id()`). Client payloads cannot spoof or override the sender identity.
- **One-Letter Invariant**: A sender can submit only ONE birthday letter to a specific recipient member for a given birthday year (`unique(user_id, member_id, birthday_year)`).
- **Self-Wish Prevention**: A user associated with a member is strictly prevented from submitting a birthday letter to themselves.
- **Anonymous Display with Administrator Traceability**: Anonymity is strictly a display preference for the birthday person. The underlying `user_id` is always persisted, allowing administrators to audit and identify senders while presenting "Anonymous" to the recipient.
- **Birthday Date Editing Discipline**: Normal users may edit their submitted letter ONLY while the recipient's birthday is still active today in Surabaya (`Asia/Jakarta`). Administrators retain full privileges to edit letters at any time.

