<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace filter_faq;

defined('MOODLE_INTERNAL') || die;

/**
 * This file provides and api that uses a string cache with a FAQ repository.
 *
 * @package    filter_faq
 * @copyright  2023 Austrian Federal Ministry of Education
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class stringlib  {
    private static $cache = null;

    public static function get_string(string $identifier, string $component, $a = null, bool $nobrackets = false): string {
        global $CFG;
        if (empty(self::$cache)) self::$cache = \cache::make('filter_faq', 'string');

        $cleanidentifier = clean_param($identifier, PARAM_STRINGID);
        $curlang = current_language();
        $secondarylang = \filter_faq\lib::default_lang();
        $langs = [$curlang];
        if ($curlang != $secondarylang) {
            $langs[] = $secondarylang;
        }
        foreach ($langs as $lang) {
            if (empty(self::$cache->get("loaded_{$component}_{$lang}"))) {
                self::load_component_strings($component, $lang);
            }
            $strings = self::$cache->get(self::cache_key($lang, $component));
            if (!empty($strings[$cleanidentifier])) return self::get_string_enhanced($strings[$cleanidentifier], $a);
        }
        // Fallback to default function
        if (\get_string_manager()->string_exists($cleanidentifier, $component)) {
            return \get_string_manager()->get_string($cleanidentifier, $component, $a);
        }
        if ($nobrackets) return $identifier;
        else return "[[$cleanidentifier]]";
    }

    private static function get_string_enhanced(string $string, $a = null): string {
        if ($a !== null) {
            // Process array's and objects (except lang_strings).
            if (is_array($a) or (is_object($a) && !($a instanceof lang_string))) {
                $a = (array)$a;
                $search = array();
                $replace = array();
                foreach ($a as $key => $value) {
                    if (is_int($key)) {
                        // We do not support numeric keys - sorry!
                        continue;
                    }
                    if (is_array($value) or (is_object($value) && !($value instanceof lang_string))) {
                        // We support just string or lang_string as value.
                        continue;
                    }
                    $search[]  = '{$a->'.$key.'}';
                    $replace[] = (string)$value;
                }
                if ($search) {
                    $string = str_replace($search, $replace, $string);
                }
            } else {
                $string = str_replace('{$a}', (string)$a, $string);
            }
        }
        return $string;
    }

    private static function load_component_strings($component, $lang) {
        global $CFG;
        $basepath = "$CFG->dataroot/faq/stringlib";
        $path = "$basepath/$lang/$component.json";
        $file = realpath($path);
        if (strpos($file, $basepath) === 0) {
            if (file_exists($file)) {
                $strings = (array) json_decode(file_get_contents($file));
                self::$cache->set(self::cache_key($lang, $component), $strings);
                if (count($strings) > 1) {
                    self::$cache->set("loaded_{$component}_{$lang}", 1);
                }
            }
        } else if($file) {
            throw new \moodle_exception('exception:file_outside_bounds', 'filter_faq');
        }
    }
    private static function cache_key(string $lang, string $component) {
        return implode('__', [ $lang, $component ]);
    }
}
