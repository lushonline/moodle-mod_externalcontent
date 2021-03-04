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
 * Links and settings
 *
 * Class containing a set of helpers, based on admin\tool\uploadcourse by 2013 Frédéric Massart.
 *
 * @package     mod_externalcontent
 * @copyright   2019-2021 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->dirroot.'/mod/externalcontent/lib.php');
require_once($CFG->dirroot.'/mod/externalcontent/locallib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/enrollib.php');
require_once($CFG->dirroot.'/course/lib.php');
require($CFG->dirroot.'/mod/externalcontent/lrs/vendor/autoload.php');
use TinCan\Statement;
use TinCan\Agent;

/**
 * Class containing a set of helpers for using xapi data.
 *
 * @package   mod_externalcontent
 * @copyright 2019-2021 LushOnline
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class xapihelper {

    /**
     * Process an xAPI statement to create payload for sending to externalcontent_update_completion_state
     *
     * @param String $xapiversion The version of the xAPI sepcification
     * @param Statement $statement The Statement object
     * @return object payload of parsed information
     */
    public static function processstatement(String $xapiversion, Statement $statement) {
        $cfg = get_config('externalcontent');
        $completionverbs = array_map('trim', explode(',', $cfg->xapicompletionverbs));

        $payload = new \stdClass;
        $payload->version = $xapiversion;
        $payload->statementId = $statement->getId();
        $payload->verb = $statement->getVerb()->getId();
        $payload->object = $statement->getObject()->getId();

        $actor = $statement->getActor();
        $agent = $actor instanceof Agent ? $actor->getAccount() : null;
        $payload->actor = $agent != null ? $agent->getName() : null;

        $result = $statement->getResult();
        $payload->completion = $result != null ? $result->getCompletion() : null;

        $payload->completed = false;
        if (in_array($payload->verb, $completionverbs)) {
            $payload->completed = true;
        }

        $score = $result != null ? $result->getScore() : null;
        $payload->score = $score != null ? $score->getRaw() : null;
        $payload->user = self::get_user_by_username($payload->actor);
        $payload->cm = null;
        $payload->course = null;
        $payload->updateresponse = null;

        // Retrieve the course module that has the specified object id as its idnumber.
        // Moodle prevents duplicate idnumbers.
        if ($cm = self::get_cm_by_idnumber($payload->object)) {
            $payload->cm = $cm;
            $payload->course = self::get_course_by_id($cm->course);
            $payload->updateresponse = self::mark_completed($payload);
        }

        return $payload;
    }


    /**
     * Retrieve a user by username.
     *
     * @return object|null role or null.
     */
    public static function get_student_role() {
        global $DB;

        if ($role = $DB->get_record('role', array('shortname' => 'student'))) {
            return $role;
        } else {
            return null;
        }
    }

    /**
     * Retrieve a user by username.
     *
     * @param string $username Moodle username.
     * @return object|null user or null.
     */
    public static function get_user_by_username($username) {
        global $DB;

        $params = array('username' => $username);

        if ($user = $DB->get_record('user', $params, 'id,username')) {
            return $user;
        } else {
            return null;
        }
    }

    /**
     * Retrieve a course by its id.
     *
     * @param string $courseid course id.
     * @return object|null course or null.
     */
    public static function get_course_by_id($courseid) {
        global $DB;

        $params = array('id' => $courseid);
        if ($course = $DB->get_record('course', $params)) {
            return $course;
        } else {
            return null;
        }
    }

    /**
     * Retrieve the course module with idnumber
     *
     * @param string $idnumber Unique idnumber
     * @return object|null coursemodules or null
     */
    public static function get_cm_by_idnumber($idnumber) {
        global $DB;

        $params = array('courseidnumber' => $idnumber, 'modulename' => 'externalcontent');

        $sql = "SELECT cm.*
                FROM {course_modules} cm
                JOIN {modules} md ON md.id = cm.module
                WHERE cm.idnumber = :courseidnumber AND md.name = :modulename";

        if ($cm = $DB->get_record_sql($sql, $params)) {
            return $cm;
        } else {
            return null;
        }
    }

    /**
     * Update externalcontent activity viewed and completion if needed
     *
     * This will show a developer debug warning when run in Moodle UI because
     * of the function set_module_viewed in completionlib.php details copied below:
     *
     * Note that this function must be called before you print the externalcontent header because
     * it is possible that the navigation block may depend on it. If you call it after
     * printing the header, it shows a developer debug warning.
     *
     * @param object $record Output from processstatement
     * @return object $response contains details of processing
     */
    public static function mark_completed($record) {
        $response = new \stdClass();
        $response->status = false;
        $response->completionupdated = false;
        $response->scoreupdated = false;
        $response->viewedupdated = false;
        $response->message = null;

        if ($record->course) {
            if ($record->user) {
                if ($record->cm) {
                    // Student role to use when enroling user.
                    $studentrole = self::get_student_role();

                    // Execute real Moodle enrolment for user.
                    enrol_try_internal_enrol($record->course->id, $record->user->id, $studentrole->id);

                    $response = externalcontent_update_completion_state($record->course, $record->cm, null, $record->user->id,
                                                                              $record->score, $record->completed, 1, 1);
                    $response->lrserrorcode = EXTERNALCONTENT_LRS_NO_ERROR;
                } else {
                    $response->message = "Course module does not exist.";
                    $response->lrserrorcode = EXTERNALCONTENT_LRS_COURSEMODULE_NOT_FOUND;
                }
            } else {
                $response->message = "User does not exist.";
                $response->lrserrorcode = EXTERNALCONTENT_LRS_USER_NOT_FOUND;
            }
        } else {
            $response->message = "Course does not exist.";
            $response->lrserrorcode = EXTERNALCONTENT_LRS_COURSE_NOT_FOUND;
        }
        return $response;
    }
}