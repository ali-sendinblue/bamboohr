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

const FILE = __DIR__ . '/holidays.json';

$years = array_map('intval', array_slice($argv, 1)) ?: [(int)date('Y')];
$config = json_decode(file_get_contents(FILE), true, 512, JSON_THROW_ON_ERROR);

$cache = [];
foreach ($config['offices'] as $office => &$entry) {
    if (($entry['source'] ?? '') !== 'nager') {
        echo "$office: manual, skipped\n";
        continue;
    }
    foreach ($years as $year) {
        $key = "{$entry['country']}-$year";
        $cache[$key] ??= json_decode(
            file_get_contents("https://date.nager.at/api/v3/PublicHolidays/$year/{$entry['country']}"),
            true, 512, JSON_THROW_ON_ERROR
        );

        // Drop this year's dates, then add the nationwide ones (regional holidays are ignored)
        $entry['holidays'] = array_filter($entry['holidays'] ?? [], fn($date) => !str_starts_with($date, "$year-"), ARRAY_FILTER_USE_KEY);
        foreach ($cache[$key] as $holiday) {
            if ($holiday['global']) {
                $entry['holidays'][$holiday['date']] = $holiday['name'];
            }
        }
        ksort($entry['holidays']);
        echo "$office ({$entry['country']}) $year: " . count(array_filter(array_keys($entry['holidays']), fn($d) => str_starts_with($d, "$year-"))) . " holidays\n";
    }
}
unset($entry);

file_put_contents(FILE, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
echo "Saved " . FILE . "\n";
