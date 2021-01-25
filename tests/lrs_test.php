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
 * Library of interface functions and constants.
 *
 * @package     mod_externalcontent
 * @category    external
 * @copyright   2019-2021 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/externalcontent/lib.php');
require($CFG->dirroot . '/mod/externalcontent/lrs/vendor/autoload.php');
require($CFG->dirroot . '/mod/externalcontent/lrs/xapihelper.php');

/**
 * Unit tests for mod_externalcontent lrs xapihelper
 *
 * @package     mod_externalcontent
 * @category    external
 * @copyright   2019-2021 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_externalcontent_lrs_testcase extends advanced_testcase {

    /**
     * Return an incomplete xapi statement
     * @return TinCan\Statement The statement
     */
    private function get_incomplete_statement($username, $activityid) {
        $incomplete = [
            'id' => '3f961635-9b58-4683-8e85-372d8c7c5b2a',
            'actor' => [
                'objectType' => 'Agent',
                'account' => [
                'homePage' => 'https://externalprovider.com',
                'name' => $username,
                ],
            ],
            'verb' => [
                'id' => 'http://adlnet.gov/expapi/verbs/experienced',
                'display' => [
                'en' => 'experienced',
                ],
            ],
            'timestamp' => '2020-12-14T16:22:52.000Z',
            'version' => '1.0.0',
            'object' => [
                'id' => $activityid,
                'definition' => [
                'name' => [
                    'en' => 'Test Activity',
                ],
                'type' => 'http://adlnet.gov/expapi/activities/course',
                ],
                'objectType' => 'Activity',
            ],
        ];
        return new TinCan\Statement($incomplete);
    }

    /**
     * Return a completed xapi statement
     * @return TinCan\Statement The statement
     */
    private function get_completed_statement($username, $activityid) {
        $complete = [
            'id' => '3f961635-9b58-4683-8e85-372d8c7c5b2a',
            'actor' => [
                'objectType' => 'Agent',
                'account' => [
                'homePage' => 'https://externalprovider.com',
                'name' => $username,
                ],
            ],
            'verb' => [
                'id' => 'http://adlnet.gov/expapi/verbs/completed',
                'display' => [
                'en' => 'completed',
                ],
            ],
            'result' => [
                'success' => true,
                'completion' => true,
            ],
            'timestamp' => '2020-12-14T16:22:52.000Z',
            'version' => '1.0.0',
            'object' => [
                'id' => $activityid,
                'definition' => [
                'name' => [
                    'en' => 'Test Activity',
                ],
                'type' => 'http://adlnet.gov/expapi/activities/course',
                ],
                'objectType' => 'Activity',
            ],
        ];
        return new TinCan\Statement($complete);
    }

    /**
     * Return a scored xapi statement
     * @return TinCan\Statement The statement
     */
    private function get_scored_statement($username, $activityid) {
        $scored = [
            'id' => '3f961635-9b58-4683-8e85-372d8c7c5b2a',
            'actor' => [
                'objectType' => 'Agent',
                'account' => [
                'homePage' => 'https://externalprovider.com',
                'name' => $username,
                ],
            ],
            'verb' => [
                'id' => 'http://adlnet.gov/expapi/verbs/experienced',
                'display' => [
                'en' => 'experienced',
                ],
            ],
            'result' => [
                'score' => [
                'raw' => 50,
                'min' => 0,
                'max' => 100,
                ],
            ],
            'timestamp' => '2020-12-14T16:22:52.000Z',
            'version' => '1.0.0',
            'object' => [
                'id' => $activityid,
                'definition' => [
                'name' => [
                    'en' => 'Test Activity',
                ],
                'type' => 'http://adlnet.gov/expapi/activities/course',
                ],
                'objectType' => 'Activity',
            ],
        ];
        return new TinCan\Statement($scored);
    }

    /**
     * Return a guid
     * @return string The string representation of a GUIDv4
     */
    private function guidv4() {
        $data = random_bytes(16);
        // Set version to 0100.
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10.
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Return LRS events object or null
     * @return object The lrs event objects
     */
    private function get_lrs_events($events) {
        $lrsevents = new \stdClass;
        $lrsevents->viewed = Array();
        $lrsevents->scored = Array();
        $lrsevents->completed = Array();

        // Filter for course_module_viewed.
        $moduleviewedevents = array_filter($events, function($k) {
            return $k instanceof mod_externalcontent\event\course_module_viewed;
        });
        $lrsevents->viewed = $moduleviewedevents;

        // Filter for course_module_scoredexternally.
        $modulescoredevents = array_filter($events, function($k) {
            return $k instanceof mod_externalcontent\event\course_module_scoredexternally;
        });
        $lrsevents->scored = $modulescoredevents;

        $modulecompletedevents = array_filter($events, function($k) {
            return $k instanceof mod_externalcontent\event\course_module_completedexternally;
        });
        $lrsevents->completed = $modulecompletedevents;
        return $lrsevents;
    }

    /**
     * Set up for every test
     */
    public function setUp(): void {
        global $CFG;

        $CFG->enablecompletion = 1;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Create users.
        $this->user = self::getDataGenerator()->create_user();

        // Setup XAPI Activity Id.
        $this->xapiactivityid = 'https://xapi.com/xapi/course/'.self::guidv4();

        // Setup test data.
        $this->course = $this->getDataGenerator()->create_course(
                                array('enablecompletion' => 1, 'idnumber' => $this->xapiactivityid) );
        $this->externalcontent = $this->getDataGenerator()->create_module('externalcontent',
                              array('course' => $this->course->id, 'idnumber' => $this->xapiactivityid, 'completionexternally' => 1),
                              array('completion' => 2, 'completionview' => 1) );

        $this->context = context_module::instance($this->externalcontent->cmid);
        $this->cm = get_coursemodule_from_instance('externalcontent', $this->externalcontent->id);
    }

    /**
     * Test course and module viewed only
     * @return void
     */
    public function test_externalcontent_lrs_xapihelper_processstatement_viewed() {
        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        $statement = self::get_incomplete_statement($this->user->username, $this->xapiactivityid);
        $payload = xapihelper::processstatement('1.0.0', $statement, true);

        $events = $sink->get_events();

        $lrsevents = self::get_lrs_events($events);
        $this->assertCount(1, $lrsevents->viewed);
        $moduleviewedevent = reset($lrsevents->viewed);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_externalcontent\event\course_module_viewed', $moduleviewedevent);
        $this->assertEquals($this->context, $moduleviewedevent->get_context());
        $this->assertEventContextNotUsed($moduleviewedevent);
        $this->assertNotEmpty($moduleviewedevent->get_name());
    }

    /**
     * Test course and module scored
     * @return void
     */
    public function test_externalcontent_lrs_xapihelper_processstatement_scored() {
        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        $statement = self::get_scored_statement($this->user->username, $this->xapiactivityid);
        $payload = xapihelper::processstatement('1.0.0', $statement, true);

        $events = $sink->get_events();

        $lrsevents = self::get_lrs_events($events);

        // Checking that the event exists.
        $this->assertCount(1, $lrsevents->scored);
        $modulescoredevent = reset($lrsevents->scored);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_externalcontent\event\course_module_scoredexternally', $modulescoredevent);
        $this->assertEquals($this->context, $modulescoredevent->get_context());
        $this->assertEventContextNotUsed($modulescoredevent);
        $this->assertNotEmpty($modulescoredevent->get_name());

    }

    /**
     * Test course and module completed
     * @return void
     */
    public function test_externalcontent_lrs_xapihelper_processstatement_completed() {
        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        $statement = self::get_completed_statement($this->user->username, $this->xapiactivityid);
        $payload = xapihelper::processstatement('1.0.0', $statement, true);

        $events = $sink->get_events();

        $lrsevents = self::get_lrs_events($events);

        // Checking that the event exists.
        $this->assertCount(1, $lrsevents->completed);
        $modulecompletedevent = reset($lrsevents->completed);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_externalcontent\event\course_module_completedexternally', $modulecompletedevent);
        $this->assertEquals($this->context, $modulecompletedevent->get_context());
        $this->assertEventContextNotUsed($modulecompletedevent);
        $this->assertNotEmpty($modulecompletedevent->get_name());
    }

}
