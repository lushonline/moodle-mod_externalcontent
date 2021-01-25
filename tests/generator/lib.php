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
 * @copyright   2019-2021 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * External content module data generator class
 *
 * @package     mod_externalcontent
 * @copyright   2019-2021 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_externalcontent_generator extends testing_module_generator {

    /**
     * create_instance
     *
     * @param  object $record
     * @param  array $options
     * @return object
     */
    public function create_instance($record = null, array $options = null) {
        $record = (object)(array)$record;

        if (!isset($record->content)) {
            $record->content = 'Test page content';
        }
        if (!isset($record->contentformat)) {
            $record->contentformat = FORMAT_MOODLE;
        }
        if (!isset($record->printheading)) {
            $record->printheading = 0;
        }
        if (!isset($record->printintro)) {
            $record->printintro = 0;
        }
        if (!isset($record->printlastmodified)) {
            $record->printlastmodified = 1;
        }

        return parent::create_instance($record, (array)$options);
    }
}
