# EcoRise - Environmental Crowdfunding and Volunteer Platform

EcoRise is a PHP + MySQL web platform where users can launch environmental campaigns, donate securely, and join verified volunteer missions for disaster response.

The project includes:
- Public campaign discovery and support flow
- User dashboard and campaign ownership features
- Volunteer application and approval workflow
- Volunteer-only disaster opportunity creation and assignment
- Admin moderation for users and campaigns

## Features

- Crowdfunding campaigns with progress tracking (target vs raised)
- Support flow for approved campaigns
- User authentication (sign up, sign in, logout)
- Role-aware UI (regular user, volunteer, admin)
- Volunteer system:
  - Become volunteer application form
  - Volunteer status handling (`pending`, `approved`, etc.)
  - Volunteer opportunities page with join/leave assignments
  - Volunteer-created disaster opportunities
- Admin panel for campaign/user oversight
- CSRF protection and PDO prepared statements

## Tech Stack

- Backend: PHP 8+
- Database: MySQL / MariaDB
- Frontend: HTML, CSS, JavaScript, W3.CSS
- Icons: Font Awesome

## Project Structure

```text
EcoRise-main01042026/
|- admin/
|  |- index.php
|  |- campaigns.php
|  |- users.php
|  \- process_campaign.php
|- assets/
|  |- campaigns/
|  |- css/
|  |  \- index.css
|  |- db/
|  |  \- ecorise.sql
|  \- logo/
|- config.php
|- index.php
|- blog.php
|- opportunities.php
|- support.php
|- create_campaign.php
|- process_create_campaign.php
|- volunteer_opportunities.php
|- volunteer_create_campaign.php
|- process_volunteer_campaign.php
|- process_volunteer_assignment.php
|- become_volunteer.php
|- process_volunteer_application.php
|- dashboard.php
|- signin.php
|- signup.php
\- README.md
```

## Local Setup

1. Install prerequisites:
   - PHP 8+
   - MySQL/MariaDB
   - Optional bundle: XAMPP / WAMP / Laragon

2. Create database:
   - Create a database named `ecorise`
   - Import `assets/db/ecorise.sql`

3. Configure DB credentials:
   - Open `config.php`
   - Set DB host/name/user/password for your machine

4. Ensure upload folders are writable:
   - `assets/campaigns/`
   - `assets/disasters/` (if used by your volunteer campaign flow)

## Run Commands

From project root:

```bash
php -S localhost:8000
```

Open in browser:

```text
http://localhost:8000
```

Alternative (XAMPP Apache):

```text
http://localhost/EcoRise-main01042026
```

If `8000` is busy:

```bash
php -S localhost:8080
```

## Quick Checks

Lint key files:

```bash
php -l index.php
php -l volunteer_opportunities.php
php -l support.php
```

## Security Notes

- Uses PDO prepared statements for database operations
- Uses password hashing for user credentials
- Uses CSRF tokens in form submissions
- Escapes dynamic output with `htmlspecialchars()` in templates

## Notes

- This project is intended for educational and community-impact use.
- Update any default admin/test credentials in your local DB before deployment.
