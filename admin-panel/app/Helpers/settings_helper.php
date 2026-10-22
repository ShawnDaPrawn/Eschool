<?php

use App\Models\Grade;
use App\Models\Language;
use App\Services\Platform\Tenancy\PlatformSettings;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Settings by type: the current school's rows over the platform's (see
 * App\Services\Platform\Tenancy\PlatformSettings).
 */
function getSettings($type = '')
{
    return PlatformSettings::read((string) $type);
}

/**
 * One school setting, e.g. schoolSetting('logo1') or schoolSetting('school_name').
 * Use this instead of env(): env() returns null once config is cached.
 */
function schoolSetting(string $type, $default = null)
{
    $value = getSettings($type)[$type] ?? null;

    return ($value === null || $value === '') ? $default : $value;
}

/** URL of an uploaded school logo ('logo1' or 'logo2'), or the default eSchool logo. */
function schoolLogoUrl(string $type = 'logo1'): string
{
    $path = schoolSetting($type);

    return $path ? url(Storage::url($path)) : url('assets/logo.svg');
}

function get_language()
{

    return Language::get();
}

function getTimeFormat()
{
    $timeFormat = [];
    $timeFormat['h:i a'] = 'h:i a - '.date('h:i a');
    $timeFormat['h:i A'] = 'h:i A - '.date('h:i A');
    $timeFormat['H:i'] = 'H:i - '.date('H:i');

    return $timeFormat;
}

function getDateFormat()
{
    $dateFormat = [];
    $dateFormat['d/m/Y'] = 'd/m/Y - '.date('d/m/Y');
    $dateFormat['m/d/Y'] = 'm/d/Y - '.date('m/d/Y');
    $dateFormat['Y/m/d'] = 'Y/m/d - '.date('Y/m/d');
    $dateFormat['Y/d/m'] = 'Y/d/m - '.date('Y/d/m');
    $dateFormat['m-d-Y'] = 'm-d-Y - '.date('m-d-Y');
    $dateFormat['d-m-Y'] = 'd-m-Y - '.date('d-m-Y');
    $dateFormat['Y-m-d'] = 'Y-m-d - '.date('Y-m-d');
    $dateFormat['Y-d-m'] = 'Y-d-m - '.date('Y-d-m');
    $dateFormat['F j, Y'] = 'F j, Y - '.date('F j, Y');
    $dateFormat['jS F Y'] = 'jS F Y - '.date('jS F Y');
    $dateFormat['l jS F'] = 'l jS F - '.date('l jS F');
    $dateFormat['d M, y'] = 'd M, y - '.date('d M, y');

    return $dateFormat;
}

function getTimezoneList()
{
    static $timezones = null;

    if ($timezones === null) {
        $list = DateTimeZone::listAbbreviations();
        $idents = DateTimeZone::listIdentifiers();

        $data = $offset = $added = [];
        foreach ($list as $abbr => $info) {
            foreach ($info as $zone) {
                if (! empty($zone['timezone_id']) and ! in_array($zone['timezone_id'], $added) and in_array($zone['timezone_id'], $idents)) {
                    $z = new DateTimeZone($zone['timezone_id']);
                    $c = new DateTime('', $z);
                    $zone['time'] = $c->format('H:i a');
                    $offset[] = $zone['offset'] = $z->getOffset($c);
                    $data[] = $zone;
                    $added[] = $zone['timezone_id'];
                }
            }
        }

        array_multisort($offset, SORT_ASC, $data);
        $i = 0;
        $temp = [];
        foreach ($data as $key => $row) {
            $temp[0] = $row['time'];
            $temp[1] = formatOffset($row['offset']);
            $temp[2] = $row['timezone_id'];
            $timezones[$i++] = $temp;
        }
    }

    return $timezones;
}

function formatOffset($offset)
{
    $hours = $offset / 3600;
    $remainder = $offset % 3600;
    $sign = $hours > 0 ? '+' : '-';
    $hour = (int) abs($hours);
    $minutes = (int) abs($remainder / 60);

    if ($hour == 0 and $minutes == 0) {
        $sign = ' ';
    }

    return $sign.str_pad($hour, 2, '0', STR_PAD_LEFT).':'.str_pad($minutes, 2, '0');
}

function flattenMyModel($model)
{
    $modelArr = $model->toArray();
    $data = [];
    array_walk_recursive($modelArr, function ($item, $key) use (&$data) {
        $data[$key] = $item;
    });

    return $data;
}

function findExamGrade($session_year_id, $percentage)
{
    $grades = Grade::where('session_year_id', $session_year_id)->get();

    if (count($grades)) {
        foreach ($grades as $row) {
            if (floor($percentage) >= $row['starting_range'] && floor($percentage) <= $row['ending_range']) {
                return $row->grade;
            }
        }
    } else {
        return '';
    }
}
function resizeImage($image)
{
    $manager = new ImageManager(new Driver);
    $img = $manager->read($image);
    $img->save($image->getPathname(), 50);

    return $img;
}

function convertDateFormat($dateString, $format = 'Y-m-d H:i:s')
{
    if (empty($dateString)) {
        return $dateString;
    }

    try {
        $date = new DateTime($dateString);

        return $date->format($format);
    } catch (Exception $e) {
        // Handle the exception if needed or return the original date string
        return $dateString;
    }
}
/**
 * Safe htmlspecialchars function that handles null bytes and other problematic characters
 *
 * @param  string|null  $string
 * @param  int  $flags
 * @param  string  $encoding
 * @param  bool  $double_encode
 * @return string
 */
function safe_htmlspecialchars($string, $flags = ENT_QUOTES | ENT_SUBSTITUTE, $encoding = 'UTF-8', $double_encode = true)
{
    if ($string === null) {
        return '';
    }

    // Convert to string if not already
    $string = (string) $string;

    // Remove null bytes and other problematic characters
    $string = preg_replace(pattern: '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', replacement: '', subject: $string);

    // Use htmlspecialchars with proper error handling
    $result = htmlspecialchars($string, $flags, $encoding, $double_encode);

    // If htmlspecialchars fails, try with a different approach
    if ($result === false) {
        // Remove any remaining problematic characters
        $string = preg_replace(pattern: '/[^\x20-\x7E\xA0-\xFF]/', replacement: '', subject: $string);
        $result = htmlspecialchars($string, $flags, $encoding, $double_encode);

        // If still fails, return empty string
        if ($result === false) {
            return '';
        }
    }

    return $result;
}

/**
 * Safe htmlspecialchars_decode function that handles null bytes and other problematic characters
 *
 * @param  string|null  $string
 * @param  int  $flags
 * @return string
 */
function safe_htmlspecialchars_decode($string, $flags = ENT_QUOTES | ENT_SUBSTITUTE)
{
    if ($string === null) {
        return '';
    }

    // Convert to string if not already
    $string = (string) $string;

    // Remove null bytes and other problematic characters
    $string = preg_replace(pattern: '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', replacement: '', subject: $string);

    // Use htmlspecialchars_decode with proper error handling
    $result = htmlspecialchars_decode($string, $flags);

    // If htmlspecialchars_decode fails, try with a different approach
    if ($result === false) {
        // Remove any remaining problematic characters
        $string = preg_replace(pattern: '/[^\x20-\x7E\xA0-\xFF]/', replacement: '', subject: $string);
        $result = htmlspecialchars_decode($string, $flags);

        // If still fails, return empty string
        if ($result === false) {
            return '';
        }
    }

    return $result;
}
