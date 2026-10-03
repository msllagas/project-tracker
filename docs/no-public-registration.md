# No public sign-up

## Context

The first version of the app had a public sign-up page (`/register` in the frontend and `POST /api/register` in the API), so anyone who could reach the app could create an account.

Reading the requirements again, this is an internal tool. A digital agency uses it to track its clients' projects, so the people using it are the agency's own staff: project managers, account managers, and the developers and designers working on those projects. Agencies normally give their staff accounts from inside the app, with an admin or a project manager adding each new team member. Letting anyone on the internet create an account would also let them see and change every client project, since projects are shared by all logged-in users.

## Decision

Registration is removed:

- **API:** the `POST /api/register` route, its controller and form request, and the rate limiter that only that route used.
- **Frontend:** the sign-up page and its route, the "New here? Create an account" link on the login page, and the `register` functions in the auth API and composable. `/register` is now treated like any unknown address and redirects to the home page (the login page when logged out).
- **Tests:** the registration tests.

Login, logout and the current-user endpoint are unchanged.

## Consequences

- Accounts are created by someone with access to the server. The seeder creates the demo account (`demo@example.com` / `password`), and more users can be added with Tinker:

  ```bash
  docker compose exec backend php artisan tinker --execute="App\Models\User::create(['name' => 'Jane Doe', 'email' => 'jane@example.com', 'password' => 'a-strong-password']);"
  ```

  The `User` model hashes the password automatically.

- The natural next step is a user management screen where an admin invites or creates team members, which would need roles such as admin and project manager. That is outside this assessment's scope.
