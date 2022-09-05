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
 * @copyright   2019-2022 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use mod_externalcontent\instance;
use mod_externalcontent\plugin;
use mod_externalcontent\output\view_page;

require(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/mod/externalcontent/lib.php');

// Get the external conmtent instance from either the cmid (id), or the instanceid (p).
$id = optional_param('id', 0, PARAM_INT);
if ($id) {
    $instance = instance::get_from_cmid($id);
    if (!$instance) {
        throw new moodle_exception('invalidcoursemodule', 'error');
    }
} else {
    $p = optional_param('p', 0, PARAM_INT);
    if ($p) {
        $instance = instance::get_from_moduleid($p);
        if (!$instance) {
            throw new moodle_exception('invalidaccessparameter', 'error');
        }
    }
}

if (!$instance) {
    throw new moodle_exception('invalidaccessparameter', 'error');
}

$cm = $instance->get_cm();
$course = $instance->get_course();
$externalcontent = $instance->get_module();
$context = $instance->get_context_module();

require_course_login($course, true, $cm);
require_capability('mod/externalcontent:view', $context);

// Completion and trigger events.
externalcontent_view($externalcontent, $course, $cm, $context);

$PAGE->set_url('/mod/externalcontent/view.php', array('id' => $cm->id));
$options = empty($externalcontent->displayoptions) ? array() : unserialize($externalcontent->displayoptions);

$PAGE->set_title($course->shortname.': '.$externalcontent->name);
$PAGE->set_heading($course->fullname);

if (class_exists('core\output\activity_header')) {
    $activityheaderconfig = [];
    if (empty($options['printintro']) || !trim(strip_tags($externalcontent->intro))) {
        $activityheaderconfig['description'] = '';
    }
    if (!$PAGE->activityheader->is_title_allowed()) {
        $activityheader['title'] = "";
    }
    // Remove the activity description.
    $PAGE->activityheader->set_attrs($activityheaderconfig);

    $PAGE->add_body_class('limitedwidth');
    echo $OUTPUT->header();
} else {
    $PAGE->set_activity_record($externalcontent);
    echo $OUTPUT->header();
    $cminfo = cm_info::create($cm);
    $completiondetails = \core_completion\cm_completion_details::get_instance($cminfo, $USER->id);
    $activitydates = \core\activity_dates::get_dates_for_module($cminfo, $USER->id);
    echo $OUTPUT->activity_information($cminfo, $completiondetails, $activitydates);
}

echo $OUTPUT->box_start('', 'region-externalcontent');

if (!class_exists('core\output\activity_header')) {
    if (!empty($options['printintro'])) {
        if (trim(strip_tags($externalcontent->intro))) {
            echo $OUTPUT->box_start('mod_introbox', 'externalcontentintro');
            echo format_module_intro('externalcontent', $externalcontent, $cm->id);
            echo $OUTPUT->box_end();
        }
    }
}

$renderer = $PAGE->get_renderer('mod_externalcontent');
echo $renderer->render(new view_page($instance));

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
