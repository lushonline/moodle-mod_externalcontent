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

use mod_externalcontent\instance;
use mod_externalcontent\plugin;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Renderer for Index page.
 *
 * @package     mod_externalcontent
 * @copyright   2019-2022 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class view_page implements renderable, templatable {

    /** @var instance The instance being rendered */
    protected $instance;

    /**
     * Constructor for the View Page.
     *
     * @param instance $instance
     */
    public function __construct(instance $instance) {
        $this->instance = $instance;
    }

    /**
     * Export the content required to render the template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {

        $displayopions = empty($this->instance->get_instance_var('displayoptions')) ?
                        array() :
                        unserialize($this->instance->get_instance_var('displayoptions'));

        $templatedata = (object) [
            'instanceid' => $this->instance->get_instance_id(),
            'name' => $this->instance->get_name(),
            'description' => $this->instance->get_description(),
            'content' => $this->instance->get_content(),
            'showlastmodified' => $displayopions['printlastmodified'] == 1,
            'lastmodified' => get_string("lastmodified").": ".userdate($this->instance->get_instance_var('timemodified'))
        ];
        return $templatedata;
    }
}
