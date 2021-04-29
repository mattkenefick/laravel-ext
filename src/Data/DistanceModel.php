<?php

namespace PolymerMallard\Data;

use DB;

trait DistanceModel {
    /**
     * Spatial
     */
    protected $geofields = ['location'];

    public function scopeDistance($query, $dist, $location) {
        // return $query
        //     ->addSelect(DB::raw('st_distance(POINT(lat, lon), POINT(' . $location . ')) as `distance`'))
        //     ->whereRaw('st_distance(POINT(lat, lon), POINT(' . $location . ')) < ' . $dist)
        //     ->orderBy('distance', 'ASC');
        return $query
            ->addSelect(DB::raw('haversine_distance(lat, lon, ' . $location . ') as `distance`'))
            ->whereRaw('haversine_distance(lat, lon, ' . $location . ') < ' . $dist);
    }

    public function scopeWeightedOrder($query) {
        $timeRatio = 60 * 3;

        return $query
            ->from(DB::raw("(SELECT * FROM `$this->table` ORDER BY `created_at` DESC) as `$this->table`"))
            ->orderByRaw("((1 - TIMESTAMPDIFF(MINUTE, `created_at`, now()) / $timeRatio) * 2 + (1 - `distance` / 3) * 3) DESC");
    }

    public function setLocationAttribute($value) {
        $this->attributes['location'] = DB::raw("POINT($value)");
    }

    public function getLocationAttribute($value) {
        $loc = substr($value, 6);
        $loc = preg_replace('/[ ,]+/', ',', $loc, 1);

        return substr($loc, 0, -1);
    }

    public function newQuery($excludeDeleted = true) {
        $raw = '';

        foreach ($this->geofields as $column) {
            $raw .= ' astext(' . $column . ') as ' . $column . ' ';
        }

        return parent::newQuery($excludeDeleted)
            ->addSelect('*', DB::raw($raw));
    }

    public function coordinatesToLocation($lat = null, $lon = null) {
        if (!$lat && !$lon) {
            $lat = $this->latitude;
            $lon = $this->longitude;
        }

        // get lat long from google
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=$lat,$lon&key=AIzaSyCKT38B_X04HKrtXC-f4ZETjQChApQwEqw";
        $response = json_decode(file_get_contents($url));

        // $response = json_decode(file_get_contents("http://maps.googleapis.com/maps/api/geocode/json?address=$lat,$lon"));
        $address = [];

        if ($response->status == 'OK') {
            foreach ($response->results[0]->address_components as $comp) {
                $address[$comp->types[0]] = $comp->short_name;
            }

            return (object) $address;
        }

        return false;
    }
}
