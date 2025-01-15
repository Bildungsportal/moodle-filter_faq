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
 * @copyright  2022 Austrian Federal Ministry of Education
 * @author     GTN solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_admin();

$PAGE->set_context(\context_system::instance());
$PAGE->set_url('/filter/faq/demo/callable_table_sql');
$PAGE->set_heading("Demo Table for callable_table_sql");
$PAGE->set_title("Demo Table for local_table_sql");


// $table = new callable_table_sql();

echo $OUTPUT->header();


echo format_text('{faq:call:' . \filter_faq\demo\callable_table_sql::class . '}', FORMAT_HTML);
// $table->out();

echo $OUTPUT->footer();
