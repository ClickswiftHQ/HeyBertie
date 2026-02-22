<?php

namespace App\Console\Commands;

use App\Models\GeocodeCache;
use Illuminate\Console\Command;

class ImportTownsCommand extends Command
{
    protected $signature = 'towns:import {file : Path to the townslist CSV file}';

    protected $description = 'Import UK towns from a townslist.co.uk CSV into the geocode_cache table';

    /** @var array<string, string> */
    private const COUNTY_MAP = [
        'Greater London' => 'London',
        'Greater Manchester' => 'Manchester',
        'City of Edinburgh' => 'Edinburgh',
        'City of Glasgow' => 'Glasgow',
        'City of Aberdeen' => 'Aberdeen',
        'City of Dundee' => 'Dundee',
    ];

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

        if (! isset($columns['name'], $columns['county'], $columns['latitude'], $columns['longitude'])) {
            $this->error('Missing required columns. Expected: name, county, latitude, longitude');
            fclose($handle);

            return self::FAILURE;
        }

        $hasCountry = isset($columns['country']);
        $hasPostcodeSector = isset($columns['postcode_sector']);
        $hasType = isset($columns['type']);

        $this->info('Pass 1: Scanning for duplicate names...');

        $nameCountyCounts = [];
        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $name = trim($row[$columns['name']] ?? '');
            $county = trim($row[$columns['county']] ?? '');
            $lat = (float) ($row[$columns['latitude']] ?? 0);
            $lng = (float) ($row[$columns['longitude']] ?? 0);
            $country = $hasCountry ? trim($row[$columns['country']] ?? '') : '';
            $postcodeSector = $hasPostcodeSector ? trim($row[$columns['postcode_sector']] ?? '') : '';
            $type = $hasType ? trim($row[$columns['type']] ?? '') : '';

            if ($name === '' || ($lat == 0 && $lng == 0)) {
                continue;
            }

            $displayCounty = self::COUNTY_MAP[$county] ?? $county;
            $nameCountyKey = mb_strtolower($name).'|'.mb_strtolower($displayCounty);
            $nameCountyCounts[$nameCountyKey] = ($nameCountyCounts[$nameCountyKey] ?? 0) + 1;

            $rows[] = [
                'name' => $name,
                'county' => $county,
                'display_county' => $displayCounty,
                'country' => $country,
                'postcode_sector' => $postcodeSector,
                'type' => $type,
                'latitude' => $lat,
                'longitude' => $lng,
            ];
        }

        fclose($handle);

        $duplicateNameCounty = array_filter($nameCountyCounts, fn (int $count) => $count > 1);

        $this->info('Pass 2: Importing '.count($rows).' towns...');
        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        $batch = [];
        $imported = 0;
        $usedSlugs = [];

        foreach ($rows as $row) {
            $displayCounty = $row['display_county'];
            $nameCountyKey = mb_strtolower($row['name']).'|'.mb_strtolower($displayCounty);
            $isNameCountyDuplicate = isset($duplicateNameCounty[$nameCountyKey]);

            // Always include county unless name matches county or county is empty
            if ($displayCounty !== '' && mb_strtolower($row['name']) !== mb_strtolower($displayCounty)) {
                $displayName = $row['name'].', '.$displayCounty;
            } else {
                $displayName = $row['name'];
            }

            $slug = str($displayName)->slug()->value();

            // Append postcode sector when name+county collides OR when slug already taken
            if ($isNameCountyDuplicate || isset($usedSlugs[$slug])) {
                if ($row['postcode_sector'] !== '') {
                    $sectorSlug = str($row['postcode_sector'])->slug()->value();
                    $slug = $slug.'-'.$sectorSlug;
                }

                // Numeric fallback if slug is still not unique
                $baseSlug = $slug;
                $counter = 2;
                while (isset($usedSlugs[$slug])) {
                    $slug = $baseSlug.'-'.$counter;
                    $counter++;
                }
            }

            $usedSlugs[$slug] = true;

            $batch[] = [
                'name' => $row['name'],
                'display_name' => $displayName,
                'slug' => $slug,
                'county' => $row['county'],
                'country' => $row['country'],
                'postcode_sector' => $row['postcode_sector'],
                'settlement_type' => $row['type'],
                'latitude' => $row['latitude'],
                'longitude' => $row['longitude'],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= 1000) {
                GeocodeCache::upsert($batch, ['name', 'county', 'postcode_sector'], ['display_name', 'slug', 'country', 'settlement_type', 'latitude', 'longitude', 'updated_at']);
                $imported += count($batch);
                $batch = [];
            }

            $bar->advance();
        }

        if (count($batch) > 0) {
            GeocodeCache::upsert($batch, ['name', 'county', 'postcode_sector'], ['display_name', 'slug', 'country', 'settlement_type', 'latitude', 'longitude', 'updated_at']);
            $imported += count($batch);
        }

        $bar->finish();
        $this->newLine();

        $this->info("Imported {$imported} towns.");

        return self::SUCCESS;
    }
}
