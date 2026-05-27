<?php
// GeoFence — Haversine formula distance calculator for attendance geo-locking
class GeoFence
{
    private const EARTH_RADIUS_M = 6371000;

    /**
     * Calculate distance in metres between two GPS coordinates using Haversine formula.
     */
    public static function distance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_M * $c;
    }

    /**
     * Check if coordinates are within the allowed radius of a site.
     */
    public static function isOnSite(float $userLat, float $userLon, float $siteLat, float $siteLon, float $radiusMetres): bool
    {
        return self::distance($userLat, $userLon, $siteLat, $siteLon) <= $radiusMetres;
    }

    /**
     * Check if current server time is within the attendance window (EAT timezone).
     * Default window: 08:00 — 08:40
     */
    public static function isWithinTimeWindow(string $openTime = '08:00', string $closeTime = '08:40'): bool
    {
        $tz  = new DateTimeZone('Africa/Nairobi');
        $now = new DateTime('now', $tz);
        $open  = DateTime::createFromFormat('H:i', $openTime, $tz);
        $close = DateTime::createFromFormat('H:i', $closeTime, $tz);
        return $now >= $open && $now <= $close;
    }
}
