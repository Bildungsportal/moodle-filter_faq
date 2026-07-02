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
 * @author     GTN solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_faq\search;

defined('MOODLE_INTERNAL') || die;

class pages extends \core_search\base {
    protected $componentname = 'filter_faq';
    protected $areaname = 'pages';
    protected static $levels = [CONTEXT_SYSTEM];

    public function get_document_recordset($modifiedfrom = 0, \context $context = null) {
        global $DB;
        return $DB->get_recordset_select('filter_faq_pages', "timemodified > ?", array($modifiedfrom));
    }
    public function get_document($record, $options = array()) {
        global $DB, $PAGE;
        // This is only to make $OUTPUT->render_from_template work without complaints!
        $PAGE->set_url('/filter/faq/pages.php');
        $context = \context_system::instance();

        // Get the page parts generated including sub items.
        $faqrecord = $DB->get_record('filter_faq', [ 'id' => $record->pathid ]);
        // Load the page content for language given by record.
        $secondary_language = \filter_faq\lib::default_lang();
        $languages = [ $record->lang ];
        if ($record->language != $secondary_language) {
            $languages[] = $secondary_language;
        }
        \filter_faq\text_filter::force_languages($languages);

        $page = (object) [];
        $parts = [ 'textlong', 'textshort', 'titlelong', 'titleshort' ];
        $textfilteroptions = [
            'newlines' => false,
            'noclean' => true,
            'trusted' => true,
        ];
        $tf = new \filter_faq\text_filter($context, []);
        foreach ($parts as $part) {
            $page->{$part} = content_to_text(
                format_text(
                    $tf->filter(
                        "{faq:{$faqrecord->path}:{$part}}",
                        $textfilteroptions),
                    FORMAT_HTML,
                    $textfilteroptions
                ),
                FORMAT_HTML
            );
        }

        // Either textlong or textshort is required.
        if (empty($page->textlong) && empty($page->textshort)) {
            throw new \moodle_exception("Page {$faqrecord->path} could not be added to searchindex", debuginfo: print_r($record, 1));
        }
        $tags = \filter_faq\lib::get_tags($record->pathid, $languages);

        $doc = \core_search\document_factory::instance($record->id, $this->componentname, $this->areaname);
        $doc->set('itemid', $record->id);
        $doc->set('title', $page->titleshort);
        $doc->set('content', $page->titlelong);

        $doc->set('contextid', $context->id);
        $doc->set('type', \core_search\manager::TYPE_TEXT);
        $doc->set('courseid', 1);
        $doc->set('modified', $record->timemodified);

        $doc->set('owneruserid', \core_search\manager::NO_OWNER_ID);

        // Extra contents associated to the document.
        $doc->set('description1', empty($page->textlong) ? $page->textshort : $page->textlong);
        if (count($tags) > 0) {
            $doc->set('description2', content_to_text(get_string('tags') . ': ' . implode(" | ", $tags), FORMAT_PLAIN));
        }

        // Not compulsory, but speeds up things when the search area includes files (see [[#Indexing files]])
        if (isset($options['lastindexedtime']) && ($options['lastindexedtime'] < $faqrecord->timecreated)) {
            // If the document was created after the last index time, it must be new.
            $doc->set_is_new(true);
        }
        return $doc;
    }

    public function check_access($id) {
        global $DB;
        $page = $DB->get_record('filter_faq_pages', [ 'id' => $id ]);
        if (!$page) {
            return \core_search\manager::ACCESS_DELETED;
        }
        // Grant access if language fits current language.
        if ($page->lang == \current_language()) {
            return \core_search\manager::ACCESS_GRANTED;
        }
        return \core_search\manager::ACCESS_DENIED;
    }

    public function get_doc_url(\core_search\document $doc) {
        global $DB;
        $page = $DB->get_record('filter_faq_pages', [ 'id' => $doc->get('itemid') ]);
        return new \moodle_url('/filter/faq/page.php', array('p' => $page->pathid));
    }

    public function get_context_url(\core_search\document $doc) {
        return $this->get_doc_url($doc);
    }
}
