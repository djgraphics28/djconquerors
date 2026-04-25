# React Native App — DJ Conquerors (User App) — Vibe Code Prompt

---

## PROJECT OVERVIEW

Build a **React Native** mobile application for **DJ Conquerors** — a multi-level referral/investment community platform. This app is for **regular users only** (not admins). It connects to the existing **Laravel 12 REST API** using **Sanctum Bearer tokens**.

---

## TECH STACK

- **Framework:** React Native (Expo SDK, latest stable)
- **Navigation:** React Navigation v7 (Stack + Bottom Tabs + Drawer)
- **State Management:** Zustand (global auth + user store)
- **Data Fetching:** TanStack Query (React Query v5) + Axios
- **Forms:** React Hook Form + Zod validation
- **UI Components:** React Native Paper or NativeWind (Tailwind CSS for RN)
- **Storage:** Expo SecureStore (tokens), AsyncStorage (preferences)
- **Notifications:** Expo Notifications
- **Image Picker:** Expo Image Picker (avatar upload)
- **Charts:** Victory Native (for investment/team stats)
- **Icons:** react-native-vector-icons (MaterialCommunityIcons)
- **Language:** TypeScript (strict mode)

---

## BACKEND API

```
Base URL: http://djconquerors.test/api
Auth:     Bearer Token (Laravel Sanctum)
```

### Auth Endpoints

| Method | Endpoint              | Description                    |
|--------|-----------------------|--------------------------------|
| POST   | `/api/register`       | Register new user              |
| POST   | `/api/login`          | Login → returns Bearer token   |
| POST   | `/api/logout`         | Revoke token (auth required)   |
| POST   | `/api/forgot-password`| Send password reset email      |
| GET    | `/api/user`           | Get authenticated user         |

> All protected routes require header: `Authorization: Bearer {token}`

---

## USER MODEL (API Response Shape)

```typescript
interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at: string | null;
  riscoin_id: string | null;
  inviters_code: string | null;
  invested_amount: number;
  birth_date: string | null;
  phone_number: string | null;
  gender: 'male' | 'female' | null;
  occupation: string | null;
  is_active: boolean;
  date_joined: string;
  last_login_at: string | null;
  bonchat_id: string | null;
  team_id: number | null;
  primary_language: string | null;
  secondary_language: string | null;
  support_team: string | null;
  support_group: string | null;
  assistant_id: number | null;
  avatar_url: string | null;
  // computed
  age: number | null;
  total_team_members: number;
  direct_team_count: number;
  team_investment: number;
  months_and_days_since_joined: string;
  initials: string;
}
```

---

## APP SCREENS & FEATURES

### 1. ONBOARDING / AUTH FLOW

#### 1.1 Splash Screen
- Show DJ Conquerors logo with animated fade-in
- Check for stored Sanctum token → auto-navigate to Home if valid

#### 1.2 Login Screen
- Fields: `email`, `password`
- "Remember me" toggle (persist token in SecureStore)
- Forgot password link
- Error handling: wrong credentials, unverified email (show "Resend verification" button)
- Rate limit notice (5 attempts/min from server)
- Navigate to Register

#### 1.3 Register Screen
- Fields (all required unless noted):
  - `name` (full name)
  - `email`
  - `password` + `password_confirmation`
  - `riscoin_id` — their RisCoin ID
  - `inviters_code` — referral code from inviter (required for joining)
  - `invested_amount` — initial investment (numeric, min 0)
  - `birth_date` — date picker, must be 18+
  - `phone_number`
- Show inline validation errors from API
- On success: redirect to "Check your email" screen

#### 1.4 Verify Email Screen
- Informational screen: "Please check your email to verify your account"
- "Resend verification email" button
- "I've verified — Continue" button (attempts to reload user and proceed)

#### 1.5 Forgot Password Screen
- Field: `email`
- On submit: show success message "Reset link sent if email exists"

#### 1.6 Two-Factor Authentication Screen (2FA)
- Shown after login if 2FA is enabled on account
- Input: 6-digit code
- Option to enter recovery code

---

### 2. MAIN APP (Authenticated)

Use a **Bottom Tab Navigator** with these tabs:

| Tab       | Icon                  | Screen             |
|-----------|-----------------------|--------------------|
| Home      | home                  | Dashboard          |
| My Team   | account-group         | Team / Genealogy   |
| Wallet    | wallet                | Withdrawals        |
| Calendar  | calendar              | Appointments       |
| Profile   | account-circle        | Profile & Settings |

