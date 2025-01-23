<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\DB\Criteria;

if ( ! defined('ABSPATH')) {
    exit;
}

class FindAutomationRulesCriteria
{
    private int $page;
    private int $limit;
    private string $orderColumn;
    private string $orderDirection;

    public function __construct(int $page, int $limit, string $orderColumn, string $orderDirection)
    {
        $this->page = $page;
        $this->limit = $limit;
        $this->orderColumn = $orderColumn;
        $this->orderDirection = $orderDirection;

        if (!in_array($this->orderColumn, ['created_at'], true)) {
            throw new \InvalidArgumentException("Invalid order column");
        }

        if (!in_array($this->orderDirection, ['asc', 'desc'], true)) {
            throw new \InvalidArgumentException("Invalid order direction column");
        }
    }

    public function getOrderColumn(): string
    {
        return $this->orderColumn;
    }

    public function getOrderDirection(): string
    {
        return $this->orderDirection;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }
}
