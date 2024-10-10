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
 * String file for filter_faq.
 *
 * @package    filter_faq
 * @copyright  2023 Austrian Federal Ministry of Education
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['cachdef_pages'] = 'Cache for paths and ids of pages';
$string['exception:file_not_found'] = 'File {$a->file} does not exist';
$string['exception:file_outside_bounds'] = 'The requested file is outside the permitted boundaries!';
$string['exception:no_such_path'] = 'No path with pathid {$a->pathid} found';
$string['file_not_found'] = 'Sorry, the requested file {$a->filename} was not found for page {$a->shorttitle}';
$string['filtername'] = 'Frequently asked questions';
$string['no_faq_found'] = 'Sorry, the page {$a->path} does not exist.';
$string['no_faq_found_for'] = 'Sorry, the page {$a->path} does not contain {$a->content}.';
$string['open_help_page'] = 'Open the help page';
$string['permalink'] = 'Permanent link to this page';
$string['pluginname'] = 'FAQ Filter';
$string['pluginname:settings'] = 'FAQ Filter - settings';
$string['print'] = 'Print';
$string['privacy:metadata'] = 'This plugin does not store any data at all.';
$string['stringlib:invalid_path'] = 'Invalid stringlib path "{$a->match}"';
$string['urlshortener'] = 'URL Shortener';
$string['urlshortener:description'] = 'Please set the URL if you are using an URL-shortener. All permalinks will use that urlshortener.';
$string['more_help'] = 'More';