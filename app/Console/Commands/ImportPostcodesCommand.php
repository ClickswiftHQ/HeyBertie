<?php

namespace App\Console\Commands;

use App\Support\PostcodeFormatter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportPostcodesCommand extends Command
{
    protected $signature = 'postcodes:import {file : Path to the ONSPD CSV file}';

    protected $description = 'Import postcodes from an ONSPD CSV file into the postcodes table';

    public function handle(): int
    {
        $path = $this->argument('file');

        if (! file_exists($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $handle = fopen($path, 'r');

        if (! $handle) {
            $this->error("Could not open file: {$path}");

            return self::FAILURE;
        }

        $header = fgetcsv($handle);

        if (! $header) {
            $this->error('Could not read CSV header.');
            fclose($handle);

            return self::FAILURE;
        }

        $columns = array_flip(array_map('trim', $header));

        // Support both older ONSPD formats (pcd, oscty) and newer ones (pcds, cty25cd)
        $postcodeCol = $columns['pcds'] ?? $columns['pcd'] ?? null;

        if ($postcodeCol === null || ! isset($columns['lat']) || ! isset($columns['long'])) {
            $this->error('Missing required columns. Expected: pcds (or pcd), lat, long');
            fclose($handle);

            return self::FAILURE;
        }

        $countyCol = $columns['oscty'] ?? $columns['cty25cd'] ?? null;
        $regionCol = $columns['oslaua'] ?? $columns['lad25cd'] ?? null;
        $townCol = $columns['lsoa11nm'] ?? null;

        $this->info('Importing postcodes...');
        $bar = $this->output->createProgressBar();
        $bar->start();

        $batch = [];
        $imported = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $lat = (float) ($row[$columns['lat']] ?? 0);
            $lng = (float) ($row[$columns['long']] ?? 0);

            // Skip rows without valid coordinates
            if ($lat == 0 && $lng == 0) {
                continue;
            }

            $rawPostcode = trim($row[$postcodeCol] ?? '');

            if (! $rawPostcode) {
                continue;
            }

            $batch[] = [
                'postcode' => PostcodeFormatter::format($rawPostcode),
                'latitude' => $lat,
                'longitude' => $lng,
                'town' => $townCol !== null ? ($row[$townCol] ?? null) : null,
                'county' => $countyCol !== null ? ($row[$countyCol] ?? null) : null,
                'region' => $regionCol !== null ? ($row[$regionCol] ?? null) : null,
            ];

            if (count($batch) >= 1000) {
                DB::table('postcodes')->upsert($batch, ['postcode'], ['latitude', 'longitude', 'town', 'county', 'region']);
                $imported += count($batch);
                $batch = [];
                $bar->advance(1000);
            }
        }

        if (count($batch) > 0) {
            DB::table('postcodes')->upsert($batch, ['postcode'], ['latitude', 'longitude', 'town', 'county', 'region']);
            $imported += count($batch);
            $bar->advance(count($batch));
        }

        fclose($handle);
        $bar->finish();

        $this->newLine();
        $this->info("Imported {$imported} postcodes.");

        return self::SUCCESS;
    }
}