---

### 3. DASHBOARD SCREEN

Display a summary card for the logged-in user:

**User Info Card:**
- Avatar (circular, tap to change)
- Name, RisCoin ID, member since (months_and_days_since_joined)
- `is_active` badge (Active / Inactive)
- Email verified badge

**Stats Grid (2x2 cards):**
- Total Invested: `invested_amount`
- Team Size: `total_team_members`
- Direct Referrals: `direct_team_count`
- Team Investment: `team_investment`

**Quick Actions Row:**
- Calculator → navigate to Compound Calculator
- Invite → show share sheet with referral/invite code
- Tutorials → navigate to Tutorials list
- Support → navigate to Tickets

**Recent Activity Feed:**
- Last 5 items from activity log (if API exposes it)

---

### 4. MY TEAM SCREEN (Genealogy / Referrals)

#### 4.1 Team Overview Tab
- Header stats: total members, direct count, team investment
- List of **direct team members** (users they invited):
  - Avatar, name, joined date, invested amount, status badge
  - Tap to view member profile (read-only modal)

#### 4.2 Genealogy Tree Tab
- Scrollable/zoomable tree or collapsible accordion showing:
  - User at root
  - Level 1: direct invites
  - Level 2+: their invites (collapsible)
- Each node: avatar circle, name, level badge

#### 4.3 My Assistant
- If `assistant_id` is set: show assistant card with name, email, contact button
- If none: "No assistant assigned"

---

### 5. WALLET / WITHDRAWALS SCREEN

**Permissions required:** `myWithdrawals.view`

#### 5.1 Withdrawals List
- List all user's withdrawal records
- Each item: amount, status badge (pending/approved/rejected), date, reference
- Pull-to-refresh

#### 5.2 Create Withdrawal (FAB button)
- Form fields:
  - Amount (numeric, required)
  - Notes / Remarks (optional)
- Submit → optimistic UI update

#### 5.3 Withdrawal Detail
- Full details of a single withdrawal
- Status timeline (if available)

---

### 6. APPOINTMENTS SCREEN

**Permissions required:** `appointments.booking`

#### 6.1 Appointments List
- Tabs: **Upcoming** / **Past** / **All**
- Each card: host name, date/time, status badge (pending/confirmed/cancelled/completed)
- Pull-to-refresh

#### 6.2 Book Appointment (FAB button)
- Date + time picker
- Select available host (fetched from API)
- Notes field
- Conflict detection (show error if time slot unavailable)

#### 6.3 Appointment Detail
- Full details, cancel button (if status is pending/confirmed and in future)

---

### 7. PROFILE & SETTINGS

#### 7.1 Profile Screen
- Display current user info in read mode
- "Edit Profile" button → Profile Edit Form

#### 7.2 Edit Profile Form
- Editable fields:
  - `name`
  - `phone_number`
  - `birth_date` (date picker)
  - `gender` (dropdown: Male / Female)
  - `occupation`
  - `primary_language` (dropdown)
  - `secondary_language` (dropdown)
  - `bonchat_id`
- Avatar upload: tap avatar → image picker → upload to `/api/user/avatar` (multipart)
- Save button with loading state

#### 7.3 Change Password
- Fields: `current_password`, `password`, `password_confirmation`
- Zod validation: min 8 chars, confirmed

#### 7.4 Two-Factor Authentication
- Show 2FA status (enabled/disabled)
- Enable 2FA: show QR code + confirm with code
- Disable 2FA: confirm with current code

#### 7.5 Notification Preferences
- Toggle switches for notification types (email, push)
- Backed by EmailReceiver settings from API

#### 7.6 Appearance
- Dark mode toggle (stored in AsyncStorage, applied via theme context)
- Language preference (primary/secondary)

#### 7.7 Account
- "Delete Account" (destructive, requires confirmation dialog)
- "Logout" button → clear SecureStore + navigate to Login

---

### 8. COMPOUND CALCULATOR SCREEN

**Permissions required:** `calculator.view`, `calculator.access`

- Input fields:
  - Initial Investment (numeric)
  - Monthly Interest Rate (%)
  - Number of Months
  - Compounding frequency (monthly, quarterly)
