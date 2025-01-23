<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Modules\Backend;

use kirillbdev\WCUkrShipping\Component\Task\CheckLicenseHandler;
use kirillbdev\WCUkrShipping\Component\Task\Task;
use kirillbdev\WCUkrShipping\Services\TaskService;
use kirillbdev\WCUSCore\Contracts\ModuleInterface;

if ( ! defined('ABSPATH')) {
    exit;
}

class Jobs implements ModuleInterface
{
    private TaskService $taskService;

    private array $workers = [
        '1.0.0' => 'workersV100'
    ];

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    public function init(): void
    {
        $this->taskService->registerHandler('wcus_check_license', CheckLicenseHandler::class);
        add_action('admin_init', [$this, 'initWorkers']);
    }

    public function initWorkers(): void
    {
        $currentVersion = get_option('wcus_workers_version', '');

        foreach ($this->workers as $version => $callback) {
            if (version_compare($currentVersion, $version, '<' )) {
                call_user_func([$this, $callback]);
                $currentVersion = $version;
            }
        }

        update_option('wcus_workers_version', $currentVersion);
    }

    private function workersV100(): void
    {
        wp_unschedule_hook('wcus_check_license');
        $this->taskService->scheduleRepeatable(
            new Task('wcus_check_license'),
            time(),
            'hourly'
        );
    }
}
