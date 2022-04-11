<?php
namespace TypeRocket\Utility;

class DateTime
{
    public static function transformWordPressTimeZoneStringToTimeZone($tz) : \DateTimeZone
    {
        if(preg_match('/^UTC[+-]/', $tz)) {
            $tz = preg_replace('/UTC\+?/', '', $tz);
        }

        return new \DateTimeZone($tz);
    }

    /**
     * Get \DateTimeZone Object
     *
     * @param string $tz WordPress style timezone
     *
     * @return \DateTimeZone
     */
    public static function newDateTimezone(string $tz) : \DateTimeZone
    {
        return static::transformWordPressTimeZoneStringToTimeZone($tz);
    }

    /**
     * Get \DateTime Object
     *
     * @param string $dt HTML5 datetime input field default format
     * @param string $tz WordPress style timezone
     *
     * @return null|\DateTime
     * @throws \Exception
     */
    public static function newDateTime(string $dt, string $tz = 'UTC') : ?\DateTime
    {
        $date = null;

        if($dt)
        {
            $date = new \DateTime($dt, static::newDateTimezone($tz ?? 'UTC'));
        }

        return $date;
    }

    /**
     * Get \DateTime Object and Switch Timezones to UTC
     *
     * @param string $dt HTML5 datetime input field default format
     * @param string $from_tz from this WordPress style timezone to UTC
     *
     * @return null|\DateTime
     * @throws \Exception
     */
    public static function switchDatesTimezoneToUTC(string $dt, string $from_tz) : ?\DateTime
    {
        return static::newDateTime($dt, $from_tz)
            ->setTimezone(new \DateTimeZone('UTC'));
    }

    /**
     * Switch \DateTime Object's Timezone from UTC
     *
     * @param string $dt HTML5 datetime input field default format
     * @param string $to_tz to this WordPress style timezone from UTC
     *
     * @return null|\DateTime
     * @throws \Exception
     */
    public static function switchDatesTimezoneFromUTC(string $dt, string $to_tz) : ?\DateTime
    {
        return static::newDateTime($dt, 'UTC')
            ->setTimezone(static::newDateTimezone($to_tz));
    }

    /**
     * Get \DateTime Object and Switch Timezones to Site Timezone
     *
     * @param string $dt HTML5 datetime input field default format
     * @param string $from_tz from this WordPress style timezone to site timezone
     *
     * @return null|\DateTime
     * @throws \Exception
     */
    public static function switchDatesTimezoneToSiteTimezone(string $dt, string $from_tz) : ?\DateTime
    {
        return static::newDateTime($dt, $from_tz)
            ->setTimezone(static::newDateTimezone(wp_timezone_string()));
    }

    /**
     * Get \DateTime Object and Switch Timezones from Site Timezone
     *
     * @param string $dt HTML5 datetime input field default format
     * @param string $to_tz to this WordPress style timezone from site timezone
     *
     * @return null|\DateTime
     * @throws \Exception
     */
    public static function switchDatesTimezoneFromSiteTimezone(string $dt, string $to_tz) : ?\DateTime
    {
        return static::newDateTime($dt, wp_timezone_string())
            ->setTimezone(static::newDateTimezone($to_tz));
    }

    /**
     * @param string $dt
     * @param string $tz
     * @return \DateInterval
     * @throws \Exception
     */
    public static function getDiffDateIntervalFromNow(string $dt, string $tz = 'UTC') : ?\DateInterval
    {
        $date = static::newDateTime($dt, $tz);

        if(is_null($date)) {
            return null;
        }

        return $date->diff(new \DateTime());
    }

    /**
     * @param \DateInterval $interval
     * @return string
     */
    public static function agoFormatFromDateDiff(\DateInterval $interval)
    {
        $doPlural = function($nb,$str){return $nb>1?$str.'s':$str;}; // adds plurals

        $format = array();
        if($interval->y !== 0) {
            $format[] = "%y ".$doPlural($interval->y, "year");
        }
        if($interval->m !== 0) {
            $format[] = "%m ".$doPlural($interval->m, "month");
        }
        if($interval->d !== 0) {
            $format[] = "%d ".$doPlural($interval->d, "day");
        }
        if($interval->h !== 0) {
            $format[] = "%h ".$doPlural($interval->h, "hour");
        }
        if($interval->i !== 0) {
            $format[] = "%i ".$doPlural($interval->i, "minute");
        }
        if($interval->s !== 0) {
            if(!count($format)) {
                return "less than a minute ago";
            } else {
                $format[] = "%s ".$doPlural($interval->s, "second");
            }
        }

        if(empty($format)) {
            return "just now";
        }

        // We use the two biggest parts
        if(count($format) > 1) {
            $format = array_shift($format)." and ".array_shift($format);
        } else {
            $format = array_pop($format);
        }

        // Prepend 'since ' or whatever you like
        return $interval->format($format);
    }
}