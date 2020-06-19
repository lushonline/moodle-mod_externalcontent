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
 * Plugin administration pages are defined here.
 *
 * @package     mod_externalcontent
 * @copyright   2019-2020 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Mod edit settings.
    $settings->add(new admin_setting_heading('externalcontentmodeditdefaults', get_string('modeditdefaults', 'admin'),
                        get_string('condifmodeditdefaults', 'admin')));

    $settings->add(new admin_setting_configcheckbox('externalcontent/printheading',
        get_string('printheading', 'externalcontent'), get_string('printheadingexplain', 'externalcontent'), 0));
    $settings->add(new admin_setting_configcheckbox('externalcontent/printintro',
        get_string('printintro', 'externalcontent'), get_string('printintroexplain', 'externalcontent'), 0));
    $settings->add(new admin_setting_configcheckbox('externalcontent/printlastmodified',
        get_string('printlastmodified', 'externalcontent'), get_string('printlastmodifiedexplain', 'externalcontent'), 1));
}
