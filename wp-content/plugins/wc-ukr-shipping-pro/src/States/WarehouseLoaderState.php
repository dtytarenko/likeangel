<?php

namespace kirillbdev\WCUkrShipping\States;

use kirillbdev\WCUkrShipping\DB\Repositories\WarehouseSyncRepository;

if ( ! defined('ABSPATH')) {
    exit;
}

class WarehouseLoaderState extends AppState
{
    /**
     * @var WarehouseSyncRepository
     */
    private $syncRepository;

    public function __construct(WarehouseSyncRepository $syncRepository)
    {
        $this->syncRepository = $syncRepository;
    }

    protected function getState()
    {
        return [
            'last_sync' => $this->syncRepository->getLastSync()
        ];
    }
}