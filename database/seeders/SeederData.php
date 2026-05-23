<?php

namespace Database\Seeders;

use RuntimeException;

class SeederData
{
    public static function read(): array
    {
        $path = base_path('seederStarter.json');
        $json = file_get_contents($path);

        if ($json === false) {
            throw new RuntimeException("Unable to read seed data file: {$path}");
        }

        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data)) {
            throw new RuntimeException("Seed data file is not a JSON object: {$path}");
        }

        return $data;
    }
}
