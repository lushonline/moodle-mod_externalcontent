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
 * mod_externalcontent external API
 *
 * @package     mod_externalcontent
 * @copyright   2019-2023 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/externallib.php');

/**
 * mod_externalcontent functions
 *
 * @package     mod_externalcontent
 * @copyright   2019-2023 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_externalcontent_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function view_externalcontent_parameters() {
        return new external_function_parameters(
            array(
                'externalcontentid' => new external_value(PARAM_INT, 'external content instance id')
            )
        );
    }

    /**
     * Simulate the externalcontent/view.php web interface externalcontent: trigger events, completion, etc...
     *
     * @param int $externalcontentid the external content instance id
     * @return array of warnings and status result
     * @throws moodle_exception
     */
    public static function view_externalcontent($externalcontentid) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/mod/externalcontent/lib.php');

        $params = self::validate_parameters(self::view_externalcontent_parameters(),
                                            array(
                                                'externalcontentid' => $externalcontentid
                                            ));
        $warnings = array();

        // Request and permission validation.
        $externalcontent = $DB->get_record('externalcontent', array('id' => $params['externalcontentid']), '*', MUST_EXIST);
        list($course, $cm) = get_course_and_cm_from_instance($externalcontent, 'externalcontent');

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/externalcontent:view', $context);

        // Call the externalcontent/lib API.
        externalcontent_view($externalcontent, $course, $cm, $context);

        $result = array();
        $result['status'] = true;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function view_externalcontent_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success'),
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Describes the parameters for get_externalcontents_by_courses.
     *
     * @return external_function_parameters
     */
    public static function get_externalcontents_by_courses_parameters() {
        return new external_function_parameters (
            array(
                'courseids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'Course id'), 'Array of course ids', VALUE_DEFAULT, array()
                ),
            )
        );
    }

    /**
     * Returns a list of externalcontents in a provided list of courses.
     * If no list is provided all externalcontents that the user can view will be returned.
     *
     * @param array $courseids course ids
     * @return array of warnings and externalcontents
     */
    public static function get_externalcontents_by_courses($courseids = array()) {

        $warnings = array();
        $externalresults = array();

        $params = array(
            'courseids' => $courseids,
        );
        $params = self::validate_parameters(self::get_externalcontents_by_courses_parameters(), $params);

        $mycourses = array();
        if (empty($params['courseids'])) {
            $mycourses = enrol_get_my_courses();
            $params['courseids'] = array_keys($mycourses);
        }

        // Ensure there are courseids to loop through.
        if (!empty($params['courseids'])) {

            list($courses, $warnings) = external_util::validate_courses($params['courseids'], $mycourses);

            // Get the externalcontents in this course, this function checks users visibility permissions.
            // We can avoid then additional validate_context calls.
            $externalcontents = get_all_instances_in_courses('externalcontent', $courses);
            foreach ($externalcontents as $externalcontent) {
                $context = context_module::instance($externalcontent->coursemodule);
                // Entry to return.
                $externalcontent->name = external_format_string($externalcontent->name, $context->id);

                $options = array('noclean' => true);
                list($externalcontent->intro, $externalcontent->introformat) =
                    external_format_text($externalcontent->intro, $externalcontent->introformat, $context->id,
                                            'mod_externalcontent',
                                            'intro', null, $options);
                $externalcontent->introfiles = external_util::get_area_files($context->id, 'mod_externalcontent',
                                            'intro', false, false);

                $options = array('noclean' => true);
                list($externalcontent->content, $externalcontent->contentformat) = external_format_text($externalcontent->content,
                                                                $externalcontent->contentformat,
                                                                $context->id, 'mod_externalcontent', 'content',
                                                                null, $options);
                $externalcontent->contentfiles = external_util::get_area_files($context->id, 'mod_externalcontent', 'content');

                $externalresults[] = $externalcontent;
            }
        }

        $result = array(
            'externalcontents' => $externalresults,
            'warnings' => $warnings
        );
        return $result;
    }

    /**
     * Describes the get_externalcontents_by_courses return value.
     *
     * @return external_single_structure
     */
    public static function get_externalcontents_by_courses_returns() {
        return new external_single_structure(
            array(
                'externalcontents' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Module id'),
                            'coursemodule' => new external_value(PARAM_INT, 'Course module id'),
                            'course' => new external_value(PARAM_INT, 'Course id'),
                            'name' => new external_value(PARAM_RAW, 'externalcontent name'),
                            'intro' => new external_value(PARAM_RAW, 'Summary'),
                            'introformat' => new external_format_value('intro', VALUE_REQUIRED, 'Summary format'),
                            'introfiles' => new external_files('Files in the introduction text'),
                            'content' => new external_value(PARAM_RAW, 'External content content'),
                            'contentformat' => new external_format_value('content', VALUE_REQUIRED, 'Content format'),
                            'contentfiles' => new external_files('Files in the content'),
                            'displayoptions' => new external_value(PARAM_RAW, 'Display options'),
                            'timemodified' => new external_value(PARAM_INT, 'Last time the externalcontent was modified'),
                            'section' => new external_value(PARAM_INT, 'Course section id'),
                            'visible' => new external_value(PARAM_INT, 'Module visibility'),
                            'groupmode' => new external_value(PARAM_INT, 'Group mode'),
                            'groupingid' => new external_value(PARAM_INT, 'Grouping id'),
                        )
                    )
                ),
                'warnings' => new external_warnings(),
            )
        );
    }
}
