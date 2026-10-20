<?php

namespace BambooHr;

use GuzzleHttp\Client;

class BambooHrClient
{
    private Client $http;

    function __construct(string $companyDomain, string $apiKey, ?Client $http = null)
    {
        $this->http = $http ?? new Client([
            'base_uri' => "https://$companyDomain.bamboohr.com/api/v1/",
            'auth' => [$apiKey, 'x'],
            'headers' => ['Accept' => 'application/json'],
            'timeout' => 30,
        ]);
    }

    /**
     * All employees of the published directory.
     */
    function getDirectory(bool $onlyCurrent = true): array
    {
        $data = $this->get('employees/directory', ['onlyCurrent' => $onlyCurrent ? 'true' : 'false']);
        return $data['employees'] ?? [];
    }

    /**
     * Directory entries whose supervisor matches $supervisorName (as displayed in BambooHR, e.g. "Doe, Jane").
     * Pass $supervisorId to match on the supervisor's employee id when the directory exposes it.
     */
    function getTeamMembers(string $supervisorName): array
    {
        return array_values(array_filter(
            $this->getDirectory(),
            fn(array $e) => isset($e['supervisor']) && $this->normalizeName($e['supervisor']) === $this->normalizeName($supervisorName)
        ));
    }

    /**
     * Time off requests overlapping [$start, $end] (YYYY-MM-DD).
     *
     * @param int[]|null $employeeIds filter client-side on these employees
     */
    function getTimeOffRequests(string $start, string $end, string $status = 'approved', ?array $employeeIds = null): array
    {
        $query = ['start' => $start, 'end' => $end];
        if ($status !== '') {
            $query['status'] = $status;
        }
        $requests = $this->get('time_off/requests', $query);

        if ($employeeIds !== null) {
            $ids = array_map('intval', $employeeIds);
            $requests = array_values(array_filter($requests, fn(array $r) => in_array((int)$r['employeeId'], $ids, true)));
        }
        return $requests;
    }

    /**
     * Calendar view: time off entries and company holidays.
     */
    function getWhosOut(string $start, string $end, bool $ignoreSavedFilter = true): array
    {
        $query = ['start' => $start, 'end' => $end];
        if ($ignoreSavedFilter) {
            $query['filter'] = 'off';
        }
        return $this->get('time_off/whos_out', $query);
    }

    /**
     * Convenience: team members of $supervisorName with their approved leave in the period.
     *
     * @return array<int, array{employee: array, requests: array}>
     */
    function getTeamLeaves(string $supervisorName, string $start, string $end): array
    {
        $team = $this->getTeamMembers($supervisorName);
        $ids = array_map(fn($e) => (int)$e['id'], $team);
        $requests = $ids ? $this->getTimeOffRequests($start, $end, 'approved', $ids) : [];

        $result = [];
        foreach ($team as $employee) {
            $result[(int)$employee['id']] = ['employee' => $employee, 'requests' => []];
        }
        foreach ($requests as $request) {
            $result[(int)$request['employeeId']]['requests'][] = $request;
        }
        return $result;
    }

    /**
     * First and last day (YYYY-MM-DD) of a month.
     *
     * @return array{0: string, 1: string} [start, end]
     */
    static function getMonthPeriod(int $year, int $month): array
    {
        $first = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        return [$first->format('Y-m-d'), $first->modify('last day of this month')->format('Y-m-d')];
    }

    private function get(string $path, array $query = []): array
    {
        $response = $this->http->get($path, ['query' => $query]);
        return json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * "Doe, Jane" and "Jane Doe" compare equal.
     */
    private function normalizeName(string $name): string
    {
        if (str_contains($name, ',')) {
            [$last, $first] = array_map('trim', explode(',', $name, 2));
            $name = "$first $last";
        }
        return strtolower(preg_replace('/\s+/', ' ', trim($name)));
    }
}
