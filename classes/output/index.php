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
namespace mod_externalcontent\output;

use html_table;
use html_writer;
use mod_externalcontent\instance;
use mod_externalcontent\plugin;
use renderable;
use renderer_base;
use stdClass;

/**
 * Renderer for Index page.
 *
 * @package     mod_externalcontent
 * @copyright   2019-2022 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class index implements renderable {

    /** @var stdClass */
    protected $course;

    /** @var stdClass[] */
    protected $instances;

    /**
     * Constructor for the index renderable.
     *
     * @param stdClass $course
     * @param instance[] List of external content instances
     */
    public function __construct(stdClass $course, array $instances) {
        $this->course = $course;
        $this->instances = $instances;
        $this->usesections = course_format_uses_sections($this->course->format);
    }

    /**
     * Get the table for the index page.
     *
     * @param renderer_base $output
     * @return html_table
     */
    public function get_table(renderer_base $output): html_table {
        // Get strings.
        $strname         = get_string('name');
        $strintro        = get_string('moduleintro');

        // Print the list of instances.
        $table = new html_table();
        $table->attributes['class'] = 'generaltable mod_index';

        if ($this->usesections) {
            $strsectionname = get_string('sectionname', 'format_'.$this->course->format);
            $table->head  = array ($strsectionname, $strname, $strintro);
            $table->align = array ('center', 'left', 'left');
        } else {
            $table->head  = array ($strname, $strintro);
            $table->align = array ('left', 'left');
        }

        $currentsection = '';
        foreach ($this->instances as $instance) {
            $currentsection = $this->add_instance_to_table($output, $table, $instance, $currentsection);
        }

        return $table;
    }

    /**
     * Add details of the external content instance to the table.
     *
     * @param renderer_base $output
     * @param html_table $table
     * @param instance $instance
     * @param string $currentsection
     * @return string The current section
     */
    protected function add_instance_to_table(renderer_base $output, html_table $table,
                                             instance $instance, string $currentsection): string {
        global $DB;
        $cm = $instance->get_cm();
        if (!$cm->uservisible) {
            return $currentsection;
        }

        $classes = [];
        if (!$cm->visible) {
            $classes = array('class' => 'dimmed');
        }
        $link = html_writer::link(
            $instance->get_view_url(),
            format_string($instance->get_name(), true),
            $classes);
        $intro = format_module_intro('externalcontent', $instance->get_instance_data(), $cm->id);

        // Determine the current section name.
        $printsection = '';
        if ($this->usesections) {
            $cs = $DB->get_record('course_sections', array('course' => $this->course->id, 'section' => $cm->sectionnum));

            if ($cs->name !== $currentsection) {
                if ($cm->section) {
                    $printsection = get_section_name($this->course->id, $cs);
                }
                if ($currentsection !== '') {
                    $table->data[] = 'hr';
                }
                $currentsection = $printsection;
            }
            $table->data[] = array($printsection, $link, $intro);
        } else {
            $table->data[] = array($link, $intro);
        }

        return $currentsection;
    }

}
