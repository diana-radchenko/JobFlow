# QA Checklist — HRFlow employer role

Manual browser QA for the employer feature (spatie roles, employer job CRUD, application
review with viewed-tracking, and the two real `/request-tracker` charts).

Written for a human or agent doing a fresh pass. Everything below was implemented and
covered by 30 new Pest tests (`php artisan test --compact` → 142 passing), but automated
tests do not exercise the rendered UI, the redirect chain after a real login, or the chart
markup — that is what this checklist is for.

---

## 0. What changed (orientation)

| Area | Files |
|---|---|
| Roles | `app/Enums/UserRole.php`, `database/migrations/*_create_permission_tables.php`, `*_create_default_roles.php`, `bootstrap/app.php` (`role` middleware alias) |
| Registration → role | `app/Actions/Fortify/CreateNewUser.php`, `app/Providers/FortifyServiceProvider.php`, `app/Http/Responses/{Login,Register}Response.php` |
| Landing / register UI | `resources/js/pages/Welcome.vue`, `resources/js/pages/auth/Register.vue` |
| Schema | `*_add_user_id_to_work_jobs_table.php`, `*_add_viewed_at_to_user_work_job_applications_table.php` |
| Employer backend | `routes/web.php`, `app/Http/Controllers/Employer/{Job,Application}Controller.php`, `app/Http/Requests/StoreWorkJobRequest.php`, `app/Policies/OwnedResourcePolicy.php` |
| Employer UI | `resources/js/pages/Employer/**`, `resources/js/components/AppSidebar.vue` |
| Charts | `resources/js/pages/RequestTracker.vue` |

Two roles exist: `candidate` and `employer`. Every user has exactly one. Candidate routes
are gated with `role:candidate`, employer routes with `role:employer` — the gate cuts both
ways, so an employer is locked out of the candidate app and vice versa.

---

## 1. Setup

- [ ] `composer run dev` is running (serve + vite + queue + logs)
- [ ] `php artisan migrate` applied — tables `roles`, `model_has_roles`, column
      `work_jobs.user_id`, column `user_work_job_applications.viewed_at` all exist
- [ ] Two browser profiles (or one + incognito) so employer and candidate sessions coexist

### Starting database state (verify before you begin)

```bash
php artisan tinker --execute 'foreach(App\Models\User::with("roles")->get() as $u){ echo $u->email." => ".($u->roles->pluck("name")->implode(",") ?: "NO ROLE")."\n"; } echo "owned jobs: ".App\Models\WorkJob::whereNotNull("user_id")->count()."\n";'
```

Expected on a dev box that was migrated, not reseeded:

- All pre-existing users are `candidate` (backfilled by the roles migration). **No existing
  account is an employer** — you must register one.
- `owned jobs: 0` — all 12 seeded jobs are platform listings with `user_id = null`.
- The pre-existing applications sit on those platform jobs, so **no employer can ever open
  them**. Chart 2 only moves once a candidate applies to a job an *employer* created. Follow
  the section order below or the chart steps will read as broken.

### Non-issue to be aware of

`Features::emailVerification()` is enabled in `config/fortify.php`, but `App\Models\User`
does **not** implement `MustVerifyEmail` (the import is commented out), so the `verified`
middleware passes through. You will **not** be sent to `/verify-email` and do not need to
dig a link out of `storage/logs/laravel.log`.

---

## 2. Landing page → register

- [ ] `/` renders; **JobFlow** and **HRFlow** blocks both visible
- [ ] "Find your perfect job" → `/register`, **Candidate profile** card highlighted
- [ ] "Manage your best team" → `/register?type=employer`, **I'm hiring / Employer profile**
      card highlighted *(this link was a dead `href="#"` before)*
- [ ] On `/register?type=employer`, clicking "I'm looking for a job" still switches the
      selection back — the query param preselects, it must not lock the choice

## 3. Employer registration and redirect

- [ ] Register `employer1@test.com` with **I'm hiring** selected
- [ ] Lands on **`/employer/jobs`**, not `/resumes`
- [ ] Sidebar shows **only** My Jobs and Settings — no Dashboard, Resumes, Job Selection,
      Request Tracker, Interview, Salary, Development
- [ ] Empty state reads "You haven't posted any jobs yet"
- [ ] Log out, log back in → lands on `/employer/jobs` again (proves `LoginResponse`, not
      just `RegisterResponse`)
- [ ] Register `candidate1@test.com` with **I'm looking for a job** → lands on `/resumes`
      with the full candidate sidebar

## 4. Route gating, both directions

As **employer1**, type each URL directly — every one must return **403**:

- [ ] `/dashboard`
- [ ] `/resumes`
- [ ] `/job-selection`
- [ ] `/request-tracker`
- [ ] `/interview-preparation`

As **candidate1**, each must return **403**:

- [ ] `/employer/jobs`
- [ ] `/employer/jobs/create`

Logged out:

- [ ] `/employer/jobs` redirects to `/login`
- [ ] `/settings/profile` is reachable by **both** roles (settings is deliberately shared)

## 5. Job CRUD (as employer1)

- [ ] **Post a Job** → form loads with empty fields
- [ ] Submit blank → required fields block; any server-side errors render under the correct input
- [ ] **Salary from 90000 / Salary to 10000** → error on *Salary to*; job is **not** created
- [ ] Create a valid job, technologies `PHP, Laravel, Vue.js` → redirects to the job detail
      page showing **three separate badges**, not one joined string
- [ ] Create a second job with **Technologies left blank** → saves without a 500
      *(`work_jobs.technologies` is a non-nullable json column; the form request defaults it to `[]`)*
