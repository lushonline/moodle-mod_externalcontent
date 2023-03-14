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
 * Provides {@see \mod_externalcontent\output\renderer} class.
 *
 * @package     mod_externalcontent
 * @copyright   2019-2023 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_externalcontent\output;

use core\notification;
use html_table;
use html_writer;
use mod_externalcontent\instance;
use plugin_renderer_base;

/**
 * Renderer for mod_externalcontent plugin.
 *
 * @package     mod_externalcontent
 * @copyright   2019-2023 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Render the index table.
     *
     * @param  index $index
     * @return string
     */
    protected function render_index(index $index): string {
        return html_writer::table($index->get_table($this));
    }


    /**
     * Render the view page.
     *
     * @param view_page $page
     * @return string
     */
    public function render_view_page(view_page $page): string {
        return $this->render_from_template(
            'mod_externalcontent/view_page',
            $page->export_for_template($this)
        );
    }
}
