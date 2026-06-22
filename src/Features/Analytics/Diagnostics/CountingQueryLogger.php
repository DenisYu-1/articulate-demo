<?php

namespace App\Features\Analytics\Diagnostics;

use Articulate\QueryLogger\QueryLoggerInterface;

final class CountingQueryLogger implements QueryLoggerInterface
{
    /**
     * @var array<int, array{sql: string, parameters: array, durationMs: float}>
     */
    private array $queries = [];

    public function log(string $sql, array $parameters, float $durationMs): void
    {
        $this->queries[] = [
            'sql' => $sql,
            'parameters' => $parameters,
            'durationMs' => $durationMs,
        ];
    }

    public function count(): int
    {
        return count($this->queries);
    }

    public function reset(): void
    {
        $this->queries = [];
    }

    /**
     * @return array<int, array{sql: string, parameters: array, durationMs: float}>
     */
    public function queries(): array
    {
        return $this->queries;
    }
}
