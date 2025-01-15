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
 * @copyright  2023 Austrian Federal Ministry of Education
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_faq;

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use moodle_exception;

defined('MOODLE_INTERNAL') || die;

class externallib extends external_api {
    public static function getpage_parameters() {
        return new external_function_parameters([
            'p' => new external_value(PARAM_INT, 'the helptext identifier'),
            'ls' => new external_multiple_structure(new external_value(PARAM_TEXT, 'language identifier'), 'list of languages'),
            'text' => new external_value(PARAM_TEXT, 'request longdescription or shortdescription as page content.'),
            'title' => new external_value(PARAM_TEXT, 'request longtitle or shorttitle as page title.'),
        ]);
    }

    public static function getpage($p, $ls, $text, $title) {
        global $PAGE;
        $PAGE->set_context(\context_system::instance());
        $params = self::validate_parameters(self::getpage_parameters(), [
            'p' => $p, 'ls' => $ls, 'text' => $text, 'title' => $title,
        ]);
        if (!in_array($params['text'], ['long', 'short'])) {
            throw new moodle_exception('invalid_parameter');
        }
        if (!in_array($params['title'], ['long', 'short'])) {
            throw new moodle_exception('invalid_parameter');
        }
        $url = (new \moodle_url('/filter/faq/page.php', ['p' => $params['p']]))->out();
        $footer = '<a href="' . $url . '" target="_blank"><i class="fa-solid fa-up-right-from-square"></i>&nbsp;' . get_string('morehelp') . '</a>';

         $options = [
            'newlines' => false,
            'noclean' => true,
            'trusted' => true,
        ];

        return (object)[
            'body' => \format_text(\filter_faq\lib::get_content($params['p'], $params['text'] . 'description', $params['ls']), FORMAT_HTML, $options),
            'footer' => $text == 'short' ? $footer : '',
            'title' => \format_text(\filter_faq\lib::get_content($params['p'], $params['title'] . 'title', $params['ls']), FORMAT_HTML, $options),
        ];
    }

    public static function getpage_returns() {
        return new external_single_structure(
            array(
                'body' => new external_value(PARAM_RAW, 'the requested page body'),
                'footer' => new external_value(PARAM_RAW, 'the requested page footer'),
                'title' => new external_value(PARAM_TEXT, 'the requested page title'),
            )
        );
    }
}
