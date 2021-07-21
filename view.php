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
 * Prints an instance of mod_externalcontent.
 *
 * @package     mod_externalcontent
 * @copyright   2019-2021 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/mod/externalcontent/lib.php');
require_once($CFG->dirroot.'/mod/externalcontent/locallib.php');
require_once($CFG->libdir.'/completionlib.php');

// Course_module ID, or.
$id = optional_param('id', 0, PARAM_INT);

// Module instance id.
$p  = optional_param('p', 0, PARAM_INT);

if ($p) {
    if (!$externalcontent = $DB->get_record('externalcontent', array('id' => $p))) {
        throw new moodle_exception('invalidaccessparameter', 'error');
    }
    $cm = get_coursemodule_from_instance('externalcontent', $externalcontent->id,
                                        $externalcontent->course, false, MUST_EXIST);

} else {
    if (!$cm = get_coursemodule_from_id('externalcontent', $id)) {
        throw new moodle_exception('invalidcoursemodule', 'error');
    }
    $externalcontent = $DB->get_record('externalcontent', array('id' => $cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/externalcontent:view', $context);

// Completion and trigger events.
externalcontent_view($externalcontent, $course, $cm, $context);

$PAGE->set_url('/mod/externalcontent/view.php', array('id' => $cm->id));
$options = empty($externalcontent->displayoptions) ? array() : unserialize($externalcontent->displayoptions);

$PAGE->set_title($course->shortname.': '.$externalcontent->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($externalcontent);

echo $OUTPUT->header();
if (!isset($options['printheading']) || !empty($options['printheading'])) {
    echo $OUTPUT->heading(format_string($externalcontent->name), 2);
}

if (!empty($options['printintro'])) {
    if (trim(strip_tags($externalcontent->intro))) {
        echo $OUTPUT->box_start('mod_introbox', 'externalcontentintro');
        echo format_module_intro('externalcontent', $externalcontent, $cm->id);
        echo $OUTPUT->box_end();
    }
}

$formatoptions = new stdClass;
$formatoptions->noclean = true;
$formatoptions->overflowdiv = true;
$formatoptions->context = $context;

$content = $externalcontent->content;
$content = format_text( $content, $externalcontent->contentformat, $formatoptions);
echo $OUTPUT->box($content, "generalbox center clearfix");

if (!isset($options['printlastmodified']) || !empty($options['printlastmodified'])) {
    $strlastmodified = get_string("lastmodified");
    echo html_writer::div("$strlastmodified: " . userdate($externalcontent->timemodified), 'modified');
}

echo $OUTPUT->footer();

