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
 * Plugin strings are defined here.
 *
 * @package     mod_externalcontent
 * @category    string
 * @copyright   2019-2020 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'External content';

$string['indicator:cognitivedepth'] = 'External content cognitive';
$string['indicator:cognitivedepth_help'] = 'This indicator is based on the cognitive depth reached by the student in an External content resource.';
$string['indicator:socialbreadth'] = 'External content social';
$string['indicator:socialbreadth_help'] = 'This indicator is based on the social breadth reached by the student in an External content resource.';

$string['content'] = 'External content';
$string['contentheader'] = 'Content';
$string['createwithcompletion'] = 'Create a new External content resource';

$string['modulename'] = 'External content';
$string['modulename_help'] = 'The module enables a teacher to create a resource using the text editor.

The resource can display text, images, sound, video and web links.

Advantages of using the External content module rather than the standard page module is that optionally if the content links to an
external site that records a completion status these can then be imported.';
$string['modulename_link'] = 'mod/externalcontent/view';
$string['modulenameplural'] = 'External content';
$string['optionsheader'] = 'Display options';
$string['externalcontent-mod-externalcontent-x'] = 'Any External content module page';
$string['externalcontent:addinstance'] = 'Add a new External content resource';
$string['externalcontent:view'] = 'View External content content';
$string['externalcontent:viewreports'] = 'View External content reports';
$string['pluginadministration'] = 'External content module administration';
$string['printheading'] = 'Display External content name';
$string['printheadingexplain'] = 'Display External content name above content?';
$string['printintro'] = 'Display External content description';
$string['printintroexplain'] = 'Display External content description above content?';
$string['printlastmodified'] = 'Display last modified date';
$string['printlastmodifiedexplain'] = 'Display last modified date below content?';
$string['privacy:metadata'] = 'The External content resource plugin does not store any personal data.';
$string['search:activity'] = 'External content';

$string['xapisetting'] = 'Default xAPI settings';
$string['xapienable'] = 'Enable xAPI LRS basic support';
$string['xapienableexplain'] = 'External Content items can be marked viewed and completed by an xAPI statement.';
$string['xapicompletionverbs'] = 'List of completion xAPI verbs';
$string['xapicompletionverbsexplain'] = 'This is a comma delimited list of xAPI verbs that when received will mark the content as completed externally.';
$string['xapiusername'] = 'xAPI LRS Username';
$string['xapiusernameexplain'] = 'The username for the xAPI functionality.';
$string['xapipassword'] = 'xAPI LRS Password';
$string['xapipasswordexplain'] = 'The password for the xAPI functionality.';

$string['report'] = 'External content report';
$string['summary'] = 'Summary';

$string['eventcompletedexternally'] = 'Completed externally';
$string['eventcompletedexternallydesc'] = 'The user with id {$a->userid} was marked completed from external source for the external content with course module id {$a->contextinstanceid}.';

$string['eventscoredexternally'] = 'Scored externally';
$string['eventscoredexternallydesc'] = 'The user with id {$a->userid} received a score {$a->score} from external source for the external content with course module id {$a->contextinstanceid}.';

$string['completionexternally'] = 'Student must be marked completed by external feed.';
