# BYC GROWTH 2.0 — Global Project Rules & Architectural Reference

This document serves as the **single general reference and source of truth** for all development, refactoring, and maintenance sessions for BYC GROWTH 2.0. Every future implementation session or AI agent prompt **must read and adhere to this file** before making any architectural or code changes.

---

## 1. Language Rules
- **UI & Content Language**: All website UI text, buttons, headings, descriptions, labels, placeholders, tooltips, modals, notifications, dialogs, and error messages must be written strictly in **English**.
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
  - Backdrop: `rgba(32, 48, 41, 0.45)` to `rgba(18, 30, 23, 0.75)` with `backdrop-filter: blur(8px)`.
  - Body scroll lock (`overflow: hidden`) must always be applied to prevent page background scrolling, even if mouse is outside dialog.
  - Centered dialog container, consistent padding (`28px` to `36px`), clear title, structured actions, and explicit dismissal.
- **Animations**:
  - Consistent micro-interactions: `.2s ease` transitions on buttons, cards, and interactive elements.
  - Specific interactions (card reveals, score increments, strikes) may have tailored keyframe animations conforming to the visual earth-tone identity.

---

## 4. Navigation & Flow
- **Back Navigation**: Every relevant subpage, gameplay screen, management screen, and settings page must provide an intuitive "Back" button or link so users never have to manually edit the URL.
- **Explicit Routes**: Routes must be structured logically, use clear naming conventions, and avoid dead routes or redundant endpoints.
- **Predictable Flow**: Users and game hosts should have seamless breadcrumbs or back-action targets based on their entry route.

---

## 5. Code Organization & 1000-Line Limit
- **Separation of Concerns**: Keep files organized strictly by their domain of responsibility (Controllers, Services, Models, Blade views, CSS, JS components).
- **Flat & Clean Directory Structure**: Avoid unnecessary deeply nested folders. Never create single-file folders without architectural justification.
- **Strict 1,000-Line Limit**: Every file across the codebase (PHP controller, Service class, Blade view, CSS stylesheet, JavaScript component) must strictly remain **under 1,000 lines**.
  - When modularizing Blade views: extract into `resources/views/admin/partials/` or `resources/views/partials/` with clear, intuitive names (e.g. `homepage-add-modal.blade.php`, `games-guess-modal.blade.php`, `team-config-modal.blade.php`). Never use generic names like `body_admin1`.
  - When modularizing JavaScript: extract into `resources/js/components/` (e.g. `admin-homepage.js`, `admin-games.js`) and register in `resources/js/app.js`.
  - When modularizing CSS: extract related styles into their respective family directory (e.g. `resources/css/admin/shell.css` imported into `admin.css`), avoiding random new folders.
  - When modularizing complex Services: use standard Laravel Trait Concerns under `app/Services/{Domain}/Concerns/` (e.g. `GameStorageService` composed with `ManagesTeams`, `ManagesGuessMe`, `ManagesGrowth100`, `ManagesGameSession`).

---

## 6. Code Comments & Documentation
- **Concise & Meaningful**: Comments must explain the "why" (architectural intent, business constraints, non-obvious algorithms) rather than restating the obvious syntax.
- **No Clutter**: Avoid verbose, line-by-line narrative comments that merely repeat what the code obviously does. Delete outdated or unnecessary comments during refactoring.
- **Docblocks**: Standard PHPDoc / JSDoc for public service methods, complex helper functions, and custom API interfaces.

---

## 7. Error Handling & Resilience
- **Input Validation**: Every form submission, JSON payload, and route parameter must have strict server-side validation.
- **No Silent Logic Failures**: Catch and handle exceptions properly; do not use empty catch blocks or ignore critical failures.
- **No Raw Exception Exposure**: User-facing exceptions must never expose raw SQL queries, database credentials, server stack traces, or internal filesystem paths.
- **Graceful UI Feedback**: User-facing actions (AJAX requests, file uploads, score submissions, reordering) must display clear, non-intrusive feedback (toasts, dialog alerts).

---

