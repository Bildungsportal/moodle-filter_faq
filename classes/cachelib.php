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

/**
 * @package    filter_faq
 * originally developed for local_eduportal by:
 * @copyright  2022 Austrian Federal Ministry of Education
 * @author     GTN solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_faq;

use cache;

defined('MOODLE_INTERNAL') || die;

class cachelib {
    private static $caches = [];
    private static $cacheDisabled = null;

    const FALSE_VALUE = 'cachelib_internal_false_value';

    private static function type(string $type) {
        global $CFG;

        if (!empty(self::$caches[$type])) {
            // already created?
            return self::$caches[$type];
        }

        // type allowed?
        $definitions = [];
        require("$CFG->dirroot/filter/faq/db/caches.php");
        if (!in_array($type, array_keys($definitions))) {
            throw new \moodle_exception("cache type {$type} not supported");
        }

        $cache = cache::make('filter_faq', $type);
        self::$caches[$type] = $cache;

        return $cache;
    }

    /**
     * Getter for caches.
     * @param string $type either application or session.
     * @param string $key (optional) used to get a certain key from the cache.
     * @return mixed the cached value, or null if no cache! (this differs to moodle, which returns false on cache on no cache
     */
    public static function get(string $type, $key) {
        $key = static::stringKey($key);
        $ret = static::type($type)->get($key);

        if ($ret === false) {
            // no value from moodle cache => null
            return null;
        } elseif ($ret === static::FALSE_VALUE) {
            // the FALSE_VALUE was stored => false
            return false;
        } else {
            return $ret;
        }
    }

    /**
     * Method used as setter for caches.
     * @param string $type either application or session.
     * @param string $key (optional) used to get a certain key from the cache.
     * @param mixed $value (optional) used to set a value for a certain key in the cache.
     */
    public static function set(string $type, $key, $value) {
        $key = static::stringKey($key);

        if ($value === false) {
            $value = static::FALSE_VALUE;
        }
        static::type($type)->set($key, $value);
    }

    /**
     * Delete cache entry
     * @param string $type either application or session.
     * @param string $key (optional) used to get a certain key from the cache.
     */
    public static function delete(string $type, $key) {
        $key = static::stringKey($key);
        static::type($type)->delete($key);
    }

    public static function stringKey($key) {
        if (!is_scalar($key)) {
            return json_encode($key);
            // return join('-', $key);
        } else {
            return $key;
        }
    }

    public static function callbackWithBypassCache(string $type, array $key, bool $bypasscache, callable $callback) {
        if (!$bypasscache) {
            $ret = static::get($type, $key);
            if ($ret !== null) {
                return $ret;
            }
        }

        $ret = $callback();

        if ($ret !== null) {
            static::set($type, $key, $ret);
        }

        return $ret;
    }

    public static function callback(string $type, array $key, callable $callback) {
        return static::callbackWithBypassCache($type, $key, false, $callback);
    }
}
