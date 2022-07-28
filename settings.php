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
 * @copyright   2019-2022 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Mod edit settings.
    $settings->add(new admin_setting_heading('externalcontentmodeditdefaults', get_string('modeditdefaults', 'admin'),
                        get_string('condifmodeditdefaults', 'admin')));

    $settings->add(new admin_setting_configcheckbox('externalcontent/printintro',
        get_string('printintro', 'externalcontent'), get_string('printintroexplain', 'externalcontent'), 0));
    $settings->add(new admin_setting_configcheckbox('externalcontent/printlastmodified',
        get_string('printlastmodified', 'externalcontent'), get_string('printlastmodifiedexplain', 'externalcontent'), 1));

    $settings->add(new admin_setting_heading('externalcontent/xapisetting',
        get_string('xapisetting', 'externalcontent'), ''));

    $settings->add(new admin_setting_configcheckbox('externalcontent/xapienable',
        get_string('xapienable', 'externalcontent'), get_string('xapienableexplain', 'externalcontent'), 1));

    $settings->add(new admin_setting_configtext('externalcontent/xapiusername',
        get_string('xapiusername', 'externalcontent'), get_string('xapiusernameexplain', 'externalcontent'),
        get_config('externalcontent', 'xapidefaultusername')));

        $settings->add(new admin_setting_configtext('externalcontent/xapipassword',
        get_string('xapipassword', 'externalcontent'), get_string('xapipasswordexplain', 'externalcontent'),
        get_config('externalcontent', 'xapidefaultpassword')));

    $settings->add(new admin_setting_configtext('externalcontent/xapicompletionverbs',
        get_string('xapicompletionverbs', 'externalcontent'), get_string('xapicompletionverbsexplain', 'externalcontent'),
        'http://adlnet.gov/expapi/verbs/completed,http://adlnet.gov/expapi/verbs/passed'));

    $settings->add(new admin_setting_heading('externalcontent/xapiurlsetting',
        get_string('xapiurl', 'externalcontent'), ''));

    $xapiurl = new moodle_url('/mod/externalcontent/lrs/index.php');
    $settings->add(new admin_setting_heading('externalcontent/xapiurl', '',
                    get_string('xapiurlintro', 'externalcontent', $xapiurl.'')));
}