- Output:
  - Month-by-month breakdown table
  - Total return, total interest earned
  - Line chart showing growth over time (Victory Native)
- Export / share result as image or text

---

### 9. TUTORIALS SCREEN

**Permissions required:** `tutorials.access`

#### 9.1 Tutorials List
- Grid or list of tutorial cards
- Each: title, thumbnail, category, duration
- Search bar at top
- Filter by category

#### 9.2 Tutorial Detail
- Title, description, video player (Expo AV) or WebView for embedded video
- Mark as completed

---

### 10. SUPPORT / TICKETS SCREEN

#### 10.1 My Tickets List
- List all user's support tickets
- Status badges: open / in-progress / resolved / closed
- Pull-to-refresh

#### 10.2 Create Ticket
- Fields: subject, category (dropdown), description (multiline)
- Attach image (optional)
- Submit → show confirmation

#### 10.3 Ticket Detail / Chat
- Show original ticket + message thread
- Reply input at bottom
- Real-time updates (polling or WebSocket if Pusher is configured)

---

### 11. LEADERBOARD SCREEN

- Ranked list of top users by:
  - Team size
  - Team investment
  - Personal investment
- Highlight current user's rank
- Tabs: Weekly / Monthly / All-Time

---

### 12. CELEBRATIONS SCREEN

- Feed of achievement badges/milestones
- Current user's earned badges
- Upcoming milestones with progress bar

---

### 13. RISCOIN LINKS SCREEN

- List of user's RisCoin referral links
- Copy-to-clipboard button per link
- Share button (native share sheet)
- QR code display for each link

---

### 14. GUIDE SCREEN

**Permissions required:** `guide.access`

- Structured guide articles/pages
- Search and category filter
- Full-screen article reader with back navigation

---

### 15. NOTIFICATIONS CENTER

- Bell icon in header (with badge count)
- List of all notifications (push + in-app)
- Mark as read / Mark all as read
- Tap to navigate to relevant screen

---

## APP ARCHITECTURE

```
src/
├── api/
│   ├── axios.ts             # Axios instance with interceptors (attach token, handle 401)
│   ├── auth.ts              # Auth API calls
│   ├── user.ts              # User profile API calls
│   ├── withdrawals.ts
│   ├── appointments.ts
│   ├── tutorials.ts
│   ├── tickets.ts
│   └── ...
├── components/
│   ├── common/
│   │   ├── Avatar.tsx
│   │   ├── StatusBadge.tsx
│   │   ├── StatCard.tsx
│   │   ├── LoadingSpinner.tsx
│   │   └── EmptyState.tsx
│   └── screens/             # Screen-specific components
├── hooks/
│   ├── useAuth.ts           # Auth state + actions
│   ├── useUser.ts           # Current user query
│   └── ...
├── navigation/
│   ├── RootNavigator.tsx    # Auth check → Auth stack or App stack
│   ├── AuthNavigator.tsx    # Login, Register, etc.
│   ├── AppNavigator.tsx     # Bottom tabs
│   └── types.ts             # Navigation param types
├── screens/
│   ├── auth/
│   │   ├── LoginScreen.tsx
│   │   ├── RegisterScreen.tsx
│   │   ├── ForgotPasswordScreen.tsx
│   │   ├── VerifyEmailScreen.tsx
│   │   └── TwoFactorScreen.tsx
│   ├── dashboard/
│   ├── team/
│   ├── wallet/
│   ├── appointments/
│   ├── profile/
│   ├── calculator/
│   ├── tutorials/
│   ├── tickets/
│   └── ...
├── store/
│   ├── authStore.ts         # Zustand: token, user, isAuthenticated
│   └── themeStore.ts        # Zustand: dark mode
├── theme/
│   ├── colors.ts            # Brand colors
│   ├── typography.ts
│   └── index.ts
├── types/
│   ├── user.ts
│   ├── withdrawal.ts
│   ├── appointment.ts
│   └── ...
└── utils/
    ├── formatters.ts        # Date, currency, number formatting
    ├── validators.ts        # Zod schemas
    └── permissions.ts       # Check user role permissions
```

---

## AUTHENTICATION FLOW

