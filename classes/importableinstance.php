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
 * Importable Instance record for mod_externalcontent
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
 * Importable Instance record for mod_externalcontent
 *
 * @package     mod_externalcontent
 * @copyright   2019-2022 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class importableinstance extends instance {

    /**
     * update_course_completion_criteria
     *
     * @param stdClass $course
     * @param stdClass $cm
     * @return bool
     */
    private static function update_course_completion_criteria(stdClass $course, stdClass $cm) : bool {
        global $CFG;

        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_activity.php');

        $criterion = new \completion_criteria_activity();

        $params = array('id' => $course->id, 'criteria_activity' => array($cm->id => 1));
        if ($criterion->fetch($params)) {
            return false;
        }

        // Criteria for course.
        $criteriadata = new \stdClass();
        $criteriadata->id = $course->id;
        $criteriadata->criteria_activity = array($cm->id => 1);
        $criterion->update_config($criteriadata);

        // Handle overall aggregation.
        $aggdata = array(
            'course'        => $course->id,
            'criteriatype'  => null,
            'method' => COMPLETION_AGGREGATION_ALL
        );

        $aggregation = new \completion_aggregation($aggdata);
        $aggregation->save();

        $aggdata['criteriatype'] = COMPLETION_CRITERIA_TYPE_ACTIVITY;
        $aggregation = new \completion_aggregation($aggdata);
        $aggregation->save();

        $aggdata['criteriatype'] = COMPLETION_CRITERIA_TYPE_COURSE;
        $aggregation = new \completion_aggregation($aggdata);
        $aggregation->save();

        $aggdata['criteriatype'] = COMPLETION_CRITERIA_TYPE_ROLE;
        $aggregation = new \completion_aggregation($aggdata);
        $aggregation->save();

        return true;
    }

    /**
     * set_course_thumbnail_from_url1
     *
     * @param instance $instance
     * @param string $url
     * @return bool
     */
    private static function set_course_thumbnail(instance $instance, ?string $url = null) : bool {
        global $CFG;

        if (is_null($url)) {
            return false;
        }

        require_once($CFG->libdir . '/filelib.php');

        // Check is valid url and filetype is allowed.
        $thumbnailurl = new moodle_url($url);
        $overviewfilesoptions = course_overviewfiles_options($instance->course->id);
        $filetypesutil = new \core_form\filetypes_util();
        $whitelist = $filetypesutil->normalize_file_types($overviewfilesoptions['accepted_types']);

        $ext = pathinfo($thumbnailurl->get_path(), PATHINFO_EXTENSION);
        $filename = 'thumbnail.' . $ext;

        // Check the extension is valid.
        if (!$filetypesutil->is_allowed_file_type($filename, $whitelist)) {
            return false;
        }

        // Now see if we already have thumbnail.
        $coursecontextid = $instance->get_context_course()->id;

        $fs = get_file_storage();

        if ($thumbnailfile = $fs->get_file($coursecontextid, 'course', 'overviewfiles', 0, '/', $filename)) {
            // Check is the the file the same source as url.
            if ($thumbnailfile->get_source() == $url) {
                // Source is the same so do nothing.
                return false;
            }
        }

        // Delete existing thumbnail files and continue with download.
        $fs->delete_area_files($coursecontextid, 'course', 'overviewfiles');

        $thumbnailfilerecord = array(
            'contextid' => $coursecontextid,
            'component' => 'course',
            'filearea' => 'overviewfiles',
            'itemid' => '0',
            'filepath' => '/',
            'filename' => $filename,
        );

        $urlparams = array(
            'calctimeout' => false,
            'timeout' => 5,
            'skipcertverify' => true,
            'connecttimeout' => 5,
        );

        try {
            $thumbnailfile = $fs->create_file_from_url($thumbnailfilerecord, $url, $urlparams);
            // Check if Moodle recognises as a valid image file.
            if (!$thumbnailfile->is_valid_image()) {
                $fs->delete_area_files($coursecontextid, 'course', 'overviewfiles');
                return false;
            } else {
                return true;
            }
        } catch (\file_exception $e) {
            return false;
        }
    }

    /**
     * Get the current course tags as array sorted alphbetically
     *
     * @param int $courseid
     * @return array
     */
    private static function get_course_tags(int $courseid) : array {
        $tags = array();
        if (\core_tag_tag::is_enabled('core', 'course')) {
            $coursetags = \core_tag_tag::get_item_tags_array('core', 'course', $courseid,
                    \core_tag_tag::BOTH_STANDARD_AND_NOT, 0, false);

            foreach ($coursetags as $value) {
                array_push($tags, $value);
            }
        }
        return $tags;
    }

    /**
     * create_from_importrecord
     *
     * @param importrecord $importrecord
     * @return null|instance
     */
    private static function create_from_importrecord(importrecord $importrecord) : ?instance {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->libdir . '/phpunit/classes/util.php');
        require_once($CFG->dirroot . '/mod/externalcontent/lib.php');

        $messages = array();

        // We need to create.
        $generator = \phpunit_util::get_data_generator();

        $courseimport = clone $importrecord->get_courseimport();
        $moduleimport = clone $importrecord->get_moduleimport();

        $course = create_course($courseimport);
        $moduleimport->course = $course->id;

        $messages[] = 'Course: created.';
        $messages[] = $courseimport->visible ? 'Course Visibility Updated: visible.' : 'Course Visibility Updated: hidden.';

        $instance = $generator->create_module('externalcontent', $moduleimport);

        $cm = get_coursemodule_from_instance('externalcontent', $instance->id);
        $cm->idnumber = $course->idnumber;
        $DB->update_record('course_modules', $cm);
        $messages[] = 'Module: created.';

        if (self::update_course_completion_criteria($course, $cm)) {
            $messages[] = 'Course: completion criteria created.';
        };

        $newinstance = self::get_from_cmid($cm->id);
        // Set the tags.
        if (\core_tag_tag::is_enabled('core', 'course') && count($courseimport->tags) > 0) {
            \core_tag_tag::set_item_tags('core', 'course', $newinstance->get_course_id(),
                                         $newinstance->get_context_course(), $courseimport->tags);
            $messages[] = 'Course Property: tags created.';
        }

        if (self::set_course_thumbnail($newinstance, $courseimport->thumbnail)) {
            $messages[] = 'Course: thumbnail created.';
        };

        $newinstance->clear_messages();
        $newinstance->set_messages($messages);
        return $newinstance;
    }

    /**
     * update_from_importrecord
     *
     * @param instance $instance
     * @param importrecord $importrecord
     * @return null|instance
     */
    private static function update_from_importrecord(instance $instance, importrecord $importrecord) : ?instance {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->libdir . '/phpunit/classes/util.php');
        require_once($CFG->dirroot . '/mod/externalcontent/lib.php');

        $messages = array();

        $courseimport = clone $importrecord->get_courseimport();

        if ($instance->is_course_visible() != (bool)$courseimport->visible) {
            $messages[] = $courseimport->visible ? 'Course Visibility Updated: visible.' : 'Course Visibility Updated: hidden.';
        }

        $courseupdateneeded = false;
        foreach ($instance->course as $k => $v) {
            if (isset($courseimport->$k) && !in_array($k, $courseimport->readonly)) {
                if ($instance->course->$k != $courseimport->$k) {
                    $instance->course->$k = $courseimport->$k;
                    $messages[] = 'Course Property: '.$k.' updated';
                    $courseupdateneeded = true;
                };
            }
        }

        // Update the course object.
        if ($courseupdateneeded) {
            update_course($instance->course);
        }

        // Update tags.
        if (\core_tag_tag::is_enabled('core', 'course') && count($courseimport->tags) > 0) {
            $existingtags = self::get_course_tags($instance->get_course_id());

            if (!$tagsmatch = (
                array_diff($existingtags, $courseimport->tags) == [] &&
                array_diff($courseimport->tags, $existingtags) == [])) {
                    \core_tag_tag::set_item_tags('core', 'course',
                                                 $instance->get_course_id(),
                                                 $instance->get_context_course(),
                                                 $courseimport->tags);
                    $messages[] = 'Course Property: tags updated.';
            }
        }

        $moduleimport = clone $importrecord->get_moduleimport();

        $moduleupdateneeded = false;
        foreach ($instance->module as $k => $v) {
            if (isset($moduleimport->$k) && !in_array($k, $moduleimport->readonly)) {
                if ($instance->module->$k != $moduleimport->$k) {
                    $instance->module->$k = $moduleimport->$k;
                    $messages[] = 'Module Property: '.$k.' updated';
                    $moduleupdateneeded = true;
                }
            }
        }

        // Update the externalcontent object.
        if ($moduleupdateneeded) {
            $DB->update_record('externalcontent',  $instance->module);
        }

        // Update the cm.
        $cm = get_coursemodule_from_instance('externalcontent',  $instance->module->id);

        if ($cm->idnumber != $instance->course->idnumber) {
            $cm->idnumber = $instance->course->idnumber;
            $DB->update_record('course_modules', $cm);
            $messages[] = 'Module Property: idnumber updated';
        };

        if (self::update_course_completion_criteria($instance->course, $cm)) {
            $messages[] = 'Course: completion criteria updated.';
        };

        if (self::set_course_thumbnail($instance, $courseimport->thumbnail)) {
            $messages[] = 'Course: thumbnail updated.';
        };

        $newinstance = self::get_from_cmid($cm->id);
        $newinstance->clear_messages();
        $newinstance->set_messages(count($messages) > 0 ? $messages : ['No changes']);
        return $newinstance;
    }

    /**
     * Get the instance information from an import record.
     * This will upsert an instance.
     *
     * @param importrecord $importrecord
     * @return null|instance
     */
    public static function get_from_importrecord(importrecord $importrecord): ?instance {
        if ($valid = $importrecord->validate()) {
            if ($result = self::get_from_cmidnumber($importrecord->get_courseimport()->idnumber)) {
                // Update.
                return self::update_from_importrecord($result, $importrecord);
            } else {
                // Create.
                return self::create_from_importrecord($importrecord);
            }
        } else {
            return null;
        }
    }

    /**
     * set_course_thumbnail_from_url
     *
     * @param string $url
     * @return bool
     */
    public function set_course_thumbnail_from_url(string $url): bool {
        return self::set_course_thumbnail($this, $url);
    }
}
