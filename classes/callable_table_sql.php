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
 * @copyright  2024 Austrian Federal Ministry of Education
 * @author     GTN solutions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_faq;

use local_table_sql\table_sql;

defined('MOODLE_INTERNAL') || die;

class callable_table_sql extends table_sql implements callable_class {
    /**
     * @param array $params params for the xhr requests, this is also used for the uniqueid of this table
     */
    public function __construct(array $params = []) {
        $this->filter_faq_set_params($params);

        parent::__construct($params);
    }

    /**
     * for child classes this should be done with the constructor $params argument
     */
    private function filter_faq_set_params(array $params): void {
        $this->set_xhr_url(\filter_faq\lib::get_call_url($this, $params));
    }

    public static function filter_faq_call(array $params): string {
        $obj = new static($params);
        ob_start();
        $obj->out();
        return ob_get_clean();
    }
}
