# BambooHR utilities

Small browser tools on top of the [BambooHR API](https://documentation.bamboohr.com/docs/getting-started):

- **Team leaves** (`bamboo-leaves.html`): pick a manager and a month, see everyone's time off, export it as CSV.
- **On-call payroll** (`bamboo-oncall.html`): "OnCall Period" days and "On Call Intervention" hours per person,
  split into weekdays and weekends/holidays, with a summary CSV and a daily details CSV.

## Run

```bash
php -S localhost:8000
```

Open http://localhost:8000, enter the company domain (`<domain>.bamboohr.com`) and your personal BambooHR API key
(BambooHR → your profile → API Keys). You see the same employees and fields as your BambooHR user.

To share it, host the folder on any PHP server.

## How it works

| File | Role |
|---|---|
| `index.php` | Launcher listing the tools |
| `bamboo-leaves.html`, `bamboo-oncall.html` | The tools (plain HTML/JS, no build step) |
| `bamboo-common.js`, `bamboo.css` | Shared helpers and styles |
| `bamboo-proxy.php` | Same-origin proxy: BambooHR sends no CORS headers, so the browser can't call it directly |
| `holidays.json` | Public holidays per BambooHR office (location) |
| `generate_holidays.php` | Fills `holidays.json` from [Nager.Date](https://date.nager.at) |
| `src/BambooHrClient.php` | The same API calls as a PHP class, for scripts |

The API key is sent in an `X-Bamboo-Key` header to the proxy, which uses it once for HTTP Basic auth against BambooHR.
It is never stored or logged. The proxy only forwards a few read-only endpoints and only to `*.bamboohr.com`.
If you host it, make sure your web server does not log request headers.

## Holidays

`holidays.json` has one entry per BambooHR location:

```json
"Paris": { "country": "FR", "source": "nager", "holidays": { "2026-01-01": "New Year's Day" } }
```

- `"source": "nager"`: refreshed by `php generate_holidays.php 2027 [2028 ...]` (nationwide holidays only).
- `"source": "manual"`: never touched by the script. **Noida and Bengaluru are placeholders** (only the 3 Indian
  national holidays, since Nager.Date does not cover India); replace them with the company holiday list.

To add an office, add an entry with its ISO country code and source, then run the script.
An employee whose location is not listed gets weekends only, and the page shows a warning.

## On-call rules

- Only approved requests count, and only the days inside the selected month (BambooHR gives the amount per day).
- Weekend = Saturday and Sunday, for every office. A holiday at the employee's office counts as weekend/holiday.
- Type names are matched ignoring case, spaces and dashes; change `TYPES` in `bamboo-oncall.html` if they are renamed.

## PHP client

```bash
composer install
```

```php
$bamboo = new \BambooHr\BambooHrClient('brevo', getenv('BAMBOO_API'));
[$start, $end] = \BambooHr\BambooHrClient::getMonthPeriod(2026, 9);
$requests = $bamboo->getTimeOffRequests($start, $end, 'approved', [1418]);
```
