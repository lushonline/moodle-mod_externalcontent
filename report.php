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
 * @copyright   2019-2020 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->dirroot.'/mod/externalcontent/locallib.php');

$id = required_param('id', PARAM_INT);// Course Module ID, or ...

$cm = get_coursemodule_from_id('externalcontent', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$externalcontent = $DB->get_record('externalcontent', array('id' => $cm->instance), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/externalcontent:viewreports', $context);

$PAGE->set_url('/mod/externalcontent/report.php', array('id' => $cm->id));
$PAGE->set_title($course->shortname.': '.$externalcontent->name);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add( get_string('report', 'externalcontent'),
                    new moodle_url('/mod/externalcontent/report.php',
                    array('id' => $cm->id)));

$table = new html_table();
$table->tablealign = 'center';
$table->head = array(
'COMPLETED DATE',
'SCORE'
);
$table->align = array('left', 'left');
$table->wrap = array('', '');
$table->width = '80%';
$table->size = array('*', '*');

$row = array();
$row[] = 'today';
$row[] = '100%';

$table->data[] = $row;

echo $OUTPUT->box_start('generalbox boxaligncenter');
echo html_writer::table($table);
echo $OUTPUT->box_end();
echo $OUTPUT->footer();