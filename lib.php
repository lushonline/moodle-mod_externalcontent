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
 * Library of interface functions and constants.
 *
 * @package     mod_externalcontent
 * @copyright   2019-2020 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function externalcontent_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
        {
            return MOD_ARCHETYPE_RESOURCE;
        }
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        {
            return true;
        }
        case FEATURE_COMPLETION_HAS_RULES:
        {
            return true;
        }
        case FEATURE_GRADE_HAS_GRADE:
        {
            return false;
        }
        case FEATURE_GRADE_OUTCOMES:
        {
            return false;
        }
        case FEATURE_MOD_INTRO:
        {
            return true;
        }
        case FEATURE_BACKUP_MOODLE2:
        {
            return true;
        }
        case FEATURE_SHOW_DESCRIPTION:
        {
            return true;
        }
        default:
        {
            return null;
        }
    }
}

/**
 * Saves a new instance of the mod_externalcontent into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_externalcontent_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function externalcontent_add_instance($moduleinstance, $mform = null) {
    global $DB;

    $cmid = $moduleinstance->coursemodule;

    $moduleinstance->timecreated = time();
    $moduleinstance->timemodified = time();

    $displayoptions = array();
    $displayoptions['printheading'] = $moduleinstance->printheading;
    $displayoptions['printintro']   = $moduleinstance->printintro;
    $displayoptions['printlastmodified'] = $moduleinstance->printlastmodified;
    $moduleinstance->displayoptions = serialize($displayoptions);

    if ($mform) {
        $moduleinstance->content       = $moduleinstance->externalcontent['text'];
        $moduleinstance->contentformat = $moduleinstance->externalcontent['format'];
    }

    $moduleinstance->id = $DB->insert_record('externalcontent', $moduleinstance);

    // We need to use context now, so we need to make sure all needed info is already in db.
    $DB->set_field('course_modules', 'instance', $moduleinstance->id, array('id' => $cmid));
    $context = context_module::instance($cmid);

    if ($mform and !empty($data->externalcontent['itemid'])) {
        $draftitemid = $data->externalcontent['itemid'];
        $moduleinstance->content = file_save_draft_area_files($draftitemid, $context->id, 'mod_externalcontent',
                        'content', 0, externalcontent_get_editor_options($context), $moduleinstance->content);
        $DB->update_record('externalcontent', $moduleinstance);
    }

    externalcontent_grade_item_update($moduleinstance);

    $completiontimeexp = !empty($moduleinstance->completionexpected) ? $moduleinstance->completionexpected : null;
    \core_completion\api::update_completion_date_event($cmid, 'externalcontent',
                                                        $moduleinstance->id, $completiontimeexp);

    return $moduleinstance->id;
}

/**
 * Updates an instance of the mod_externalcontent in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @return bool True if successful, false otherwise.
 */
function externalcontent_update_instance($moduleinstance) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    $cmid = $moduleinstance->coursemodule;
    $draftitemid = $moduleinstance->externalcontent['itemid'];

    $displayoptions = array();
    $displayoptions['printheading'] = $moduleinstance->printheading;
    $displayoptions['printintro']   = $moduleinstance->printintro;
    $displayoptions['printlastmodified'] = $moduleinstance->printlastmodified;
    $moduleinstance->displayoptions = serialize($displayoptions);

    $moduleinstance->content       = $moduleinstance->externalcontent['text'];
    $moduleinstance->contentformat = $moduleinstance->externalcontent['format'];

    $DB->update_record('externalcontent', $moduleinstance);

    $context = context_module::instance($cmid);
    if ($draftitemid) {
        $moduleinstance->content = file_save_draft_area_files($draftitemid, $context->id, 'mod_externalcontent',
                                    'content', 0, externalcontent_get_editor_options($context), $moduleinstance->content);
        $DB->update_record('externalcontent', $moduleinstance);
    }

    externalcontent_grade_item_update($moduleinstance);
    externalcontent_update_grades($moduleinstance);

    $completiontimeexp = !empty($moduleinstance->completionexpected) ? $moduleinstance->completionexpected : null;
    \core_completion\api::update_completion_date_event($cmid, 'externalcontent', $moduleinstance->id, $completiontimeexp);

    return true;
}