- [ ] `/employer/jobs` lists both, each with **0 applications** and an *Updated* date
- [ ] **Edit** (pencil) → all fields prefilled, technologies shown comma-separated; change
      the title and save → redirects to detail with the new title
- [ ] **Delete** (trash) → native `confirm()` appears; **Cancel** keeps the job; confirming
      deletes it and returns to the list

## 6. Candidate applies (as candidate1, second browser)

- [ ] `/job-selection` lists employer1's job alongside the 12 platform jobs
- [ ] Open it → **Apply**; the button state changes to *Applied*
- [ ] Apply to **3 more** jobs (any) so the charts have material in section 9
- [ ] `/request-tracker` lists all 4, every one status *Applied*

## 7. Viewed tracking (the Chart 2 driver)

- [ ] As **employer1**, `/employer/jobs` now shows **1 application** on that job
- [ ] Open the job → the application row shows the candidate's name/email and an
      **EyeOff "New"** marker
- [ ] Click the application row → detail page opens, shows the email as a `mailto:` link and
      a *Viewed on \<today\>* line
- [ ] Navigate **back to the job** → the marker is now **Eye "Viewed"**
- [ ] Reload the detail page → the *Viewed on* date does **not** advance (first open only)

Optional DB confirmation:

```bash
php artisan tinker --execute 'foreach(App\Models\UserWorkJobApplication::whereNotNull("viewed_at")->get() as $a){ echo $a->id." viewed_at ".$a->viewed_at."\n"; }'
```

## 8. Reject / Schedule an Interview

- [ ] On the application detail, **Schedule an Interview** → status pill flips to
      *Interview Scheduled* without a full page reload
- [ ] **Reject** → flips to *Rejected*
- [ ] The job detail row reflects the same status
- [ ] As **candidate1**, `/request-tracker` shows the updated status on that application

## 9. Charts (as candidate1, `/request-tracker`)

Both charts were hardcoded mock markup before; they now compute from the real applications prop.

**Chart 1 — "Application Outcomes Overview"**

- [ ] Third bar is labelled **Offer**, not "Shortlisted" (a status that never existed in `ApplicationStatus`)
- [ ] With 4 applications and 1 rejected: *Rejected* bar ≈ **25%** height; the other two flat
- [ ] Have employer1 flip that application to *Interview Scheduled*, reload → *Interview
      Scheduled* ≈ 25%, *Rejected* flat

**Chart 2 — "Percentage of Viewed Applications"**

- [ ] Legend reads **Viewed** / **Not viewed** (no more "Other")
- [ ] With 1 of 4 opened: pie shows **25% / 75%**, legend "1 applications" / "3 applications"
- [ ] Employer1 opens a second application → reload → **50% / 50%**

**Empty and zero states**

- [ ] Log in as a pre-existing account with applications only on platform jobs (e.g.
      `test@gmail.com`) → Chart 2 shows **0% viewed**, Chart 1 all bars flat
- [ ] Register a fresh candidate with **zero** applications → both charts render **0%**, no
      `NaN`, no broken pie, no console errors

## 10. Security — direct URL tampering

- [ ] Register `employer2@test.com`. As employer2, paste employer1's `/employer/jobs/{id}` → **403**
- [ ] Same for `/employer/jobs/{id}/edit` → **403**
- [ ] As employer2, paste `/employer/jobs/1` (a **platform** job, no owner) → **403**
- [ ] As employer1, take a valid `/employer/jobs/{job}/applications/{application}` URL and
      swap in your *other* job's id → **404** (route-level scoped bindings)
- [ ] After each attempt, confirm in the employer1 session that nothing changed and
      `viewed_at` was not set

## 11. Regression sweep (existing candidate features)

The `role:candidate` gate was added to every pre-existing authenticated route. Spot-check
that nothing broke for a normal candidate:

- [ ] `/dashboard` loads
- [ ] `/resumes` loads; open a resume in the editor and save one section
- [ ] `/job-selection` filters (region / income level) still work
- [ ] `/request-tracker` delete-application dialog still deletes
- [ ] `/interview-preparation` loads
- [ ] `/settings/profile` and `/settings/security` load

---

## 12. Known limitations — do **not** file these as bugs

- Existing accounts were all backfilled as **candidate**; there is no in-app way to switch
  roles. Becoming an employer requires a new registration.
- **Schedule an Interview** only sets the status. No date picker, no notification to the
  candidate. Marked in the code with a `ponytail:` comment on
  `app/Http/Controllers/Employer/ApplicationController.php`.
- Employers have no dashboard, resumes, or company profile — the sidebar is intentionally
  just My Jobs + Settings.
- Chart 2's two percentage labels sit at fixed positions inside the pie; at very low or very
  high percentages a label can overlap the arc boundary. That is the pre-existing static
  layout, deliberately left unchanged.
- The 12 seeded platform jobs stay visible to candidates and belong to no employer by design.
- `resources/js/pages/RequestTracker.vue` still uses `slate-*` utility classes rather than
  design tokens. This change was a data fix, not a restyle.
- Pre-existing lint/format failures remain in ~10 untouched files (`Development.vue`,
  `Salary.vue`, `Interview/Live.vue`, `AppServiceProvider.php`, several older test files).
  They were failing before this work and were left alone.

## 13. Automated checks (for reference)

```bash
php artisan test --compact          # expect 142 passed
vendor/bin/pint --test --dirty      # expect pass
npx vue-tsc --noEmit                # only pre-existing NodeJS namespace errors in Development.vue
npm run build
```