```
App Start
   │
   ├─ Token in SecureStore?
   │       ├─ YES → GET /api/user
   │       │           ├─ 200 → email_verified? 
   │       │           │           ├─ YES → Main App (Bottom Tabs)
   │       │           │           └─ NO  → Verify Email Screen
   │       │           └─ 401 → Clear token → Auth Stack
   │       └─ NO → Auth Stack (Login)
   │
Auth Stack:
   Login → POST /api/login
               ├─ 200 → Store token → (2FA?) → App
               ├─ 422 → Show field errors
               └─ 429 → "Too many attempts" warning
```

---

## AXIOS INTERCEPTOR SETUP

```typescript
// src/api/axios.ts
import axios from 'axios';
import * as SecureStore from 'expo-secure-store';

const api = axios.create({
  baseURL: 'http://djconquerors.test/api',
  headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
});

api.interceptors.request.use(async (config) => {
  const token = await SecureStore.getItemAsync('auth_token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

api.interceptors.response.use(
  (res) => res,
  async (error) => {
    if (error.response?.status === 401) {
      await SecureStore.deleteItemAsync('auth_token');
      // navigate to Login
    }
    return Promise.reject(error);
  }
);

export default api;
```

---

## BRANDING & THEME

```typescript
// Brand Colors (DJ Conquerors)
const colors = {
  primary: '#1a56db',      // Royal Blue
  secondary: '#7c3aed',    // Purple (accent)
  success: '#16a34a',
  warning: '#d97706',
  danger: '#dc2626',
  background: '#f8fafc',
  surface: '#ffffff',
  textPrimary: '#0f172a',
  textSecondary: '#64748b',
  border: '#e2e8f0',
  // Dark mode
  dark: {
    background: '#0f172a',
    surface: '#1e293b',
    textPrimary: '#f1f5f9',
    textSecondary: '#94a3b8',
    border: '#334155',
  }
};
```

---

## PERMISSIONS HELPER

Since the backend uses Spatie role/permission, include a client-side helper:

```typescript
// src/utils/permissions.ts
export const USER_PERMISSIONS = {
  dashboard: ['dashboard.view'],
  myWithdrawals: ['myWithdrawals.view', 'myWithdrawals.create', 'myWithdrawals.edit', 'myWithdrawals.delete'],
  genealogy: ['genealogy.view'],
  tutorials: ['tutorials.access'],
  myTeam: ['myTeam.access', 'myTeam.view'],
  appointments: ['appointments.booking'],
  calculator: ['calculator.view', 'calculator.access'],
};

export const hasPermission = (userPermissions: string[], permission: string): boolean =>
  userPermissions.includes(permission);
```

---

## KEY REQUIREMENTS

1. **Token Security:** Always store Sanctum token in `Expo SecureStore`, never AsyncStorage
2. **Offline Handling:** Show cached data with stale indicator when offline
3. **Pull to Refresh:** All list screens must support pull-to-refresh
4. **Loading States:** Skeleton loaders (not spinners) for initial data fetches
5. **Error Boundaries:** Wrap main screens with error boundary components
6. **Form Validation:** All forms validate on submit AND on blur
7. **Image Handling:** Avatar images should be cached with `expo-image`
8. **Deep Linking:** Support deep links for email verification (`/verify-email/{id}/{hash}`)
9. **Pagination:** Implement infinite scroll / load-more for all lists
10. **Accessibility:** All touchable elements must have `accessibilityLabel`
11. **Logout Safety:** Clear all stored credentials and cached data on logout
12. **Input Sanitization:** Sanitize all user inputs before sending to API

---

## WHAT NOT TO BUILD (Admin-Only Features)

Do NOT include these in the user app:
- User management (create/edit/delete other users)
- Role & permission management
- Impersonation
- Admin dashboard analytics
- Manager level assignment
- Team CRUD (users can only view their team, not manage all teams)
- Activity logs viewer (admin-level)
- All withdrawals (user sees only their own)
- Email receiver management (admin-level)

---

## STARTING POINT INSTRUCTIONS

1. `npx create-expo-app@latest djconquerors-app --template blank-typescript`
2. Install dependencies listed in the Tech Stack section
3. Start with the Auth Flow (Login → Register → Verify Email)
4. Build the Zustand auth store and Axios interceptor next
5. Implement the Bottom Tab Navigator with placeholder screens
6. Fill in screens one tab at a time starting with Dashboard
7. Test against the live API at `http://djconquerors.test/api`

---

*Generated for the DJ Conquerors project — April 2026*