/**
 * Removes an instance of the mod_externalcontent from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function externalcontent_delete_instance($id) {
    global $DB;

    $externalcontent = $DB->get_record('externalcontent', array('id' => $id));
    if (!$externalcontent) {
        return false;
    }

    $result = true;

    // Delete any dependent records.
    if (! $DB->delete_records('externalcontent_track', array('externalcontentid' => $externalcontent->id))) {
        $result = false;
    }

    // Note: all context files are deleted automatically.

    $cm = get_coursemodule_from_instance('externalcontent', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'externalcontent', $externalcontent->id, null);

    // We must delete the module record after we delete the grade item.
    if (! $DB->delete_records('externalcontent', array('id' => $externalcontent->id))) {
        $result = false;
    }

    return $result;
}

/**
 * Mark the activity viewed and trigger the course_module_viewed event.
 *
 * @param  stdClass $externalcontent       externalcontent object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 3.0
 */
function externalcontent_view($externalcontent, $course, $cm, $context) {
    externalcontent_viewed($course, $cm, $context);
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid User id to use. Set to 0 for current user (default)
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_externalcontent_core_calendar_provide_event_action(calendar_event $event,
                                                      \core_calendar\action_factory $factory, $userid = 0) {
    global $USER;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    $cm = get_fast_modinfo($event->courseid, $userid)->instances['externalcontent'][$event->instance];

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false, $userid);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/externalcontent/view.php', ['id' => $cm->id]),
        1,
        true
    );
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 *
 * See get_array_of_activities() in course/lib.php
 *
 * @param stdClass $coursemodule
 * @return cached_cm_info Info to customise main page display
 */
function externalcontent_get_coursemodule_info($coursemodule) {
    global $CFG, $DB;
    require_once("$CFG->libdir/resourcelib.php");

    if (!$externalcontent = $DB->get_record('externalcontent', array('id' => $coursemodule->instance),
            'id, name, intro, introformat')) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $externalcontent->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $info->content = format_module_intro('externalcontent', $externalcontent, $coursemodule->id, false);
    }
    return $info;
}

/**
 * Obtains the automatic completion state for this externalcontent based on any conditions
 * in externalcontent settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not. (If no conditions, then return
 *   value depends on comparison type)
 */
function externalcontent_get_completion_state($course, $cm, $userid, $type) {
    global $DB;
    // Get external content details.
    $externalcontent = $DB->get_record('externalcontent', array('id' => $cm->instance), '*', MUST_EXIST);

    if (!$externalcontent->completionexternally) {
        // Completion option is not enabled so just return $type.
        return $type;
    } else {
        $params = array('userid' => $userid, 'externalcontentid' => $externalcontent->id, 'completed' => 1);
        $completed = $DB->record_exists('externalcontent_track', $params);

        return $completed;
    }
}

/**
 * Add an entry to the tracking, or updates the existing one
 *
 * @param  int $externalcontentid The id of the externalcontent
 * @param  int $userid Set to 0 for current user (default=0)
 * @param  int $completed Set to 1 for completed (default=1)
 * @param  int $score Set to score (default=NULL)
 * @param  int $usebestscore Set to 1 to update score only if it is higher than current score (default=0)
 * @return bool True if succesful, false if not.
 */
function externalcontent_add_track($externalcontentid, $userid = 0, $completed = 1, $score = null, $usebestscore=0) {
    global $DB, $USER;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    $record = new \stdClass();
    $record->externalcontentid = $externalcontentid;
    $record->userid = $userid;
    $record->completed = $completed;
    $record->score = $score;
    $record->timemodified = time();

    // Get external content track.
    if ($track = $DB->get_record('externalcontent_track',
                array('externalcontentid' => $externalcontentid, 'userid' => $userid),
                '*',
                IGNORE_MISSING)) {
        $track->completed = $completed;

        // Only update score if new score > old score
        $bestscore = $track->score > $score ? $track->score : $score;

        $track->score = $usebestscore ? $bestscore : $score;
        return $DB->update_record('externalcontent_track', $track);
    } else {
        return $DB->insert_record('externalcontent_track', $record, false);
    }
}

/**
 * Delete entries for the tracking
 *
 * @param  int $externalcontentid The id of the externalcontent
 * @param  int $userid Set to 0 for current user (default)
 * @return bool True if succesful, false if not.
 */
function externalcontent_delete_tracks($externalcontentid, $userid = 0) {
    global $DB, $USER;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    return $DB->delete_records('externalcontent_track', array('userid' => $userid, 'externalcontentid' => $externalcontentid));
}

