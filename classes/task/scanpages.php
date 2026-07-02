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
 * @copyright  2026 Austrian Federal Ministry of Education
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Scan all pages in the faq-path for their versions and data updates.
 */

namespace filter_faq\task;

defined('MOODLE_INTERNAL') || die;

class scanpages extends \core\task\scheduled_task {
    private array $foundpathids;
    public function get_name() {
        return get_string('task:scanpages', 'filter_faq');
    }

    public function execute() {
        global $CFG, $DB;
        $faqpath = "{$CFG->dataroot}/faq";
        $languages = scandir($faqpath);
        foreach ($languages as $language) {
            // Ignore ., .. and other hidden files as well as the 'general'- and 'stringlib'-folder
            if (mb_substr($language, 0, 1) == '.' || $language == 'general' || $language == 'stringlib') {
                continue;
            }
            mtrace("=> Process language $language");
            // Reset the foundpathids array for this language
            $this->foundpathids = [];
            // Start recursive scan of the language.
            if (is_dir("{$faqpath}/{$language}")) {
                $this->scan_path($language, "");
            }
            // Remove all pages from the index that have been removed within this language.
            [ $insql, $inparams ] = $DB->get_in_or_equal($this->foundpathids, equal: false, onemptyitems: null);
            if (count($inparams) > 0) {
                $count = $DB->count_records_select('filter_faq_pages',"pathid {$insql}", $inparams);
                if ($count > 0) {
                    mtrace("==> Remove {$count} pages from this language");
                    $DB->delete_records_select('filter_faq_pages', "pathid {$insql}", $inparams);
                } else {
                    mtrace("==> No pages have been removed");
                }
            }
        }
    }

    /**
     * Process scanning a particular path if it contains page data.
     * @param string $lang
     * @param string $path
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    private function scan_path(string $lang, string $path): void {
        global $CFG;
        $itempath = "{$CFG->dataroot}/faq/{$lang}/{$path}";
        mtrace("==> Process {$lang}/{$path}");
        // Expected files that indicate this is a used text block.
        $expectedfiles = [
            'toc.json',
            'data.json',
            'longdescription',
            'longtitle',
            'shortdescription',
            'shorttitle',
            'version',
        ];
        $pathid = 0;
        foreach ($expectedfiles as $expectedfile) {
            if (is_file("{$itempath}/{$expectedfile}")) {
                // Ensure a filter_faq-record exists for this path.
                $pathid = \filter_faq\lib::get_pathid($path);
                break;
            }
        }
        if (!empty($pathid)) {
            mtrace("===> This path contains content files");
            if (file_exists("{$itempath}/data.json")) {
                $data = json_decode(file_get_contents("{$itempath}/data.json"));
                // Only store as page if "ispage" is true.
                if ($data->ispage) {
                    $this->page_record($pathid, $lang, $data->version);
                }
            }
            if (file_exists("{$itempath}/toc.json")) {
                // Currently not used, but later on used to build a TOC
            }
            if (!file_exists("{$itempath}/data.json") && file_exists("{$itempath}/version")) {
                // Deprecated, but for now we accept it if data.json is missing. Always treat as it has "ispage=true".
                $version = file_get_contents("{$itempath}/version");
                $this->page_record($pathid, $lang, $version);
            }
        }

        // Recursively scan subdirectories
        $contents = array_diff(scandir($itempath), [ '..', '.' ]);
        foreach ($contents as $content) {
            // Ignore hidden files/directories
            if (mb_substr($content, 0, 1) == '.') {
                continue;
            }
            if (is_dir("{$itempath}/{$content}")) {
                $subpath = $path == '' ? $content : "{$path}/{$content}";
                $this->scan_path($lang, $subpath);
            }
        }
    }

    /**
     * Ensure a page record within a language is created or updated.
     * @param int $pathid
     * @param string $lang
     * @param string $version
     * @return void
     * @throws \dml_exception
     */
    private function page_record(int $pathid, string $lang, string $version): void {
        global $DB;
        // Mark this pathid as process for this language.
        $this->foundpathids[] = $pathid;
        // Allow maximum length of 10 characters
        $version = mb_substr(trim($version), 0, 10);
        $version = mb_check_encoding($version, 'UTF-8') ? $version : mb_convert_encoding($version, 'UTF-8', 'auto');
        $params = [ 'pathid' => $pathid, 'lang' => $lang ];
        $rec = $DB->get_record('filter_faq_pages', $params);
        if ($rec) {
            // Only update if version has changed.
            if ($rec->version != $version) {
                $rec->version = $version;
                $rec->timemodified = time();
                $DB->update_record('filter_faq_pages', $rec);
                mtrace("===> Page record for $pathid | $lang with version $version updated");
            } else {
                mtrace("===> Page record for $pathid | $lang with version $version has not changed");
            }
        } else {
            $rec = (object) $params;
            $rec->version = $version;
            $rec->timemodified = time();
            $DB->insert_record('filter_faq_pages', $rec);
            mtrace("===> Page record for $pathid | $lang with version $version inserted");
        }
    }
}
