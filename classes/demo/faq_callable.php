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

defined('MOODLE_INTERNAL') || die;

/**
 * This is a demo class that shows how the keyword {faq:call:xxx} can be used.
 * You can place the following code in any page that uses the filter:
 *     {faq:call:\filter_faq\demo\faq_callable~content of var 1~content of var 2~content of var 3}
 */
class faq_callable implements \filter_faq\callable_class {
    public function __construct(private $var1, private $var2, private $var3 = '') {
    }

    public function out() {
        return "this is the faq callable demo showing you<br />- {$this->var1}<br />- {$this->var2}<br />- {$this->var3}";
    }

    public static function filter_faq_call(array $params): string {
        $obj = new static(...$params);
        return $obj->out();
    }
}
