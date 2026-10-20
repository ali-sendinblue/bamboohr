<?php
// Fills holidays.json with public holidays from Nager.Date (https://date.nager.at).
//
// Usage: php generate_holidays.php 2026 [2027 ...]   (defaults to the current year)
//
// holidays.json lists one entry per BambooHR "location" (office):
//   "Paris": {"country": "FR", "source": "nager", "holidays": {"2026-01-01": "New Year's Day", ...}}
// - source "nager":  the given years are replaced with Nager.Date's nationwide holidays.
// - source "manual": never touched by this script; edit the dates by hand (e.g. India, not covered by Nager.Date).
// To add an office, add an entry with its country code and source, then run this script.

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script can only be run from the command line.\n");
}

const FILE = __DIR__ . '/holidays.json';

function decodeJson(string $json): array
{
    $data = json_decode($json, true);
    if (!is_array($data)) {
        throw new RuntimeException('Invalid JSON: ' . json_last_error_msg());
    }
    return $data;
}

$years = array_map('intval', array_slice($argv, 1)) ?: [(int)date('Y')];
$config = decodeJson(file_get_contents(FILE));

$cache = [];
foreach ($config['offices'] as $office => &$entry) {
    if (($entry['source'] ?? '') !== 'nager') {
        echo "$office: manual, skipped\n";
        continue;
    }
    foreach ($years as $year) {
        $key = "{$entry['country']}-$year";
        if (!isset($cache[$key])) {
            $cache[$key] = decodeJson(file_get_contents("https://date.nager.at/api/v3/PublicHolidays/$year/{$entry['country']}"));
        }

        // Drop this year's dates, then add the nationwide ones (regional holidays are ignored)
        $entry['holidays'] = array_filter($entry['holidays'] ?? [], function ($date) use ($year) {
            return strpos($date, "$year-") !== 0;
        }, ARRAY_FILTER_USE_KEY);
        foreach ($cache[$key] as $holiday) {
            if ($holiday['global']) {
                $entry['holidays'][$holiday['date']] = $holiday['name'];
            }
        }
        ksort($entry['holidays']);
        echo "$office ({$entry['country']}) $year: " . count(array_filter(array_keys($entry['holidays']), function ($d) use ($year) {
            return strpos($d, "$year-") === 0;
        })) . " holidays\n";
    }
}
unset($entry);

file_put_contents(FILE, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
echo "Saved " . FILE . "\n";
