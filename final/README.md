# oPark

oPark is a parking reservation application built with PHP, MySQL, React, and Vite. PHP serves the application pages and API; React/Vite builds the client interface.

> **GitHub Pages demo:** The static entry page provides a browser-only demo login and parking dashboard. Demo accounts and reservations are saved in that browser's local storage; lane availability is not live and data is not shared across devices. This is not secure authentication. Do not use real passwords or sensitive information. The complete application and live reservations still need a PHP/MySQL host.

## Upload this folder to GitHub

Create a GitHub repository, then upload the **contents of this `final` folder** to the repository root. Do not upload `node_modules/` or `dist/`; they are generated locally and excluded by `.gitignore`.

## Requirements

- XAMPP (Apache and MySQL/MariaDB), or another PHP/MySQL web host
- PHP with the `mysqli` and `curl` extensions enabled
- Node.js and npm
- Arduino IDE and an ESP32 board for the optional hardware integration

## Run locally with XAMPP

1. Copy this folder into XAMPP's `htdocs` directory and name it `vite-opark`. Keep the subfolders in place because the application refers to their paths.
2. Start Apache and MySQL from the XAMPP Control Panel.
3. In phpMyAdmin, import [`setup.sql`](./setup.sql) to create the `users_db` database and its tables.
4. The default database connection is `localhost`, MySQL user `root`, an empty password, and database `users_db`. If your database settings differ, configure these environment variables for Apache:

   - `OPARK_DB_HOST`
   - `OPARK_DB_USER`
   - `OPARK_DB_PASSWORD`
   - `OPARK_DB_NAME`

   For ESP32 sync, also configure `OPARK_DEVICE_API_TOKEN` with a long, unique secret. Restart Apache after changing its environment. Do not commit credentials or tokens.
5. Open <http://localhost/vite-opark/>. Apache serves the PHP login, signup, account, admin, and API endpoints.

## React development and build

Run these commands from the project folder:

```sh
npm ci
npm run dev
```

The Vite development server is for the React interface; it does not execute the PHP backend. Use the XAMPP URL above to use PHP-backed pages and APIs. To create and check the production React build, run:

```sh
npm run build
```

## Optional social sign-in

Copy [`src/oauth_config.example.php`](./src/oauth_config.example.php) to `src/oauth_config.local.php` and enter the provider credentials and redirect URLs for your installation. The local configuration file is ignored by Git. Social login also requires PHP cURL to be enabled.

## Optional ESP32 integration

The firmware source is in [`src/esp32`](./src/esp32). Before flashing it, set its Wi-Fi SSID and password, the API URL for your server, and a device token matching `OPARK_DEVICE_API_TOKEN`. Keep real credentials out of commits. The firmware uses the Adafruit PWM Servo Driver library.

## Project layout

```text
final/
├── index.html              # Vite React entry point
├── index.php               # PHP welcome page
├── config.php              # PHP database and device-token configuration
├── setup.sql               # Database schema and initial parking lanes
├── public/                 # Public static assets
├── src/                    # PHP pages, React components, styles, and firmware
├── package.json
└── vite.config.js
```
