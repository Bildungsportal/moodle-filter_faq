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
 * @package    local_faq
 * @copyright  2023 Austrian Federal Ministry of Education
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_faq;

defined('MOODLE_INTERNAL') || die;

class lib {
    /**
     * Returns the fallback language.
     * @return string
     */
    public static function default_lang(): string {
        return 'en';
    }

    /**
     * Get the contents of a helptext or a sorry-info.
     * @param int $pathid the path of the item.
     * @param string $content the content identifier: version, shorttitle, longtitle, shortdescription, longdescription
     * @param array $langs the languages to use
     * @return string
     */
    public static function get_content(int $pathid, string $content, array $langs): string {
        global $CFG;

        $path = self::get_path($pathid);
        $basepath = "$CFG->dataroot/faq";

        $generalfilepath = new \moodle_url('/filter/faq/generalfile.php', [ 'l' => $langs[0] ]);
		$elementfilepath = new \moodle_url('/filter/faq/elementfile.php', [ 'l' => $langs[0], 'p' => $pathid ]);

        foreach ($langs as $lang) {
            // For security reasons we check again if the realpath is valid and inside the boundaries.
            $file = realpath("$basepath/$lang/$path/$content");
            if (strpos($file, $basepath) === 0) {
                if (file_exists($file)) {
                    $text = file_get_contents($file);
                    $text = str_replace('{{GENERALPATH}}', $generalfilepath->out() . '&f=', $text);
                    $text = str_replace('{{ELEMENTPATH}}', $elementfilepath->out() . '&f=', $text);
                    return self::enhancetext($text);
                }
            } else if($file) {
                throw new \moodle_exception('exception:file_outside_bounds', 'filter_faq');
            }
        }

        return get_string('no_faq_found_for', 'filter_faq', [ 'path' => $path, 'content' => $content]);
    }

	/**
	 * Get the absolute filepath of an embedded file within a FAQ item.
	 * @param int $pathid the pathid of the item.
	 * @param string $filename the filename.
	 * @param array $langs the languages to use.
	 * @return string|null the absolute file path within the dataroot.
	 */
	public static function get_filepath(int $pathid, string $filename, array $langs): ?string {
		global $CFG;
        $path = self::get_path($pathid);
        $basepath = "$CFG->dataroot/faq";

        foreach ($langs as $lang) {
            // For security reasons we check again if the realpath is valid and inside the boundaries.
            $file = self::realpath("$basepath/$lang/$path/$filename");
            if (strpos($file, $basepath) !== 0) {
                throw new \moodle_exception('exception:file_outside_bounds', 'filter_faq');
            } else if(file_exists($file)) {
                return $file;
            }
        }
       return null;
	}

    /**
     * Get the absolute filepath of an embedded file in the general files folder.
     * @param int $pathid the pathid of the item.
     * @param string $filename the filename.
     * @param array $langs the languages to use.
     * @return string|null the absolute file path within the dataroot.
     */
    public static function get_generalfilepath(string $filename, array $langs): ?string {
        global $CFG;
        $basepath = "$CFG->dataroot/faq/general";

        foreach ($langs as $lang) {
            // For security reasons we check again if the realpath is valid and inside the boundaries.
            $file = self::realpath("$basepath/$lang/$filename");
            if (strpos($file, $basepath) !== 0) {
                throw new \moodle_exception('exception:file_outside_bounds', 'filter_faq');
            } else if (file_exists($file)) {
                return $file;
            }
        }
        return null;
    }

    /**
     * Get the relative path of a pathid.
     * @param int $pathid
     * @return string|null
     * @throws \dml_exception
     */
    public static function get_path(int $pathid): ?string {
        global $DB;
        $path = \filter_faq\cachelib::get('pages', $pathid);
        if (!empty($path)) return $path;

        $record = $DB->get_record('filter_faq', [ 'id' => $pathid ]);
        if ($record) {
            \filter_faq\cachelib::set('pages', $pathid, $record->path);
            return $record->path;
        }
        throw new \moodle_exception('exception:no_such_path', 'filter_faq', '', [ 'pathid' => $pathid]);
    }

    /**
     * Test if a certain relative path exists in a certain language, and that it is not outside the FAQ-basepath.
     * @param string $path
     * @param string $lang
     * @return null|string
     * @throws \moodle_exception if invalid path was given.
     */
    public static function get_pathid(string $path, string $lang = ''): ?string {
        global $CFG, $DB;
        // No preceding slash.
        if (substr($path, 0, 1) == '/') {
            $path = substr($path, 1);
        }
        if (substr($path, 0, 2) == './') {
            $path = str_replace('./', '', $path);
        }
        if (substr($path, 0, 2) == '../') {
            $path = str_replace('../', '', $path);
        }
        // Avoid double slashes.
        $path = str_replace('//', '/', $path);
        // Not allowed to use such parameters.
        $path = str_replace('/../', '/', $path);


        if (empty($lang)) {
            $lang = \filter_faq\lib::default_lang();
        }
        // Get the pathid from cache.
        $pathid = \filter_faq\cachelib::get('pages', $path);
        if (!empty($pathid)) {
            return $pathid;
        }
        // Load pathid from database.
        $record = $DB->get_record('filter_faq', [ 'path' => $path ]);
        if ($record) {
            \filter_faq\cachelib::set('pages', $path, $record->id);
            return $record->id;
        }
        // Enter path in database if it is within the boundaries and the default language folder.
        $basepath = "$CFG->dataroot/faq";
        $file = self::realpath("$basepath/$lang/$path");
        if (strpos($file, $basepath) !== 0) {
            throw new \moodle_exception('exception:file_outside_bounds', 'filter_faq');
        } else if (file_exists($file)) {
            $record = (object) [
                'path' => $path,
                'timecreated' => time(),
            ];
            $record->id = $DB->insert_record('filter_faq', $record);
            \filter_faq\cachelib::set('pages', $path, $record->id);
            return $record->id;
        }
        return null;
    }

    /**
     * Detect if text contains html and enhance accordingly.
     * @param string $text
     * @return string
     */
    private static function enhancetext(string $text): string {
        $needles = [ 'div', 'p', 'table', 'ol', 'ul', 'a', 'img', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ];
        foreach ($needles as $needle) {
            if (strpos($text, "<$needle") !== false) {
                return $text;
            }
        }
        return nl2br($text);
    }

    public static function permalink(string $path): string {
        global $CFG;
        $urlshortener = get_config('filter_faq', 'urlshortener');
        if (!empty($urlshortener)) {
            $delimiter = '/';
            if (str_ends_with($urlshortener, '/')) { $delimiter = ''; }
            return $urlshortener . $delimiter . $path;
        } else {
            return "{$CFG->wwwroot}/filter/faq/page.php?path={$path}";
        }
    }

    /**
     * Use something like the realpath()-function on files that do not exist.
     * @param string $path
     * @return string
     */
    private static function realpath(string $path): string {
        return array_reduce(explode('/', $path), function($a, $b) {
            if ($a === null) {
                $a = "/";
            }
            if ($b === "" || $b === ".") {
                return $a;
            }
            if ($b === "..") {
                return dirname($a);
            }

            return preg_replace("/\/+/", "/", "$a/$b");
        });
    }
}