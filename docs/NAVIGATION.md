# Shared responsive tabs

The logged-in navigation is rendered once in `includes/layout.php`.
`assets/css/layout.css` switches its layout at 768px without JavaScript:

- **768px and wider:** fixed, scrollable left sidebar with icon-and-label tabs.
- **Below 768px:** fixed bottom bar, five equal-width tabs, icons above labels.
  The header contains only the existing logo and page title. No hamburger.
- Students/teachers: Home, Subjects, Notifications, Ranking, Profile.
- Admins: Home, Pending, Accounts, Notifications, Profile.
- Help and POST/CSRF-protected Logout are in the own-Profile “More options”
  panel, along with the remaining admin destinations for administrators.
  These links remain available in the desktop sidebar too.

The existing inline SVG style, logo, palette, and fonts are preserved. Active
tabs have a blue accent (left on desktop, top on mobile) and tinted background.
The Profile tab is visually selected on mobile for destinations accessed through
its More options. Notification badges use the actual unread, non-archived count,
with a compact `99+` display above 99 and the exact count in the accessible label.

The viewport enables `viewport-fit=cover`. Bottom navigation and body clearance
include `env(safe-area-inset-bottom)`, and side insets are respected too. Mobile
tabs are at least 68px tall; content has additional bottom clearance. Legacy
hamburger JavaScript was removed; other shared behaviors were preserved.

## Verification

Headless Chromium with temporary sessions for existing approved student/admin
accounts, without changing database rows:

```text
PASS student 360px: 7 routes; navigation, active tab, no overflow, no hamburger, scroll clearance
PASS student 767px: 7 routes; navigation, active tab, no overflow, no hamburger, scroll clearance
PASS student 768px: 7 routes; navigation, active tab, no overflow, no hamburger, scroll clearance
PASS student 1280px: 7 routes; navigation, active tab, no overflow, no hamburger, scroll clearance
PASS student: Profile more options and zero JavaScript errors
PASS admin 360px: 12 routes; navigation, active tab, no overflow, no hamburger, scroll clearance
PASS admin 767px: 12 routes; navigation, active tab, no overflow, no hamburger, scroll clearance
PASS admin 768px: 12 routes; navigation, active tab, no overflow, no hamburger, scroll clearance
PASS admin 1280px: 12 routes; navigation, active tab, no overflow, no hamburger, scroll clearance
PASS admin: Profile more options and zero JavaScript errors
```

These 76 route/viewport checks cover Home, Subjects, Notifications, Ranking,
Profile, Help, and feed, plus admin dashboard, pending accounts, accounts,
account creation, and archive. Assertions verify exactly five visible mobile
tabs in the requested order, tap sizes, fixed placement after scrolling,
footer clearance, desktop sidebar dimensions, active state, and no overflow.
360px screenshots were also inspected. Safe-area CSS is included, but physical
iPhone hardware was not tested.

`pnpm test` passed: 61 PHP files linted, 58 local references resolved, all 89
subjects retained, and shared CSRF/error-setting checks passed.
