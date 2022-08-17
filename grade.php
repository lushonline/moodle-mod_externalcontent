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
 * Private externalcontent module utility functions
 *
 * @package     mod_externalcontent
 * @copyright   2019-2022 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_externalcontent\instance;

require(__DIR__.'/../../config.php');

// Get the external conmtent instance from either the cmid (id), or the instanceid (p).
$id = optional_param('id', 0, PARAM_INT);
$instance = instance::get_from_cmid($id);

if (!$instance) {
    throw new moodle_exception('invalidcoursemodule', 'error');
}

$cm = $instance->get_cm();
$course = $instance->get_course();

require_course_login($course, true, $cm);
redirect(new moodle_url('/mod/externalcontent/view.php', array('id' => $cm->id)));
