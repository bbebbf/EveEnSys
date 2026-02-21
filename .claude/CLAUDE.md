# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

EveEnSys (Event Enrollment System) is a PHP web app where users register, create events, and enroll themselves or other persons into events.

- A logged-in user can create, edit, and delete their own events.
- A logged-in user can enroll themselves into an event (`subscriber.subscriber_is_creator = 1`).
- A logged-in user can enroll other (non-registered) persons by name via `subscriber.subscriber_name`.
- A logged-in user can change user name and user password.
- All enrolled names are visible to logged-in users only.

## Development Environment

All services run via Docker Compose. From the `docker/` directory:

```bash
# Start core services (webapp + database)
docker compose up -d

# Start with architecture docs (adds structurizr on port 8083)
docker compose --profile documentation up -d

# Rebuild the PHP image after Dockerfile changes
docker compose build webapp

# View logs
docker compose logs -f webapp
```

Service ports:
- **webapp** (PHP/Apache): http://localhost:8082
- **database** (MariaDB): localhost:8307
- **structurizr** (docs, optional): http://localhost:8083

The `.env` file in `docker/` supplies credentials and is gitignored — create it locally from these keys: `TZ`, `DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`.

## Project Structure

```
src/web/            # PHP source (mounted to /var/www/html in container)
  public/           # Apache document root (web-accessible files only)
    assets/         # CSS, images, JavaScript
  config/           # Configuration files (not web-accessible)
  core/             # Router, Request, Session, View
  controllers/      # AuthController, EventController
  model/
    dtos/           # Database entity classes
    business/       # Repository classes (all SQL)
  views/            # PHP view templates

sql/
  create-database.sql  # Full DB schema (run once to initialize)

docker/
  docker-compose.yml
  .env                 # Gitignored — contains DB credentials
  images/websrv-php/
    Dockerfile          # PHP 8.2 + Apache image with mysqli, bcmath, gd, composer
    msmtp.conf          # Gitignored — contains SMTP credentials

docs/structurizr/   # Architecture diagrams (Structurizr DSL)
```

## Database Schema

Three tables in database `ees_db`:

**user** — registered accounts
`user_id`, `user_email`, `user_name`, `user_passwd` (hashed), `user_is_active`, `user_role`, `user_last_login`

**event** — created by a user
`event_id`, `event_guid`
`creator_user_id` (FK→user), `event_title`, `event_description`, `event_date`, `event_duration_hours`, `event_max_subscriber`

**subscriber** — enrollment records
`subscriber_id`, `event_id` (FK→event), `creator_user_id` (FK→user, the logged-in user who created this record), `subscriber_is_creator` (1 = the logged-in user enrolled themselves), `subscriber_name` (name of a non-registered person being enrolled), `subscriber_enroll_timestamp`

## Tech Stack

- PHP 8.2 + Apache
- MariaDB 10.11
- Bootstrap 5.3.8
- Composer (installed in the image)
- Structurizr lite, for documentation
- Mailpit, for SMTP mock up

## Security features

- User passwords should be at least 8 characters long and must include uppercase and lower case letters and also numbers.
- The GET URLs to each event must not be guessable. So a GUID be used in URLs instead of the internal database ``event`` table id. The event GUID should be stored in the column ``event.event_guid`` without curly brackets.
- A "forgot password email" helps to create a new password.