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
 * Version of filter_eduportal.
 *
 * @package    filter_faq
 * @copyright  2023 Austrian Federal Ministry of Education
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$l = optional_param('lang', current_language(), PARAM_TEXT);
$t = optional_param('t', '', PARAM_TEXT);
$untestedpath = optional_param('path', '', PARAM_TEXT);
if (!empty($untestedpath)) {
    $p = \filter_faq\lib::get_pathid($untestedpath);
    $url = new \moodle_url('/filter/faq/page.php', ['lang' => $l, 'p' => $p, 't' => $t]);
    redirect($url);
}

$p = required_param('p', PARAM_INT);
$pathrecord = $DB->get_record('filter_faq', ['id' => $p], '*', MUST_EXIST);
$PAGE->set_url('/filter/faq/page.php', ['lang' => $l, 'p' => $p, 't' => $t]);
$PAGE->set_context(context_system::instance());

$langs = [$l];
$secondarylang = \filter_faq\lib::default_lang();
if ($l != $secondarylang) {
    $langs[] = $secondarylang;
}

$params = (object)[
    'longdescription' => \filter_faq\lib::get_content($p, 'longdescription', $langs),
    'longtitle' => \filter_faq\lib::get_content($p, 'longtitle', $langs),
    'permalink' => \filter_faq\lib::permalink($pathrecord->path),
    'shortdescription' => \filter_faq\lib::get_content($p, 'shortdescription', $langs),
    'shorttitle' => \filter_faq\lib::get_content($p, 'shorttitle', $langs),
];

$PAGE->set_title($params->shorttitle);
$PAGE->set_heading($params->longtitle);
$PAGE->requires->css('/filter/faq/style/print.css');

$options = [
    'newlines' => false,
    'noclean' => true,
    'trusted' => true,
];
if (empty($t)) {
    $html = $OUTPUT->render_from_template('filter_faq/page', $params);
} else {
    $html = "{faq:{$pathrecord->path}:$t}";
}

if (empty($t)) {
    echo $OUTPUT->header();
}
if ($t != 'linkurl') {
    echo format_text($html, FORMAT_HTML, $options);
} else {
    echo $html;
}
if (empty($t)) {
    echo $OUTPUT->footer();
}
