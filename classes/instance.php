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
 * Instance record for mod_externalcontent
 *
 * @package     mod_externalcontent
 * @copyright   2019-2022 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_externalcontent;

use cm_info;
use context;
use context_course;
use context_module;
use moodle_url;
use stdClass;

/**
 * Instance record for mod_externalcontent
 *
 * @package     mod_externalcontent
 * @copyright   2019-2022 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class instance {

    /** @var cm_info The cm_info object relating to the instance */
    protected $cm;

    /** @var stdClass The course that the instance is in */
    protected $course;

    /** @var stdClass The instance data for the instance */
    protected $instancedata;

    /** @var context The current context */
    protected $context;

    /**
     * instance constructor.
     *
     * @param cm_info $cm
     * @param stdClass $course
     * @param stdClass $instancedata
     */
    public function __construct(cm_info $cm, stdClass $course, stdClass $instancedata) {
        $this->cm = $cm;
        $this->course = $course;
        $this->instancedata = $instancedata;
    }

    /**
     * Get the instance information from an instance id.
     *
     * @param int $instanceid The id from the bigbluebuttonbn table
     * @return null|self
     */
    public static function get_from_instanceid(int $instanceid): ?self {
        global $DB;

        $coursetable = new \core\dml\table('course', 'c', 'c');
        $courseselect = $coursetable->get_field_select();
        $coursefrom = $coursetable->get_from_sql();

        $cmtable = new \core\dml\table('course_modules', 'cm', 'cm');
        $cmfrom = $cmtable->get_from_sql();

        $exttable = new \core\dml\table('externalcontent', 'ext', 'ext');
        $extselect = $exttable->get_field_select();
        $extfrom = $exttable->get_from_sql();

        $sql = <<<EOF
            SELECT {$courseselect}, {$extselect}
                FROM {$cmfrom}
            INNER JOIN {$coursefrom} ON c.id = cm.course
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {$extfrom} ON cm.instance = ext.id
            WHERE ext.id = :instanceid
            EOF;

        $result = $DB->get_record_sql($sql, [
            'modname' => 'externalcontent',
            'instanceid' => $instanceid,
        ]);

        if (empty($result)) {
            return null;
        }

        $course = $coursetable->extract_from_result($result);
        $instancedata = $exttable->extract_from_result($result);
        $cm = get_fast_modinfo($course)->instances['externalcontent'][$instancedata->id];

        return new self($cm, $course, $instancedata);
    }

    /**
     * Get the instance information from a cmid.
     *
     * @param int $cmid
     * @return null|self
     */
    public static function get_from_cmid(int $cmid): ?self {
        global $DB;

        $coursetable = new \core\dml\table('course', 'c', 'c');
        $courseselect = $coursetable->get_field_select();
        $coursefrom = $coursetable->get_from_sql();

        $cmtable = new \core\dml\table('course_modules', 'cm', 'cm');
        $cmfrom = $cmtable->get_from_sql();

        $exttable = new \core\dml\table('externalcontent', 'ext', 'ext');
        $extselect = $exttable->get_field_select();
        $extfrom = $exttable->get_from_sql();

        $sql = <<<EOF
                SELECT {$courseselect}, {$extselect}
                FROM {$cmfrom}
            INNER JOIN {$coursefrom} ON c.id = cm.course
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {$extfrom} ON cm.instance = ext.id
                WHERE cm.id = :cmid
            EOF;

        $result = $DB->get_record_sql($sql, [
            'modname' => 'externalcontent',
            'cmid' => $cmid,
        ]);

        if (empty($result)) {
            return null;
        }

        $course = $coursetable->extract_from_result($result);
        $instancedata = $exttable->extract_from_result($result);
        $cm = get_fast_modinfo($course)->get_cm($cmid);

        return new self($cm, $course, $instancedata);
    }

    /**
     * Get the instance information from an course module idnumber.
     *
     * @param string $idnumber
     * @return null|self
     */
    public static function get_from_idnumber(string $idnumber): ?self {
        global $DB;

        $coursetable = new \core\dml\table('course', 'c', 'c');
        $courseselect = $coursetable->get_field_select();
        $coursefrom = $coursetable->get_from_sql();

        $cmtable = new \core\dml\table('course_modules', 'cm', 'cm');
        $cmfrom = $cmtable->get_from_sql();

        $exttable = new \core\dml\table('externalcontent', 'ext', 'ext');
        $extselect = $exttable->get_field_select();
        $extfrom = $exttable->get_from_sql();

        $sql = <<<EOF
                SELECT {$courseselect}, {$extselect}
                FROM {$cmfrom}
            INNER JOIN {$coursefrom} ON c.id = cm.course
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {$extfrom} ON cm.instance = ext.id
                WHERE cm.idnumber = :idnumber
            EOF;

        $result = $DB->get_record_sql($sql, [
            'modname' => 'externalcontent',
            'idnumber' => $idnumber,
        ]);

        if (empty($result)) {
            return null;
        }

        $course = $coursetable->extract_from_result($result);
        $instancedata = $exttable->extract_from_result($result);
        $cm = get_fast_modinfo($course)->instances['externalcontent'][$instancedata->id];

        return new self($cm, $course, $instancedata);
    }

    /**
     * Get all instances in the specified course.
     *
     * @param int $courseid
     * @return self[]
     */
    public static function get_all_instances_in_course(int $courseid): array {
        global $DB;

        $coursetable = new \core\dml\table('course', 'c', 'c');
        $courseselect = $coursetable->get_field_select();
        $coursefrom = $coursetable->get_from_sql();

        $cmtable = new \core\dml\table('course_modules', 'cm', 'cm');
        $cmfrom = $cmtable->get_from_sql();

        $exttable = new \core\dml\table('externalcontent', 'ext', 'ext');
        $extselect = $exttable->get_field_select();
        $extfrom = $exttable->get_from_sql();

        $sql = <<<EOF
                SELECT cm.id as cmid, {$courseselect}, {$extselect}
                FROM {$cmfrom}
            INNER JOIN {$coursefrom} ON c.id = cm.course
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {$extfrom} ON cm.instance = ext.id
                WHERE cm.course = :courseid
            EOF;

        $results = $DB->get_records_sql($sql, [
            'modname' => 'externalcontent',
            'courseid' => $courseid,
        ]);

        $instances = [];
        foreach ($results as $result) {
            $course = $coursetable->extract_from_result($result);
            $instancedata = $exttable->extract_from_result($result);
            $cm = get_fast_modinfo($course)->get_cm($result->cmid);
            $instances[$cm->id] = new self($cm, $course, $instancedata);
        }

        return $instances;
    }

    /**
     * Get the course object for the instance.
     *
     * @return stdClass
     */
    public function get_course(): stdClass {
        return $this->course;
    }

    /**
     * Get the course id of the course that the instance is in.
     *
     * @return int
     */
    public function get_course_id(): int {
        return $this->course->id;
    }

    /**
     * Get the course idnumber of the course that the instance is in.
     *
     * @return string
     */
    public function get_course_idnumber(): string {
        return $this->course->idnumber;
    }

    /**
     * Get the cm_info object for the instance.
     *
     * @return cm_info
     */
    public function get_cm(): cm_info {
        return $this->cm;
    }

    /**
     * Get the id of the course module.
     *
     * @return int
     */
    public function get_cm_id(): int {
        return $this->get_cm()->id;
    }

    /**
     * Get the cm idnumber of the cm that the instance is in.
     *
     * @return string
     */
    public function get_cm_idnumber(): string {
        return $this->get_cm()->idnumber;
    }


    /**
     * Get the context.
     *
     * @return context_module
     */
    public function get_context(): context_module {
        if ($this->context === null) {
            $this->context = context_module::instance($this->get_cm()->id);
        }

        return $this->context;
    }

    /**
     * Get the context ID of the module context.
     *
     * @return int
     */
    public function get_context_id(): int {
        return $this->get_context()->id;
    }

    /**
     * Get the course context.
     *
     * @return context_course
     */
    public function get_course_context(): context_course {
        return $this->get_context()->get_course_context();
    }

    /**
     * Get the external content instance data.
     *
     * @return stdClass
     */
    public function get_instance_data(): stdClass {
        return $this->instancedata;
    }

    /**
     * Get the external content description with the pluginfile URLs rewritten.
     *
     * @return string
     */
    public function get_description(): string {
        $description = $this->get_instance_var('intro');

        $description = file_rewrite_pluginfile_urls(
            $description,
            'pluginfile.php',
            $this->get_context_id(),
            'mod_externalcontent',
            'intro',
            null
        );

        return $description;
    }

    /**
     * Get the external content with the pluginfile URLs rewritten.
     *
     * @return string
     */
    public function get_content(): string {
        $content = $this->get_instance_var('content');

        $content = file_rewrite_pluginfile_urls(
            $content,
            'pluginfile.php',
            $this->get_context_id(),
            'mod_externalcontent',
            'content',
            null
        );

        return $content;
    }

    /**
     * Get the external content name.
     *
     * @return string
     */
    public function get_name(): string {
        return $this->get_instance_var('name');
    }

    /**
     * Get the instance id.
     *
     * @return int
     */
    public function get_instance_id(): int {
        return $this->instancedata->id;
    }

    /**
     * Helper to get a course var.
     *
     * @param string $name
     * @return string
     */
    public function get_course_var(string $name) {
        $course = $this->get_course();
        if (property_exists($course, $name)) {
            return $course->{$name};
        }

        return null;
    }

    /**
     * Helper to get an instance var.
     *
     * @param string $name
     * @return string
     */
    public function get_instance_var(string $name) {
        $instance = $this->get_instance_data();
        if (property_exists($instance, $name)) {
            return $instance->{$name};
        }

        return null;
    }

    /**
     * Get the user.
     *
     * @return stdClass
     */
    public function get_user(): stdClass {
        global $USER;

        return $USER;
    }

    /**
     * Get the id of the user.
     *
     * @return int
     */
    public function get_user_id(): int {
        $user = $this->get_user();

        return $user->id;
    }

    /**
     * Get the fullname of the current user.
     *
     * @return string
     */
    public function get_user_fullname(): string {
        $user = $this->get_user();

        return fullname($user);
    }

    /**
     * Whether the current user is an administrator.
     *
     * @return bool
     */
    public function is_admin(): bool {
        global $USER;

        return is_siteadmin($USER->id);
    }

    /**
     * Whether the courese is visible.
     *
     * @return bool
     */
    public function is_course_visible(): bool {
        return (bool) $this->get_course_var('visible');
    }

    /**
     * Get the URL used to access the course that the instance is in.
     *
     * @return moodle_url
     */
    public function get_course_url(): moodle_url {
        return new moodle_url('/course/view.php', ['id' => $this->get_course_id()]);
    }

    /**
     * Get the URL used to view the instance as a user.
     *
     * @return moodle_url
     */
    public function get_view_url(): moodle_url {
        return new moodle_url('/mod/externalcontent/view.php', ['id' => $this->get_cm_id()]);
    }
}
