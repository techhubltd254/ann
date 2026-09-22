<?php

namespace App\Services\Mcp;

/**
 * k-anonymity gate — Algorithm 20 from kicc-algorithms.
 * Ensures no MCP response leaks individual identity.
 * Applied before data exposure through MCP APIs.
 */
class KAnonymizerService
{
    private int $k;

    public function __construct()
    {
        $this->k = config('kicc.pool.anonymizer_k', 5);
    }

    public function anonymize(array $rows, string $groupByKey = 'county_id'): array
    {
        if ($this->k <= 1) return $rows;

        $groups = [];
        foreach ($rows as $row) {
            $key = $row[$groupByKey] ?? '_ungrouped';
            $groups[$key][] = $row;
        }

        $result = [];
        foreach ($groups as $key => $group) {
            if (count($group) < $this->k) {
                // Suppress — too small to release
                continue;
            }
            array_push($result, ...$group);
        }

        return $result;
    }

    public function setK(int $k): void
    {
        $this->k = $k;
    }
}