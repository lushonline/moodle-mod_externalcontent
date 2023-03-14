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
 * Import record for mod_externalcontent
 *
 * @package     mod_externalcontent
 * @copyright   2019-2022 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_externalcontent;

/**
 * Import record for mod_externalcontent
 *
 * @package     mod_externalcontent
 * @copyright   2019-2022 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class importrecord {

    /** @var stdClass The course import data */
    protected $courseimport;

    /** @var stdClass The module import data */
    protected $moduleimport;

    /** @var stdClass The options for processing the import data */
    protected $options;

    /**
     * Return an empty course import record.
     *
     * @return stdClass
     */
    private function get_default_courseimport(): \stdClass {
        $defaults = get_config('moodlecourse');

        $course = new \stdClass();
        $course->idnumber = null;
        $course->shortname = null;
        $course->fullname = null;
        $course->summary = null;
        $course->summaryformat = 1; // FORMAT_HTML.
        $course->tags = array();
        $course->visible = null;
        $course->thumbnail = null;

        $course->format = "singleactivity";
        $course->numsections = 0;
        $course->newsitems = 0;
        $course->showgrades = $defaults->showgrades;
        $course->showreports = $defaults->showreports;
        $course->showactivitydates = $defaults->showactivitydates;
        $course->startdate = time();
        $course->activitytype = "externalcontent";
        $course->enablecompletion = 1;

        $course->category = null;

        $course->readonly = array(
            'summaryformat',
            'format',
            'numsections',
            'newsitems',
            'startdate',
            'activitytype',
            'enablecompletion'
        );

        return $course;
    }

    /**
     * Return an empty external content import record.
     *
     * @return stdClass
     */
    private function get_default_moduleimport(): \stdClass {
        $defaults = get_config('externalcontent');

        $module = new \stdClass();
        $module->name = null;
        $module->intro = null;
        $module->introformat = 1; // FORMAT_HTML.
        $module->content = null;
        $module->contentformat = 1; // FORMAT_HTML.
        $module->completionexternally = 0;
        $module->completion = 2;
        $module->completionview = 1;

        $module->printintro = $defaults->printintro;
        $module->printlastmodified = $defaults->printintro;

        $displayoptions = array();
        $displayoptions['printintro'] = $defaults->printintro;
        $displayoptions['printlastmodified'] = $defaults->printlastmodified;
        $module->displayoptions = serialize($displayoptions);

        $module->readonly = array(
            'introformat',
            'contentformat',
            'completion',
            'completionview',
            'displayoptions',
            'printintro',
            'printlastmodified'
        );

        return $module;
    }

    /**
     * Return an empty options import record.
     *
     * @return stdClass
     */
    private function get_default_options(): \stdClass {
        $options = new \stdClass();
        // Downloading of thumbnail.
        // 1 = Download and add to course.
        // 0 = Dont download.
        $options->downloadthumbnail = 1;

        $options->readonly = array();

        return $options;
    }

    /**
     * importrecord constructor
     *
     * @param object|null $courseimport - object with one or more properties available for courseimport or null.
     * @param object|null $moduleimport - object one or more properties available for moduleimport or null.
     * @param object|null $options - object one or more properties available for options or null.
     * @return void
     */
    public function __construct(?object $courseimport = null, ?object $moduleimport = null, ?object $options = null) {
        $this->courseimport = self::get_default_courseimport();
        $this->moduleimport = self::get_default_moduleimport();
        $this->options = self::get_default_options();

        self::update_courseimport($courseimport);
        self::update_moduleimport($moduleimport);
        self::update_options($options);
    }

    /**
     * Validate we have the minimum info for a course.
     *
     * @return bool true if validated
     */
    private function validate_course() {
        // As a minimum we need.
        // course idnumber is not empty.
        // course shortname is not empty.
        // course longname is not empty.
        // course category is an integer.

        $isvalid = true;
        $isvalid = $isvalid && !empty($this->courseimport->idnumber);
        $isvalid = $isvalid && !empty($this->courseimport->shortname);
        $isvalid = $isvalid && !empty($this->courseimport->fullname);
        $isvalid = $isvalid && is_int($this->courseimport->category);
        return $isvalid;
    }

    /**
     * Validate we have the minimum info for an external content module.
     *
     * @return bool true if validated
     */
    private function validate_module() {
        // As a minimum we need.
        // external name is not empty.
        // external intro is not empty.
        // external content is not empty.
        $isvalid = true;
        $isvalid = $isvalid && !empty($this->moduleimport->name);
        $isvalid = $isvalid && !empty($this->moduleimport->intro);
        $isvalid = $isvalid && !empty($this->moduleimport->content);
        return $isvalid;
    }


    /**
     * Validate we have the minimum info to create/update course and externalcontent module
     *
     * @return bool true if validated
     */
    public function validate() : bool {
        return self::validate_course() && self::validate_module();
    }

    /**
     * Get the course import record.
     *
     * @return stdClass|bool - returns the course import record or false if not valid
     */
    public function get_courseimport(): \stdClass {
        return $this->courseimport;
    }

    /**
     * Update the course import record, supplied values override current.
     * Only values not defined as readonly updated.
     *
     * @param object|null $updates - the courseimport updates or null.
     * @return bool
     */
    public function update_courseimport(?object $updates): bool {
        if (is_null($updates)) {
            return false;
        }

        // Loop thru all properties of this course import object.
        foreach ($this->courseimport as $k => $v) {
            // Check if passed object has property and it isnt readonly.
            if (isset($updates->$k) && !in_array($k, $this->courseimport->readonly)) {
                $this->courseimport->$k = $updates->$k;
            }
        }
        return true;
    }

    /**
     * Get the module import record.
     *
     * @return stdClass|bool - returns the module import record or false if not valid
     */
    public function get_moduleimport(): \stdClass {
        return $this->moduleimport;
    }

    /**
     * Update the module import record, supplied values override current.
     * Only values not defined as readonly updated.
     *
     * @param object|null $updates - the moduleimport updates or null.
     * @return bool
     */
    public function update_moduleimport(?object $updates): bool {
        if (is_null($updates)) {
            return false;
        }

        // Loop thru all properties of this module import object.
        foreach ($this->moduleimport as $k => $v) {
            // Check if passed object has property and it isnt readonly.
            if (isset($updates->$k) && !in_array($k, $this->moduleimport->readonly)) {
                $this->moduleimport->$k = $updates->$k;
            }
        }
        return true;
    }

    /**
     * Get the options record.
     *
     * @return stdClass|bool - returns the options or false if not valid
     */
    public function get_options(): \stdClass {
        return $this->options;
    }

    /**
     * Update the options record, supplied values override current.
     * Only values not defined as readonly updated.
     *
     * @param object|null $updates - the options updates or null.
     * @return bool
     */
    public function update_options(?object $updates): bool {
        if (is_null($updates)) {
            return false;
        }

        // Loop thru all properties of this module import object.
        foreach ($this->options as $k => $v) {
            // Check if passed object has property and it isnt readonly.
            if (isset($updates->$k) && !in_array($k, $this->options->readonly)) {
                $this->options->$k = $updates->$k;
            }
        }
        return true;
    }

}
