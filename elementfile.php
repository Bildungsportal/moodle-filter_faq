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
 * Serve files from FAQ items.
 *
 * @package    filter_faq
 * @copyright  2023 Austrian Federal Ministry of Education
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$f = required_param('f', PARAM_TEXT);
$l = optional_param('lang', current_language(), PARAM_TEXT);
$p = required_param('p', PARAM_TEXT);

$langs = [ $l ];
$secondarylang = \filter_faq\lib::default_lang();
if ($l != $secondarylang) {
    $langs[] = $secondarylang;
}

$filepath = \filter_faq\lib::get_filepath($p, $f, $langs);

if (!empty($filepath) && file_exists($filepath)) {
    $extension = pathinfo($filepath, PATHINFO_EXTENSION);
    $mimetype = mime_content_type($filepath);
    header("Content-Type: $mimetype");
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . filesize($filepath));
    ob_clean();
    flush();
    readfile($filepath);
    exit;
} else {
	$exparams = (object) [
		'filename' => $f,
		'shorttitle' => \filter_faq\lib::get_content($p, 'shorttitle', $langs),
	];
	throw new \moodle_exception('file_not_found', 'filter_faq', '', $exparams, "Path $filepath");
}