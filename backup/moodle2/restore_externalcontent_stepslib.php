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
 * All the steps to restore mod_externalcontent are defined here.
 *
 * @package     mod_externalcontent
 * @copyright   2019-2022 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// For more information about the backup and restore process, please visit:
// https://docs.moodle.org/dev/Backup_2.0_for_developers.
// https://docs.moodle.org/dev/Restore_2.0_for_developers.

/**
 * Defines the structure step to restore one mod_externalcontent activity.
 * @package     mod_externalcontent
 * @copyright   2019-2022 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_externalcontent_activity_structure_step extends restore_activity_structure_step {

    /**
     * Defines the structure to be restored.
     *
     * @return restore_path_element[].
     */
    protected function define_structure() {
        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('externalcontent', '/activity/externalcontent');
        if ($userinfo) {
            $paths[] = new restore_path_element('externalcontent_track',
                            '/activity/externalcontent/externalcontent_tracks/externalcontent_track');
        }
        return $this->prepare_activity_structure($paths);
    }

    /**
     * process_externalcontent
     *
     * @param  object $data
     * @return void
     */
    protected function process_externalcontent($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        // Insert the record.
        $newitemid = $DB->insert_record('externalcontent', $data);

        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * process_externalcontent_track
     *
     * @param  object $data
     * @return void
     */
    protected function process_externalcontent_track($data) {
        global $DB;

        $data = (object)$data;

        $data->externalcontentid = $this->get_new_parentid('externalcontent');
        $data->userid = $this->get_mappingid('user', $data->userid);

        $DB->insert_record('externalcontent_track', $data);
        // No need to save this mapping as far as nothing depend on it.
        // (child paths, file areas nor links decoder).
    }

    /**
     * after_execute
     *
     * @return void
     */
    protected function after_execute() {
        $this->add_related_files('mod_externalcontent', 'intro', null);
        return;
    }
}
