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
 * Backup steps for mod_externalcontent are defined here.
 *
 * @package     mod_externalcontent
 * @category    backup
 * @copyright   2019-2021 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// For more information about the backup and restore process, please visit:
// https://docs.moodle.org/dev/Backup_2.0_for_developers.
// https://docs.moodle.org/dev/Restore_2.0_for_developers.

/**
 * Define the complete structure for backup, with file and id annotations.
 * @package     mod_externalcontent
 * @copyright   2019-2021 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_externalcontent_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the structure of the resulting xml file.
     *
     * @return backup_nested_element The structure wrapped by the common 'activity' element.
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        // The activity item.
        $externalcontent = new backup_nested_element('externalcontent',
                            array(
                                'id'
                            ),
                            array(
                                'name',
                                'intro',
                                'introformat',
                                'content',
                                'contentformat',
                                'displayoptions',
                                'completionexternally',
                                'scoredexternally',
                                'timemodified'
                            )
                        );

        $tracks = new backup_nested_element('externalcontent_tracks');

        // The tracking for the activity item.
        $track = new backup_nested_element('externalcontent_track',
                                    array(
                                        'id'
                                    ),
                                    array(
                                        'userid',
                                        'completed',
                                        'score',
                                        'timemodified'
                                    )
                                );

        // Build the tree with these elements with $root as the root of the backup tree.
        $tracks->add_child($track);
        $externalcontent->add_child($tracks);

        // Define the source tables for the elements.
        $externalcontent->set_source_table('externalcontent', array('id' => backup::VAR_ACTIVITYID));

        // Only add tracks if we are including user info.
        if ($userinfo) {
            $track->set_source_table('externalcontent_track', array('externalcontentid' => backup::VAR_PARENTID));
        }

        // Define id annotations.
        $track->annotate_ids('user', 'userid');

        // Define file annotations.
        $externalcontent->annotate_files('mod_externalcontent', 'intro', null);
        $externalcontent->annotate_files('mod_externalcontent', 'content', null);

        return $this->prepare_activity_structure($externalcontent);
    }
}