/**
 * Simple quick function to return true/false if this user has tracks
 *
 * @param  int $externalcontentid The id of the externalcontent
 * @param  int $userid Set to 0 for current user (default)
 * @return boolean (false if there are no tracks)
 */
function externalcontent_has_tracks($externalcontentid, $userid = 0) {
    global $DB, $USER;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    return $DB->record_exists('externalcontent_track', array('userid' => $userid, 'externalcontentid' => $externalcontentid));
}

/**
 * Retrieve the track
 *
 * @param  int $externalcontentid The id of the externalcontent
 * @param  int $userid Set to 0 for current user (default)
 * @return boolean (false if there are no tracks)
 */
function externalcontent_get_tracks($externalcontentid, $userid = 0) {
    global $DB, $USER;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    return $DB->get_record('externalcontent_track', array('userid' => $userid, 'externalcontentid' => $externalcontentid));
}

/**
 * Updates the external completion viewed and completion state and score, using external data
 *
 * @param object $course
 * @param object $cm Course-module
 * @param object $context Set to null to get context_module (default)
 * @param int $userid Set to 0 for current user (default)
 * @param int $score Set to score (default=NULL)
 * @param int $completed Set to completed status (default=1)
 * @param int $xapi Support anonymous updates from xapi, without user needing to be logged in (default=0)
 * @param int $usebestscore Set to 1 to update score only if it is higher than current score (default=0)
 * @return object statsus=bool if change processed. completionupdated=bool. scoreupdated=bool. message=A response message
 */
function externalcontent_update_completion_state($course, $cm, $context = null, $userid = 0, $score = null,
                                                 $completed = 1, $xapi=0, $usebestscore=0) {
    global $DB, $USER;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    if (empty($context)) {
        $context = context_module::instance($cm->id);
    }

    $response = new \stdClass();
    $response->status = false;
    $response->completionupdated = false;
    $response->scoreupdated = false;
    $response->viewedupdated = false;
    $response->message = null;

    // Get external content details.
    $externalcontent = $DB->get_record('externalcontent', array('id' => $cm->instance), '*', MUST_EXIST);

    if (!$externalcontent->completionexternally) {
        $response->message = 'External content completion state cannot be updated externally.';
        return $response;
    }

    // Add the tracking data.
    externalcontent_add_track($externalcontent->id, $userid, $completed, $score, $usebestscore);

    // Update completion state.
    $completion = new completion_info($course);
    if ((isloggedin() || $xapi) && !isguestuser() && $completion->is_enabled($cm)) {
        $currentstate = $completion->get_data($cm, false, $userid, null);

        if ($currentstate->viewed == COMPLETION_VIEWED) {
            $response->viewedupdated = false;
        } else {
            externalcontent_viewed($course, $cm, null, $userid);
            $response->message .= ' External content viewed status set to COMPLETION_VIEWED.';
            $response->viewedupdated = true;
        }

        if ($completed) {
            if ($currentstate->completionstate == COMPLETION_COMPLETE) {
                $response->completionupdated = false;
            } else {
                $params = array(
                    'context' => $context,
                    'objectid' => $externalcontent->id,
                    'userid' => $userid,
                );

                $event = \mod_externalcontent\event\course_module_completedexternally::create($params);
                $event->add_record_snapshot('course_modules', $cm);
                $event->add_record_snapshot('course', $course);
                $event->add_record_snapshot('externalcontent', $externalcontent);
                $event->trigger();

                $completion->update_state($cm, COMPLETION_COMPLETE, $userid);
                $response->message .= ' External content completion status set to COMPLETION_COMPLETE.';
                $response->completionupdated = true;
            }
        }

        if ($score != null && $score > 0) {
                $params = array(
                    'context' => $context,
                    'objectid' => $externalcontent->id,
                    'userid' => $userid,
                    'other' => $score,
                );

                $event = \mod_externalcontent\event\course_module_scoredexternally::create($params);
                $event->add_record_snapshot('course_modules', $cm);
                $event->add_record_snapshot('course', $course);
                $event->add_record_snapshot('externalcontent', $externalcontent);
                $event->trigger();

                $grades = new \stdClass();
                $grades->userid   = $userid;
                $grades->rawgrade = $score;

                $externalcontent->cmidnumber = $cm->idnumber;
                externalcontent_grade_item_update($externalcontent, $grades);
                $response->message .= ' External content grade set to '.$score.'.';
                $response->scoreupdated = true;
        }
    }

    $response->status = $response->completionupdated || $response->scoreupdated || $response->viewedupdated;
    $response->message = trim($response->message);
    return $response;
}

