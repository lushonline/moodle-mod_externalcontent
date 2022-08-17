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
 * Display information about all the mod_externalcontent modules in the requested course.
 *
 * @package     mod_externalcontent
 * @copyright   2019-2022 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\notification;
use mod_externalcontent\instance;
use mod_externalcontent\output\index;
use mod_externalcontent\plugin;

require(__DIR__.'/../../config.php');
global $PAGE, $OUTPUT;

$id = required_param('id', PARAM_INT);
$course = get_course($id);
require_course_login($course);

$coursecontext = context_course::instance($course->id);

$event = \mod_externalcontent\event\course_module_instance_list_viewed::create(array(
    'context' => $coursecontext
));
$event->add_record_snapshot('course', $course);
$event->trigger();

$PAGE->set_url('/mod/externalcontent/index.php', array('id' => $id));
$PAGE->set_title(get_string('modulename', plugin::COMPONENT));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_cacheable(false);

$instances = instance::get_all_instances_in_course($course->id);
if (empty($instances)) {
    notification::add(
        get_string('thereareno', 'moodle', get_string('modulenameplural', plugin::COMPONENT)),
        notification::ERROR
    );
    redirect(new moodle_url('/course/view.php', ['id' => $course->id]));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', plugin::COMPONENT));
$renderer = $PAGE->get_renderer(plugin::COMPONENT);
echo $renderer->render(new index($course, $instances));
echo $OUTPUT->footer();
