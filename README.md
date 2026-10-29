# oPark

oPark is a parking reservation app built with PHP, MySQL, React, and Vite. Its PHP pages and API run under Apache; Vite is used to develop and build the React interface.

GitHub Pages can display the static welcome page, but it cannot execute the PHP login or backend. Use a PHP/MySQL host such as XAMPP to use the complete application.

## Requirements

- XAMPP (Apache, PHP, and MySQL/MariaDB)
- PHP with the `mysqli` and `curl` extensions enabled
- Node.js and npm
- Arduino IDE and an ESP32 board for the optional hardware integration

## Run locally with XAMPP

1. Put the complete `vite-opark` folder in XAMPP's `htdocs` directory. Keep the existing subfolders: PHP pages, React sources, styles, and assets refer to them.
2. Start Apache and MySQL from the XAMPP Control Panel.
3. In phpMyAdmin, import [`setup.sql`](./setup.sql) to create the `users_db` database and its tables.
4. Configure the database connection. By default, the app uses `localhost`, MySQL user `root`, an empty password, and database `users_db`. If your local database differs, set these environment variables for Apache:

   - `OPARK_DB_HOST`
   - `OPARK_DB_USER`
   - `OPARK_DB_PASSWORD`
   - `OPARK_DB_NAME`

   Also set `OPARK_DEVICE_API_TOKEN` to a long, unique secret before enabling ESP32 sync. Restart Apache after changing its environment. Do not commit real credentials or tokens.
5. Open <http://localhost/vite-opark/>. The PHP login, signup, account, and admin pages are served by Apache.

## React development and build

From the project folder:

```sh
npm ci
npm run dev
```

Vite's development server is for the React interface; use the XAMPP URL above for PHP-backed pages and APIs. To create and check the production React build, run:

```sh
npm run build
```

Generated files in `dist/` and installed packages in `node_modules/` are intentionally excluded from Git.

## Optional social sign-in

Copy [`src/oauth_config.example.php`](./src/oauth_config.example.php) to `src/oauth_config.local.php`, then enter the provider credentials and redirect URLs for your local installation. The local configuration file is ignored by Git. Social login also requires PHP cURL to be enabled.

## Optional ESP32 integration

The firmware source is in [`src/esp32`](./src/esp32). Before flashing it, set its Wi-Fi SSID and password, the API URL for your XAMPP host, and a device token matching `OPARK_DEVICE_API_TOKEN`. Keep those real values out of commits. The firmware uses the Adafruit PWM Servo Driver library.

## Repository layout

```text
vite-opark/
├── index.html              # Vite React entry point
├── index.php               # PHP welcome page
├── config.php              # PHP database and device-token configuration
├── setup.sql               # Database schema and initial parking lanes
├── public/                 # Public static assets
├── src/                    # PHP pages, React components, styles, and firmware
├── package.json
└── vite.config.js
```

Keep the project in this single root folder when uploading it to GitHub. Its internal folders are required for the application to work.
# tutorial-with-gitbash
# stiopark