/**
 * Marks the external content viewed.
 *
 * @param object $course
 * @param object $cm Course-module
 * @param object $context Set to null to get context_module (default)
 * @param int $userid Set to 0 for current user (default)
 * @return bool True if succesful, false if not.
 */
function externalcontent_viewed($course, $cm, $context = null, $userid = 0) {
    global $DB, $USER;

    if (empty($userid)) {
        $userid = $USER->id;
    }

    if (empty($context)) {
        $context = context_module::instance($cm->id);
    }

    // Get external content details.
    $externalcontent = $DB->get_record('externalcontent', array('id' => $cm->instance), '*', MUST_EXIST);

    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $externalcontent->id,
        'userid' => $userid,
    );

    $event = \mod_externalcontent\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('externalcontent', $externalcontent);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm, $userid);

    return true;
}

/**
 * Return grade for given user or all users.
 *
 * @param int $externalcontent The externalcontent object
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function externalcontent_get_user_grades($externalcontent, $userid = 0) {
    global $CFG, $DB;
    require_once($CFG->dirroot.'/mod/externalcontent/locallib.php');

    $grades = array();
    if (empty($userid)) {
        $externalcontentusers = $DB->get_records_select('externalcontent_track', "externalcontentid=? GROUP BY userid",
                                            array($externalcontent->id), "", "userid,null");
        if ($externalcontentusers) {
            foreach ($externalcontentusers as $externalcontentuser) {
                $grades[$externalcontentuser->userid] = new stdClass();
                $grades[$externalcontentuser->userid]->id         = $externalcontentuser->userid;
                $grades[$externalcontentuser->userid]->userid     = $externalcontentuser->userid;
                $grades[$externalcontentuser->userid]->rawgrade = externalcontent_grade_user($externalcontent,
                                                                                             $externalcontentuser->userid);
            }
        } else {
            return false;
        }

    } else {
        $preattempt = $DB->get_records_select('externalcontent_track', "externalcontentid=? AND userid=? GROUP BY userid",
                                                array($externalcontent->id, $userid), "", "userid,null");
        if (!$preattempt) {
            return false;
        }
        $grades[$userid] = new stdClass();
        $grades[$userid]->id         = $userid;
        $grades[$userid]->userid     = $userid;
        $grades[$userid]->rawgrade = externalcontent_grade_user($externalcontent,
                                                                $userid);
    }

    return $grades;
}


/**
 * externalcontent_grade_item_update
 *
 * @param int $externalcontent The externalcontent object
 * @param object $grades optional grades information, default null
 * @return int Returns GRADE_UPDATE_OK, GRADE_UPDATE_FAILED, GRADE_UPDATE_MULTIPLE or GRADE_UPDATE_ITEM_LOCKED
 */
function externalcontent_grade_item_update($externalcontent, $grades = null) {
    global $CFG;
    if (!function_exists('grade_update')) {
        require_once($CFG->libdir.'/gradelib.php');
    }

    $params = array('itemname' => $externalcontent->name);
    $params['gradetype'] = GRADE_TYPE_VALUE;
    $params['grademax'] = 100;
    $params['grademin'] = 0;

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/externalcontent', $externalcontent->course, 'mod',
                        'externalcontent', $externalcontent->id, 0, $grades, $params);
}


/**
 * externalcontent_update_grades
 *
 * @param int $externalcontent The externalcontent object
 * @param int $userid optional user id, 0 means all users
 * @param bool $removegrade optional null the score rawgrade
 */
function externalcontent_update_grades($externalcontent, $userid = 0, $removegrade = true) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    if ($grades = externalcontent_get_user_grades($externalcontent, $userid)) {
        externalcontent_grade_item_update($externalcontent, $grades);
    } else if ($userid and $removegrade) {
        $grade = new stdClass();
        $grade->userid   = $userid;
        $grade->rawgrade = null;
        externalcontent_grade_item_update($externalcontent, $grade);
    } else {
        externalcontent_grade_item_update($externalcontent);
    }
}