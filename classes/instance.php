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

    /** @var cm_info The cm_info object relating to the module */
    protected $cm = null;

    /** @var stdClass The course that the module is in */
    protected $course = null;

    /** @var stdClass The module data for the module */
    protected $module = null;

    /** @var modulecontext The current module context */
    protected $modulecontext = null;

    /** @var messages An array of messages about changes made or erros */
    protected $messages = null;

    /**
     * Instance constructor.
     *
     * @param cm_info $cm
     * @param stdClass $course
     * @param stdClass $module
     * @param array $messages
     */
    public function __construct(cm_info $cm, stdClass $course, stdClass $module, array $messages = array()) {
        $this->cm = $cm;
        $this->course = $course;
        $this->module = $module;
        $this->messages = $messages;
    }

    /**
     * Retrieve records from database, passing where clause to query
     *
     * If no errors occur the return value
     * is an associative whose keys come from the cm id,
     * and whose values are the corresponding instances.
     * False is returned if an error occurs.
     *
     * @param string $where the where clause
     * @return self[]|bool
     */
    private static function get_from_sql(string $where) : ?array {
        global $DB;

        if (empty($where)) {
            return null;
        }

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
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = 'externalcontent'
            INNER JOIN {$extfrom} ON cm.instance = ext.id
                {$where}
            EOF;

        if ($results = $DB->get_records_sql($sql)) {
            $instances = [];
            foreach ($results as $result) {
                $course = $coursetable->extract_from_result($result);
                $module = $exttable->extract_from_result($result);
                $cm = get_fast_modinfo($course)->get_cm($result->cmid);
                $instances[$cm->id] = new self($cm, $course, $module);
            }
            return $instances;
        };

        return null;
    }

    /**
     * Get the module information from an module id.
     *
     * If no errors occur the return value
     * is the corresponding module.
     * null is returned if an error occurs.
     *
     * @param int $moduleid The externalcontent id
     * @return self|bool
     */
    public static function get_from_moduleid(int $moduleid): ?self {
        $where = "WHERE ext.id = {$moduleid}";
        if ($results = self::get_from_sql($where)) {
            // Return the first result.
            return reset($results);
        }
        return null;
    }

    /**
     * Get the module information from a course module id.
     *
     * If no errors occur the return value
     * is the corresponding module.
     * null is returned if an error occurs.
     *
     * @param int $cmid The course module id
     * @return null|self
     */
    public static function get_from_cmid(int $cmid): ?self {
        $where = "WHERE cm.id = {$cmid}";
        if ($results = self::get_from_sql($where)) {
            // Return the first result.
            return reset($results);
        }
        return null;
    }

    /**
     * Get the module information from a course module idnumber.
     *
     * If no errors occur the return value
     * is the corresponding module.
     * null is returned if an error occurs.
     *
     * @param string $idnumber The course module idnumber
     * @return null|self
     */
    public static function get_from_cmidnumber(string $idnumber): ?self {
        // The idnumber is string so we need to quote it.
        $where = "WHERE cm.idnumber = '{$idnumber}'";
        if ($results = self::get_from_sql($where)) {
            // Return the first result.
            return reset($results);
        }
        return null;
    }

    /**
     * Get all modules in the specified course.
     *
     * If no errors occur the return value
     * is an associative whose keys come from the cm id,
     * and whose values are the corresponding module.
     * False is returned if an error occurs.
     *
     * @param int $courseid
     * @return null|self[]
     */
    public static function get_all_modules_in_course(int $courseid): ?array {
        $where = "WHERE cm.course = {$courseid}";
        if ($results = self::get_from_sql($where)) {
            return $results;
        }
        return null;
    }

    /**
     * Get the course object for the module.
     *
     * @return stdClass
     */
    public function get_course(): stdClass {
        if ($this->course === null) {
            throw new moodle_exception('course has not been initialized');
        }
        return $this->course;
    }

    /**
     * Get the cm_info object for the module.
     *
     * @return cm_info
     */
    public function get_cm(): cm_info {
        if ($this->cm === null) {
            throw new moodle_exception('cm has not been initialized');
        }
        return $this->cm;
    }

    /**
     * Get the external content module data.
     *
     * @return stdClass
     */
    public function get_module(): stdClass {
        if ($this->module === null) {
            throw new moodle_exception('module has not been initialized');
        }
        return $this->module;
    }

    /**
     * Get the course module context.
     *
     * @return context_module
     */
    public function get_context_module(): context_module {
        if ($this->modulecontext === null) {
            $this->modulecontext = context_module::instance($this->get_cm()->id);
        }

        return $this->modulecontext;
    }

    /**
     * Get the ID of the context module.
     *
     * @return int
     */
    public function get_context_module_id(): int {
        return $this->get_context_module()->id;
    }

    /**
     * Get the course context.
     *
     * @return context_course
     */
    public function get_context_course(): context_course {
        return $this->get_context_module()->get_course_context();
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
     * Helper to get a course module var.
     *
     * @param string $name
     * @return string
     */
    public function get_cm_var(string $name) {
        $cm = $this->get_cm();
        if (property_exists($cm, $name)) {
            return $cm->{$name};
        }

        return null;
    }

    /**
     * Helper to get a module var.
     *
     * @param string $name
     * @return string
     */
    public function get_module_var(string $name) {
        $module = $this->get_module();
        if (property_exists($module, $name)) {
            return $module->{$name};
        }

        return null;
    }

    /**
     * Get the course id of the course that the module is in.
     *
     * @return int
     */
    public function get_course_id(): int {
        return $this->get_course()->id;
    }

    /**
     * Get the course idnumber of the course that the module is in.
     *
     * @return string
     */
    public function get_course_idnumber(): string {
        return $this->get_course()->idnumber;
    }

    /**
     * Get the URL used to access the course that the module is in.
     *
     * @return moodle_url
     */
    public function get_course_url(): moodle_url {
        return new moodle_url('/course/view.php', ['id' => $this->get_course_id()]);
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
     * Get the id of the course module.
     *
     * @return int
     */
    public function get_cm_id(): int {
        return $this->get_cm()->id;
    }

    /**
     * Get the idnumber of the cm that the module is in.
     *
     * @return string
     */
    public function get_cm_idnumber(): string {
        return $this->get_cm()->idnumber;
    }

    /**
     * Get the id of the module.
     *
     * @return string
     */
    public function get_module_id(): int {
        return $this->get_module()->id;
    }

    /**
     * Get the external content intro with the pluginfile URLs rewritten.
     *
     * @return string
     */
    public function get_module_intro(): string {
        $intro = $this->get_module_var('intro');

        $intro = file_rewrite_pluginfile_urls(
        $intro,
        'pluginfile.php',
        $this->get_context_module_id(),
        'mod_externalcontent',
        'intro',
        null
        );

        return $intro;
    }

    /**
     * Get the external content with the pluginfile URLs rewritten.
     *
     * @return string
     */
    public function get_module_content(): string {
        $content = $this->get_module_var('content');

        $content = file_rewrite_pluginfile_urls(
        $content,
        'pluginfile.php',
        $this->get_context_module_id(),
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
    public function get_module_name(): string {
        return $this->get_module_var('name');
    }

    /**
     * Get the URL used to view the module as a user.
     *
     * @return moodle_url
     */
    public function get_module_url(): moodle_url {
        return new moodle_url('/mod/externalcontent/view.php', ['id' => $this->get_cm_id()]);
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
     * clear the messages
     *
     * @return bool
     */
    public function clear_messages(): void {
        $this->messages = array();
    }

    /**
     * set the messages
     *
     * @param array $messages
     * @return bool
     */
    public function set_messages(array $messages): void {
        $this->messages = array_merge($this->messages, $messages);
    }

    /**
     * get the messages
     *
     * @return array
     */
    public function get_messages(): array {
        return $this->messages;
    }

}
