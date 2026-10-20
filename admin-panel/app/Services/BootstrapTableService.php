<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Blade;

class BootstrapTableService
{
    private static string $defaultClasses = 'btn btn-xs btn-rounded btn-icon';

    /** Per-request cache of rendered Lucide icon markup, keyed by semantic name. */
    private static array $iconCache = [];

    /**
     * Render a Lucide icon (via the <x-icon> component — the single icon source)
     * to an SVG string suitable for embedding in the table's JSON `operate` cells.
     * Memoised so each glyph compiles once per request, not once per row.
     */
    private static function icon(string $name): string
    {
        return self::$iconCache[$name] ??= trim(Blade::render('<x-icon name="'.$name.'" size="16" />'));
    }

    /**
     * @param  string  $icon  Semantic <x-icon> key (e.g. "edit", "trash", "eye")
     * @return string
     */
    public static function button(string $icon, string $url, array $customClass = [], array $customAttributes = [])
    {
        $customClassStr = implode(' ', $customClass);
        $class = self::$defaultClasses.' '.$customClassStr;
        $attributes = '';
        if (count($customAttributes) > 0) {
            foreach ($customAttributes as $key => $value) {
                $attributes .= $key.'="'.$value.'" ';
            }
        }

        return '<a href="'.$url.'" class="'.$class.'" '.$attributes.'>'.self::icon($icon).'</a>&nbsp;&nbsp;';
    }

    /**
     * @return string
     */
    public static function editButton($url, bool $modal = true)
    {
        $customClass = ['edit-data', 'btn-gradient-primary'];
        $customAttributes = [
            'title' => trans('Edit'),
        ];
        if ($modal) {
            $customAttributes = [
                'title' => 'Edit',
                'data-toggle' => 'modal',
                'data-target' => '#editModal',
            ];

            $customClass[] = 'set-form-url';
        }

        return self::button('edit', $url, $customClass, $customAttributes);
    }

    /**
     * @return string
     */
    public static function deleteButton($url)
    {
        $customClass = ['delete-form', 'btn-gradient-dark'];
        $customAttributes = [
            'title' => trans('Delete'),
        ];

        return self::button('trash', $url, $customClass, $customAttributes);
    }

    /**
     * @return string
     */
    public static function restoreButton($url, string $title = 'Restore')
    {
        $customClass = ['btn-gradient-success', 'restore-data'];
        $customAttributes = [
            'title' => trans($title),
        ];

        return self::button('refresh', $url, $customClass, $customAttributes);
    }

    /**
     * @return string
     */
    public static function trashButton($url)
    {
        $customClass = ['btn-gradient-danger', 'trash-data'];
        $customAttributes = [
            'title' => trans('Delete Permanent'),
        ];

        return self::button('close', $url, $customClass, $customAttributes);
    }

    /**
     * @return string
     */
    public static function viewRelatedDataButton($url)
    {
        $customClass = ['related-data-form', 'btn-inverse-primary'];
        $customAttributes = [
            'title' => trans('View Related Data'),
        ];

        return self::button('eye', $url, $customClass, $customAttributes);
    }

    // Menu list

    public static function menuButton($title, $url, $customClass = [], $customAttributes = [])
    {
        $attributes = '';
        $customClassStr = implode(' ', $customClass);
        if (count($customAttributes) > 0) {
            foreach ($customAttributes as $key => $value) {
                $attributes .= $key.'="'.$value.'" ';
            }
        }

        return '<a href="'.$url.'" class="dropdown-item p-2 '.$customClassStr.'" '.$attributes.'>'.trans($title).'</a>';
    }

    public static function menuEditButton($title, $url, bool $modal = true)
    {
        $customClass = ['edit-data'];
        $customAttributes = [];
        if ($modal) {
            $customAttributes = [
                'data-toggle' => 'modal',
                'data-target' => '#editModal',
            ];

            $customClass[] = ' set-form-url';
        }

        return self::menuButton($title, $url, $customClass, $customAttributes);
    }

    public static function menuDeleteButton($title, $url)
    {
        $customClass = ['delete-form'];
        $customAttributes = [
            'title' => trans('Delete'),
        ];

        return self::menuButton($title, $url, $customClass, $customAttributes);
    }

    public static function menuRestoreButton($title, $url)
    {
        $customClass = ['restore-data'];
        $customAttributes = [];

        return self::menuButton($title, $url, $customClass, $customAttributes);
    }

    public static function menuTrashButton($title, $url)
    {
        $customClass = ['trash-data'];
        $customAttributes = [];

        return self::menuButton($title, $url, $customClass, $customAttributes);
    }

    public static function menuItem($operate)
    {

        // return '<div class="dropdown"> <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="dropdownMenu2" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> Dropdown </button> <div class="dropdown-menu" aria-labelledby="dropdownMenu2"> '. $operate .' </div> </div>';

        return '<div class="dropdown table-action-column d-flex align-items-center"> <button class="btn btn-sm btn-inverse-dark d-flex align-items-center" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> '.self::icon('ellipsis-vertical').' </button> <div class="dropdown-menu action-column-dropdown-menu" aria-labelledby="dropdownMenuButton"> '.$operate.' </div> </div>';
    }
}
