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

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/mod/externalcontent/lib.php");
require_once("$CFG->libdir/resourcelib.php");

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
