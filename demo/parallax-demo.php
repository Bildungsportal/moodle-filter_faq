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

// This script manages which interfaces an app is permitted to use,
// and if the interfaces is activated by the partner.

/**
 * @package    filter_faq
 * @copyright  2025 Austrian Federal Ministry of Education
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_admin();

$PAGE->set_context(\context_system::instance());
$PAGE->set_url('/filter/faq/demo/parallax-demo.php');
$PAGE->set_heading("Demo for parallax effect");
$PAGE->set_title("Demo for parallax effect");

echo $OUTPUT->header();
$options = [
    'noclean' => true,
    'filter' => true,
    'context' => $PAGE->context,
    'para' => false,
    'newlines' => false,
    'allowid' => false,
    'blanktarget' => false,
];
echo format_text(file_get_contents("{$CFG->dirroot}/filter/faq/demo/parallax-demo.html"), FORMAT_HTML, $options);
echo $OUTPUT->footer();
