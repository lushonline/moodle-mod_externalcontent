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
 * Provides {@see \mod_externalcontent\output\mobile} class.
 *
 * @package     mod_externalcontent
 * @copyright   2019-2023 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_externalcontent\output;

defined('MOODLE_INTERNAL') || die();

use mod_externalcontent\instance;

require_once($CFG->dirroot . '/mod/externalcontent/lib.php');

/**
 * Controls the display of the plugin in the Mobile App.
 *
 * @package     mod_externalcontent
 * @copyright   2019-2023 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {

    /**
     * Return the data for the CoreCourseModuleDelegate delegate.
     *
     * @param object $args
     * @return array       HTML, javascript and otherdata
     * @throws \required_capability_exception
     * @throws \coding_exception
     * @throws \require_login_exception
     * @throws \moodle_exception
     */
    public static function mobile_course_view($args) {
        global $OUTPUT;

        $args = (object) $args;
        $versionname = $args->appversioncode >= 3950 ? 'latest' : 'ionic3';

        $instance = instance::get_from_cmid($args->cmid);

        if (!$instance) {
            return self::mobile_print_error(get_string('invalidaccessparameter'));
        }
        $cm = $instance->get_cm();

        require_login($args->courseid, false, $cm, true, true);

        $context = $instance->get_context_module();
        require_capability('mod/externalcontent:view', $context);

        $course = $instance->get_course();
        $externalcontent = $instance->get_module();

        // Mark the externalcontent as viewed.
        externalcontent_view($externalcontent, $course, $cm, $context);

        // Pre-format some strings for mobile app.
        $externalcontent->name = format_string($externalcontent->name);
        list($externalcontent->content, $externalcontent->contentformat) =
          external_format_text(
            $externalcontent->content,
            $externalcontent->contentformat,
            $context->id,
            'mod_externalcontent',
            'content'
          );

        $data = [
          'cmid' => $cm->id,
          'courseid' => $course->id,
          'module' => $externalcontent
        ];

        return [
          'templates' => [
            [
              'id' => 'main',
              'html' => $OUTPUT->render_from_template('mod_externalcontent/mobile_view_' . $versionname, $data),
            ],
          ],
          'javascript' => '',
          'otherdata' => '',
          'files' => [],
        ];
    }


    /**
     * Returns the view for errors.
     *
     * @param string $error Error to display.
     * @param string $versionname The version name of the template to use
     * @return array HTML, javascript and otherdata
     */
    protected static function mobile_print_error($error, $versionname = 'ionic3'): array {
        global $OUTPUT;

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template(
                        'mod_externalcontent/mobile_view_error_'.$versionname,
                        ['error' => $error]
                      ),
                ],
            ],
            'javascript' => '',
            'otherdata' => '',
            'files' => '',
        ];
    }
}
