<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Services;

use kirillbdev\WCUkrShipping\Component\Task\TaskHandlerInterface;
use kirillbdev\WCUkrShipping\Component\Task\TaskInterface;

if ( ! defined('ABSPATH')) {
    exit;
}

final class TaskService
{
    public function scheduleSingle(TaskInterface $task, int $nextRun): void
    {
        wp_schedule_single_event($nextRun, $task->getName(), [$task]);
    }

    public function scheduleRepeatable(TaskInterface $task, int $nextRun, string $interval): void
    {
        $trustedIntervals = array_keys(wp_get_schedules());
        if (!in_array($interval, $trustedIntervals, true)) {
            throw new \InvalidArgumentException("Invalid interval '$interval'");
        }

        wp_schedule_event($nextRun, $interval, $task->getName(), [$task]);
    }

    public function registerHandler(string $taskName, string $handlerClass): void
    {
        $handler = $this->mustCreateTaskHandler($handlerClass);
        add_action($taskName, [$handler, 'handle']);
    }

    private function mustCreateTaskHandler(string $class): TaskHandlerInterface
    {
        if (!class_exists($class)) {
            throw new \InvalidArgumentException("Class $class doesn't exist");
        }

        try {
            $handlerInstance = wcus_container()->make($class);
        } catch (\Exception $e) {
            $handlerInstance = null;
        }

        if ($handlerInstance === null) {
            throw new \InvalidArgumentException("Failed to instantiate handler");
        } elseif (!$handlerInstance instanceof TaskHandlerInterface) {
            throw new \InvalidArgumentException("Provided handler $class doesn't implement TaskHandlerInterface");
        }

        return $handlerInstance;
    }
}
