<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Region;
use App\Models\Town;

class ParseRegions extends Command
{
    protected $signature = 'parse:regions';

    protected $description = 'Parse regions JSON file';

    public function handle()
    {
        ini_set('memory_limit', '2048M');
        $data = json_decode(file_get_contents('https://eventhes.com/storage/files/koatuu.json'), true);

        foreach ($data as $item) {
            $region = Region::updateOrCreate(
                ['name' => $item['Назва об\'єкта українською мовою']],
                ['code' => $item['Перший рівень']]
            );

            if (isset($item['Другий рівень']) && !empty($item['Другий рівень'])) {
                $town = Town::updateOrCreate(
                    ['name' => $item['Назва об\'єкта українською мовою']],
                    ['code' => $item['Другий рівень'], 'region_id' => $region->id]
                );
            }
        }

        $this->info('Regions parsed successfully!');
    }
}
