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

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(
        new \admin_setting_configtext(
            "filter_faq/urlshortener",
            get_string("urlshortener", 'filter_faq'),
            get_string("urlshortener:description", 'filter_faq'),
            '',
            PARAM_URL
        )
    );

    $settings->add(
        new \admin_setting_configtext(
            "filter_faq/redmine_secret",
            'Redmine secret',
            '',
            '',
            PARAM_TEXT
        )
    );
}
