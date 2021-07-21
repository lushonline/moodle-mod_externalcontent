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
 * The mod_externalcontent module custom completion class.
 *
 * @package     mod_externalcontent
 * @copyright   2019-2021 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_externalcontent\completion;

defined('MOODLE_INTERNAL') || die();

if (!class_exists('\core_completion\activity_custom_completion')) {
    // New API does not exist in this site, so do nothing.
    return;
}

/**
 * The mod_externalcontent module custom completion.
 *
 * @package     mod_externalcontent
 * @copyright   2019-2021 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class custom_completion extends \core_completion\activity_custom_completion {
    /**
     * Fetches the completion state for a given completion rule.
     *
     * @param string $rule The completion rule.
     * @return int The completion state.
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);
        // Get external content details.
        $externalcontent = $DB->get_record('externalcontent', array('id' => $this->cm->instance), '*', MUST_EXIST);

        switch ($rule) {
            case 'completionexternally':
                $params = array('userid' => $this->userid, 'externalcontentid' => $externalcontent->id, 'completed' => 1);
                $completed = $DB->record_exists('externalcontent_track', $params);

                $status = $completed ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
                break;

            default :
                $status = COMPLETION_INCOMPLETE;
                break;
        }

        return $status;
    }

    /**
     * Fetch the list of custom completion rules that this module defines.
     *
     * @return array
     */
    public static function get_defined_custom_rules(): array {
        return ['completionexternally'];
    }

    /**
     * Returns an associative array of the descriptions of custom completion rules.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        return ['completionexternally' => get_string('eventcompletedexternally', 'externalcontent')];
    }

    /**
     * Returns an array of all completion rules, in the order they should be displayed to users.
     *
     * @return array
     */
    public function get_sort_order(): array {
        return [
            'completionview',
            'completionexternally',
        ];
    }
}
