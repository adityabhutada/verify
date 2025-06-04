# Verify App

This project provides a small web application for verifying user information and tracking results. The repository does not include the SQLite database file. Follow the steps below to set up the project locally.

## Requirements

- PHP 8 with the `pdo_sqlite` extension
- Composer
- SQLite3 command line tool

## Installation

1. Install PHP dependencies:

   ```bash
   composer install
   ```

2. Create `db.sqlite` using the provided schema:

   ```bash
   sqlite3 db.sqlite < migrations/init_db.sql
   ```

3. Ensure `db.sqlite` is writable by the web server.

After these steps the application can be served by your preferred PHP server.

## Environment Variables

The application expects the following variables to be defined in the environment:

- `API_KEY`
- `SMTP_HOST`
- `SMTP_PORT`
- `SMTP_USER`
- `SMTP_PASS`
- `SENDER_EMAIL`
- `ADMIN_USER`
- `ADMIN_PASS`
- `ADMIN_EMAIL`
- `DB_PATH` (optional, defaults to `db.sqlite`)
- `LOG_PATH` (optional, defaults to `logs/webhook.log`)
- `SUBMIT_LOG_PATH` (optional, defaults to `logs/submit_error.log`)

You can copy `.env.example` to configure these values when running locally. Error messages
from `submit.php` are written to the file specified by `SUBMIT_LOG_PATH`.
