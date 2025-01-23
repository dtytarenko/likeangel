<?php

namespace kirillbdev\WCUkrShipping\DB\Repositories;

use kirillbdev\WCUkrShipping\DB\Dto\InsertAreaDto;
use kirillbdev\WCUSCore\Facades\DB;

if ( ! defined('ABSPATH')) {
    exit;
}

class AreaRepository
{
    public function clearAreas()
    {
        DB::table(DB::prefixedTable('wc_ukr_shipping_np_areas'))->truncate();
    }

    public function insertArea(InsertAreaDto $dto)
    {
        DB::table(DB::prefixedTable('wc_ukr_shipping_np_areas'))->insert([
            'ref' => $dto->getRef(),
            'description' => $dto->getDescription()
        ]);
    }
}