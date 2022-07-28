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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/externalcontent/lib.php');
require_once($CFG->libdir.'/resourcelib.php');

/** LRS Statement Processing Errors */
define('EXTERNALCONTENT_LRS_NO_ERROR', 0);
define('EXTERNALCONTENT_LRS_COURSE_NOT_FOUND', 1);
define('EXTERNALCONTENT_LRS_COURSEMODULE_NOT_FOUND', 2);
define('EXTERNALCONTENT_LRS_USER_NOT_FOUND', 3);

/**
 * This function checks if an xapi password has been generated for this site.
 *
 * If the key does not exist it generates a new one. If the openssl
 * extension is not installed or configured properly it returns a random string.
 *
 * @return void;
 */
function externalcontent_set_randomlrscredentials() {
    $username = get_config('externalcontent', 'xapidefaultusername');

    // If we already generated a valid key, no need to check.
    if (empty($username)) {
        $randomusername = substr(
                            str_shuffle(
                                str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(24 / 62))
                            ), 1, 24
                        );
        set_config('xapidefaultusername', $randomusername, 'externalcontent');
    }

    $password = get_config('externalcontent', 'xapidefaultpassword');

    // If we already generated a valid key, no need to check.
    if (empty($password)) {
        $randompassword = substr(
                            str_shuffle(
                                str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(24 / 62))
                            ), 1, 24
                        );
        set_config('xapidefaultpassword', $randompassword, 'externalcontent');
    }
}

/**
 * Helper method to get the editor options easily.
 *
 * @param string $context filearea for editor to use
 * @return mixed[] Editor-options
 */
function externalcontent_get_editor_options($context) {
    global $CFG;
    return array('subdirs' => 1,
                 'maxbytes' => $CFG->maxbytes,
                 'maxfiles' => -1,
                 'changeformat' => 1,
                 'context' => $context,
                 'noclean' => 1,
                 'trusttext' => 0);
}

/**
 * externalcontent_grade_user
 *
 * @param int $externalcontent The externalcontent object
 * @param int $userid user id
 * @return void
 */
function externalcontent_grade_user($externalcontent, $userid) {
    $score = 0;

    if ($userdata = externalcontent_get_tracks($externalcontent->id, $userid)) {
        $score = $userdata->score;
    }

    return $score;
}
