<?php
namespace Bugzilla;

use Cache\Cache;

/**
 * Bugzilla class
 *
 * Bugzilla functions: perform searches, map locale code to componente name
 *
 *
 * @package Bugzilla
 */
class Bugzilla
{
    /**
     * List of Bugzilla locales per component, used only when the global
     * cache is not available.
     *
     * @var array list of locales [component_name => Json]
     */
    private static $bugzilla_fields_json = [];

    /**
     * Parse CSV content generated by an advanced search in Bugzilla
     *
     * @param array  $csv  CSV content of Bugzilla search (read with file())
     * @param string $full Return short or long results (default to false)
     *
     * @return array List of bugs and their descriptions
     */
    public static function getBugsFromCSV($csv, $full = false)
    {
        $full_bugs = [];
        $short_bugs = [];
        $single_bug = [];

        $csv_content = array_map('str_getcsv', $csv);
        foreach ($csv_content as $line) {
            if ($line[0] == 'bug_id') {
                /* If it starts with bug_id, I'm reading the first line
                 * with all field names
                 */
                $fields = $line;
                continue;
            }
            foreach ($fields as $key => $field) {
                $single_bug[$field] = $line[$key];
            }
            $short_bugs[$single_bug['bug_id']] = $single_bug['short_desc'];
            $full_bugs[] = $single_bug;
        }

        return $full ? $full_bugs : $short_bugs;
    }

    /**
     * Given a locale code and a product, determine the correct component name
     * on Bugzilla
     *
     * @param string  $locale     Locale code
     * @param string  $component  If I need the locale code for Mozilla
     *                            Localizations or www.mozilla.org (default)
     * @param boolean $log_errors If I need to log the error for missing locale
     * @param string  $url_query  URI of the JSON file to read
     *
     * @return string "Locale / Language name" for Bugzilla queries
     */
    public static function getBugzillaLocaleField($locale, $component = 'www', $log_errors = false, $url_query = '')
    {
        if ($url_query == '') {
            $url_query = "https://l10n.mozilla-community.org/mozilla-l10n-query/?bugzilla={$component}";
        }

        // Some locales don't exist on Bugzilla, map them to another code
        $exceptions = [
            'sr-Latn' => 'sr',
        ];
        if (isset($exceptions[$locale])) {
            $locale = $exceptions[$locale];
        }

        // Check if I have a cached request for this element
        $cache_id = "bugzilla_{$component}";

        $get_remote_json = function () use ($url_query) {
            // We don't want to hang the app if the remote server doesn't answer.
            // 5 seconds seems a decent amount of time.
            $context = stream_context_create([
                'http' => ['timeout' => 5],
            ]);

            return json_decode(file_get_contents($url_query, false, $context), true);
        };

        if (Cache::isActivated()) {
            if (! $json_data = Cache::getKey($cache_id)) {
                // No cache. Read remote and cache answer on disk.
                $json_data = $get_remote_json();
                if ($json_data !== null) {
                    Cache::setKey($cache_id, $json_data);
                }
            }
        } else {
            if (! isset(self::$bugzilla_fields_json[$component])) {
                // No application-wide caching system. Read remote and cache
                // answer in a static variable during script execution.
                self::$bugzilla_fields_json[$component] = $get_remote_json();
            }

            if (self::$bugzilla_fields_json[$component] !== null) {
                $json_data = self::$bugzilla_fields_json[$component];
            }
        }

        if (isset($json_data[$locale])) {
            // Return the default language name if it exists
            return $json_data[$locale];
        }

        // Locale does not exist in mapping. Log error if necessary
        if ($log_errors) {
            error_log("Missing locale {$locale} in locale mappings (Bugzilla Class)");
        }

        return $locale;
    }
}
