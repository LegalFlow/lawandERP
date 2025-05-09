<?php

namespace App\Helpers;

class RegionHelper
{
    private static $mappingCache = null;

    private static function loadRegionMapping()
    {
        if (self::$mappingCache !== null) {
            return self::$mappingCache;
        }

        $path = resource_path('views/targets/region.csv');
        $mapping = [];
        
        if (file_exists($path)) {
            $file = fopen($path, 'r');
            while (($line = fgetcsv($file)) !== false) {
                \Log::info('CSV Line:', $line);
                $place = trim(str_replace("\xEF\xBB\xBF", '', $line[0]));
                $region = trim($line[1]);
                $mapping[$place] = $region;
            }
            fclose($file);
        }
        
        \Log::info('Final Mapping:', $mapping);
        self::$mappingCache = $mapping;
        return $mapping;
    }

    public static function getRegionForPlace($place)
    {
        $mapping = self::loadRegionMapping();
        
        foreach ($mapping as $key => $region) {
            if (str_contains($place, $key)) {
                return $region;
            }
        }
        
        return null;
    }

    public static function getTargetsWithRegion($query)
    {
        return $query->get()->map(function ($target) {
            $target->region = self::getRegionForPlace($target->living_place) ?? '기타';
            return $target;
        });
    }

    public static function getAllPlacesForRegion($region)
    {
        $mapping = self::loadRegionMapping();
        return collect($mapping)
            ->filter(function($mappedRegion) use ($region) {
                return $mappedRegion === $region;
            })
            ->keys()
            ->toArray();
    }
} 