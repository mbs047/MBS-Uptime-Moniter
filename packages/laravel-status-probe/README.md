# Laravel Status Probe

`mbs047/laravel-status-probe` is the companion package for the status monitor.

It installs a configurable health surface inside another Laravel application and
exposes metadata that the monitor can import or accept via push registration.

## Commands

- `php artisan status-probe:install`
- `php artisan status-probe:register`
- `php artisan status-probe:heartbeat scheduler`

## Default Endpoints

- `GET /status/health`
- `GET /status/metadata`

Both endpoints are bearer-token protected by default and can be changed with
the published `config/status-probe.php` file.