## 8. Security & Routing Discipline
- **Server-Side Enforcement**: Authentication, authorization, permissions, and validation must always be verified and enforced at the backend level (`auth`, `admin` middleware).
- **No Security Through Obscurity**: Never rely solely on hiding frontend buttons or menus for access control.
- **Direct Access Prevention**: Prevent unauthorized direct URL access to restricted features, admin screens, or internal actions.
- **CSRF & Injection Protection**: Always verify CSRF tokens on state-modifying requests (`POST`, `PUT`, `DELETE`), use parameterized Eloquent / query builders, and sanitize rendered outputs.
- **Path Traversal & Upload Isolation**: File serving endpoints and media deletions must strictly sanitize filenames (`basename()`) and disallow directory traversal sequences (`..`, `/`, `\`). File uploads must restrict dangerous extensions and enforce server MIME validation.
- **XSS & Template Escaping**: All dynamic data in Blade views must be escaped by default using `{{ }}`. When injecting dynamic server state into client-side scripts, `@json()` directive must always be used.

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
- **Zero Unrelated Refactoring**: Do not touch unrelated files or restructure working logic outside the current session scope.

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
- **Single Close/Cancel Control**: Modals must provide ONE clear close/dismiss control to avoid redundant controls (e.g. avoid having both a top-right 'X' button and a separate 'Cancel' button when they perform the exact same action).
- **Guess Me Text Normalization**:
  - Clues and correct answers must always be uppercase.
  - Normalization must occur on the frontend in real-time for immediate visual feedback.
  - Normalization must also be enforced at the backend service layer before database persistence.

---

## 13. Game Scoreboard & Survey Scoring Discipline
- **Single Source of Truth for Scoreboards**:
  - The Universal Game System topbar scoreboard is the single authoritative source of truth for team scores.
  - Individual games must NOT implement duplicate scoreboards on gameplay cards.
- **Authoritative Survey-Based Scoring**:
  - Growth 100 scoring is strictly derived from revealed survey answers: `sum(points of revealed answers)`.
  - Manual score adjustment buttons (+5, -5, or arbitrary score inputs) are forbidden in the Growth 100 host interface.
  - Backend validation must reject client attempts to submit arbitrary score overrides that do not match the server-calculated revealed answers sum.
- **Strike & Cross State Isolation**:
  - Wrong-answer/cross state is purely an indicator mechanism.
  - Adding, changing, or resetting strikes must never mutate revealed survey answers, alter team scores, or switch the active round.

---

## 14. Community Member Equality & Privacy Rules
- **Absolute Member Equality on Public Directory**: All fellowship members are equal. The public Members page displays ONLY member photo and full name. It is strictly forbidden to publicly display member hierarchy, position, role, rank, status, or date of birth.
- **Internal Date of Birth Confidentiality**: Member `date_of_birth` is strictly internal application data used exclusively for the automated Birthday Popup system. It must never be exposed on public interfaces.

---

## 15. Birthday Popup & Multi-Member Sequence Discipline
- **Local Timezone Authority**: Birthday calculations are determined strictly by the application server based on Surabaya, Indonesia (`Asia/Jakarta`). Matching is performed by comparing `month + day`, ignoring the birth year.
- **Strict Conditional Rendering**: The birthday popup renders ONLY when at least one active BYC member has a birthday today. When no celebrants exist, zero popup markup is rendered.
- **Auto-Slide & Close-Lock Formula (N × 5s)**: For $N$ birthday members, each celebrant is displayed for 5 seconds during the initial automatic sequence (total lock duration = $N \times 5$ seconds). After cycling through all celebrants, manual controls unlock.
- **Popup Cooldown Decoupled from Auth**: A 3-hour display cooldown governs popup reappearance on the same browser context.
- **Date-Derived Presentation Ordering on Members Page**: Today's birthday celebrants appear first on the public Members directory, followed by non-birthday members.
- **Post-Popup Birthday Interaction**: Celebrant cards on the Members page provide a direct letter-writing interaction. Unauthenticated users selecting "Write a Letter" are routed through login and returned directly to the birthday context.

---

## 16. Cash Management & Financial Privacy Discipline
- **Strict Administrator Boundary**: Cash Management is accessible only to authenticated users with the `admin` role via `/admin/cash-management`. Public visitors and standard users must never access financial ledgers or treasury statistics.
- **Internal Contribution Amount Confidentiality**: The internal fellowship monthly contribution amount (30k / 30,000) must NEVER be exposed or mentioned on any public-facing page. Public homepage cash cards must remain disabled with the English message: `Contact the admin to view your cash contribution.`.
- **Image Proof Integrity**: Payment proof uploads must be strictly validated as image formats (JPEG, PNG, JPG, WEBP). Non-image files (PDF, DOC, ZIP, etc.) must be rejected.
- **Shortcut Flexibility**: Member payment shortcuts (auto-filling previous account type and amount) must always leave fields completely editable by the administrator.
- **Proof Freshness & Shortcut Isolation**: Member payment shortcuts populate ONLY the previous account type and amount. Shortcuts must NEVER auto-fill or reuse previous transfer proof images.
- **Authoritative Ledger Aggregation**: Total cash contribution calculations must reflect the entire active filtered dataset from the database server, never restricted to the current paginated view slice. Filter changes must reset pagination to page 1 while preserving active page sizes.

---

## 17. Database-Backed Media & Photo Storage Standards
- **Relational Photo Tables**: Media assets (e.g. homepage slideshow photos, member photos, activity photos, transfer proofs) are tracked in dedicated database tables (`homepage_slide_photos`, `members`, `media_files`, `cash_transactions`) via `MediaUploadService`.
- **Clean DB-Filesystem Sync**: Uploading, updating, or deleting photos updates both the database record and cleans up replaced files on disk, avoiding orphan image accumulation.
- **No Direct Folder Dumping**: Assets must be organized by domain context (`assets/images/uploads/`, `storage/app/`), sanitized, and referenced via database IDs.

---

## 18. Drag-and-Drop & Sequence Architecture
- **In-Place Drag and Drop**: Photo slideshows and round sequences support direct drag-and-drop reordering without obsolete up/down step buttons.
- **Zero Page Refresh on Reorder**: Reordering sends an asynchronous AJAX request (`reorderSlides`, `reorderGuessMeRounds`, `reorderGrowth100Rounds`) and preserves the admin's exact scroll position without reloading the page or snapping back to top view.
- **Zero Page Refresh on Filter Switches**: Switching tabs or year filters (e.g. Birthday Wishes "Current Year" vs "All Years") updates data asynchronously via AJAX without resetting viewport scroll.

---

## 19. Birthday System Foundation & Access Control
- **One User Account = One Member**: Each user account maps to at most one fellowship member via `users.member_id`.
- **Server-Authoritative Sender Identity**: Birthday letter sender identity is derived strictly from `Auth::id()`.
- **One-Letter Invariant**: A sender can submit only ONE birthday letter to a specific recipient member for a given birthday year.
- **Self-Wish Prevention**: A user associated with a member is strictly prevented from submitting a birthday letter to themselves.
- **Anonymous Display with Traceability**: Anonymity is strictly a display preference for the recipient. The underlying `user_id` is always persisted for administrator auditability.
- **Birthday Date Editing Discipline**: Normal users may edit their letter ONLY while the recipient's birthday is still active today in Surabaya (`Asia/Jakarta`). Administrators retain full privileges at any time.

---

## 20. Admin Architecture, Global User Identity & Hidden Entry Discipline
- **Hidden Admin Login Entry (`/admin-ganteng`)**:
  - The hidden entrance `/admin-ganteng` is strictly the direct entrance to the Admin Login page and must NEVER be exposed in public navigation, headers, footers, homepage, members, games, or activities.
  - Serves the Admin Login page directly; authentication is the authoritative protection mechanism.
  - After authentication, the administrator is redirected to `/admin/dashboard`.
- **Global Dual-Identifier Authentication (Email or Username)**:
  - Both `/login` and `/admin-ganteng` support authentication via either email address OR unique username.
- **Strict Role Verification for Admin Access**:
  - Authenticating at `/admin-ganteng` strictly requires the `admin` role.
- **Global Header State & Single-Action Logout**:
  - When unauthenticated: `[ Login ]` button.
  - When authenticated: Displays the user's `username`.
  - Clicking the username reveals a popover containing ONLY `[ Logout ]`.
- **Self-Deletion Invariant**:
  - An authenticated administrator is strictly prevented from deleting their own active account (`user.id !== Auth::id()`).
- **Admin God Mode vs Design System Boundary**:
  - Administrators control website **DATA and CONTENT**.
  - Administrators are strictly forbidden from modifying the underlying website **UI design system**.

---

## 21. Admin Shell & Page-Based Management Architecture
- **Reusable Admin Shell (`layouts/admin.blade.php`)**:
  - All admin management pages must extend the unified Admin Shell layout with standard topbar, brand link, identity display, and reusable confirmation dialog (`#admin-confirm-modal`).
- **Admin-Only Navigation Architecture**:
  - Topbar provides direct navigation across all 8 core management domains:
    1. Dashboard (`/admin/dashboard`)
    2. Homepage (`/admin/homepage`)
    3. Members (`/admin/members`)
    4. Activities (`/admin/activities`)
    5. Games (`/admin/games`)
    6. Birthday Wishes (`/admin/birthday-wishes`)
    7. Cash Management (`/admin/cash-management`)
    8. Roles / Accounts (`/admin/roles`)
- **Destructive Action & Batch Deletion**:
  - Destructive actions require explicit confirmation through `#admin-confirm-modal` with server-side validation and CSRF protection.
  - Batch deletion available across Activities, Members, Birthday Wishes, Cash Management, and Roles/Accounts.

---

## 22. Visual Calmness & Anti-Clutter Discipline (Clean UI Standards)
- **Clear Functional Purpose**: Every visible UI element must serve an explicit, unambiguous functional purpose. Never add decorative UI elements merely to create an artificial dashboard look.
- **No Redundant User Navigation in Admin**: Admin management pages must NOT contain direct-link buttons that navigate to public user-facing pages.
- **No In-Page Scroll Duplication**: Action cards must NOT provide redundant buttons that merely duplicate scrolling to another section on the same page.
- **Single Action Entry Point**: Table-driven interfaces have exactly ONE authoritative action button (e.g. `[ + Add Photo ]` located directly on the management table).
- **No Decorative Overlay Clutter**: Content previews must remain clean representations of the real asset.
- **Admin Modal Scroll Architecture**: Admin editor modals must employ a fixed header, a scrollable body (`overflow-y: auto; flex: 1; min-height: 0;`), and a fixed footer. Modals must lock body scroll (`overflow: hidden`), blur the background, and prevent scrolling outside the modal.
- **Intentional Whitespace**: Layouts maintain natural breathing room without excessive empty vertical gaps.

---

## 23. Zero Dummy Data Policy (Clean Slate Foundation)
- **Zero Dummy Data Mandate**:
  - The live application database must remain clean and completely free of mock or dummy records.
  - Clean slate foundations for Members, Activities, Cash Management, and Birthday Wishes.
- **Permitted Foundation Data Only**:
  - Baseline Homepage hero slides and media.
  - Baseline Games (Guess Me! and BYC Growth 100 questions/answers/teams).
  - Baseline Administrator credentials (`admin_utama`, `rilbiezzz`).

---

## 24. Revision Efficiency & Minimal Scope Execution Protocol
- **Strict Isolation for Revisions**:
  - Revise ONLY the exact points instructed by the user.
  - Modify only directly related files.
  - Fast turnaround: Complete tasks swiftly without burning tokens or asking unnecessary multi-step confirmations.

---

## 25. Absolute Ban on Testing Code & Test Execution During Development
- **No Test Files or Test Code**: Do NOT create or maintain testing code during active revision iterations.
- **No PHPUnit / Test Suite Execution**: Do NOT run test suites during development.
- **Testing Deferred to Final Completion**: Full testing is deferred until the entire project is completed and only when the user explicitly requests it.
