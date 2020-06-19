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
 * External content external functions and service definitions
 *
 * @package     mod_externalcontent
 * @category    external
 * @copyright   2019-2020 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = array(

    'mod_externalcontent_view_externalcontent' => array(
        'classname'     => 'mod_externalcontent_external',
        'methodname'    => 'view_externalcontent',
        'description'   => 'Simulate the view.php web interface externalcontent: trigger events, completion, etc...',
        'type'          => 'write',
        'capabilities'  => 'mod/externalcontent:view',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),

    'mod_externalcontent_get_externalcontents_by_courses' => array(
        'classname'     => 'mod_externalcontent_external',
        'methodname'    => 'get_externalcontents_by_courses',
        'description'   => 'Returns a list of external content in a provided list of courses,
                            f no list is provided all external content that the user
                            can view will be returned.',
        'type'          => 'read',
        'capabilities'  => 'mod/externalcontent:view',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
);