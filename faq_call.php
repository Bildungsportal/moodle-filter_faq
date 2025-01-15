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

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/filter/faq/faq_call.php');

$class = required_param('class', PARAM_TEXT);
$params = required_param('params', PARAM_TEXT);
$verification = required_param('verification', PARAM_TEXT);
$paramarray = $params ? explode('~', $params) : [];

// TODO: check verification like in eduportal/redirect.php

if (!class_exists($class)) {
    throw new \moodle_exception("callable:class_missing", 'filter_faq', '', ['class' => $class]);
} elseif (!is_subclass_of($class, \filter_faq\callable_class::class)) {
    // implementiert interface callable_class nicht
    throw new \moodle_exception("callable:class_missing", 'filter_faq', '', ['class' => $class]);
}

$class::filter_faq_call($paramarray);
