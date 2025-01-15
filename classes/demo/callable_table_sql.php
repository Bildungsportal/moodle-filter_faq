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
 * @copyright  2024 Austrian Federal Ministry of Education
 * @author     GTN solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_faq\demo;

use filter_faq\callable_table_sql as base_table_sql;

defined('MOODLE_INTERNAL') || die;

class callable_table_sql extends base_table_sql {
    protected function define_table_configs() {
        $sql = "SELECT * FROM {local_table_sql_demo}";
        $this->set_sql_query($sql, []);
        $this->set_sql_table('local_table_sql_demo');

        // Define headers and columns.
        $cols = [
            'id' => 'id',
            'groupid' => 'groupid',
            'label1' => 'label1',
            'label2' => 'label2',
        ];
        $this->set_table_columns($cols);
        $this->sortable(true, 'id', SORT_ASC);
        $this->is_downloadable(true);
    }
}
