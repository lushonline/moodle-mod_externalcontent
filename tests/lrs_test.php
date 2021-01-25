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


    private function guidv4()
    {
        $data = random_bytes(16);
        // set version to 0100.
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // set bits 6-7 to 10.
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Test course and module viewed only
     */
    public function test_externalcontent_lrs_xapihelper_processstatement_viewed() {
        global $CFG;

        $CFG->enablecompletion = 1;
        $this->resetAfterTest();

        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();

        $xapiactivityid = 'https://xapi.com/xapi/course/'.self::guidv4();

        // Create the activity.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'idnumber' => $xapiactivityid));
        $externalcontent = $this->getDataGenerator()->create_module('externalcontent',
                              array('course' => $course->id, 'idnumber' => $xapiactivityid, 'completionexternally' => 1),
                              array('completion' => 2, 'completionview' => 1) );

        // Get some additional data.
        $context = context_module::instance($externalcontent->cmid);
        $cm = get_coursemodule_from_instance('externalcontent', $externalcontent->id);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        // Create xapistatement.
        $args = [
            'id' => '3f961635-9b58-4683-8e85-372d8c7c5b2a',
            'actor' => [
              'objectType' => 'Agent',
              'account' => [
                'homePage' => 'https://externalprovider.com',
                'name' => $user->username,
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
              'id' => $xapiactivityid,
              'definition' => [
                'name' => [
                  'en' => 'Test Activity',
                ],
                'type' => 'http://adlnet.gov/expapi/activities/course',
              ],
              'objectType' => 'Activity',
            ],
        ];

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        $statement = new TinCan\Statement($args);
        $payload = xapihelper::processstatement('1.0.0', $statement, true);

        $events = $sink->get_events();

        // Filter for course_module_viewed.
        $moduleviewedevents = array_filter($events, function($k) {
          return $k instanceof mod_externalcontent\event\course_module_viewed;
        });
        $this->assertCount(1, $moduleviewedevents);
        $moduleviewedevent = reset($moduleviewedevents);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_externalcontent\event\course_module_viewed', $moduleviewedevent);
        $this->assertEquals($context, $moduleviewedevent->get_context());
        $this->assertEventContextNotUsed($moduleviewedevent);
        $this->assertNotEmpty($moduleviewedevent->get_name());

        // Filter for course_module_scoredexternally.
        $modulescoredevents = array_filter($events, function($k) {
          return $k instanceof mod_externalcontent\event\course_module_scoredexternally;
        });

        // Checking that the event does not exist.
        $this->assertCount(0, $modulescoredevents);

        // Filter for course_module_scoredexternally.
        $modulecompletedevents = array_filter($events, function($k) {
          return $k instanceof mod_externalcontent\event\course_module_completedexternally;
        });
        // Checking that the event does not exist.
        $this->assertCount(0, $modulecompletedevents);
    }

    /**
     * Test course and module completed
     */
    public function test_externalcontent_lrs_xapihelper_processstatement_completed() {
      global $CFG;

      $CFG->enablecompletion = 1;
      $this->resetAfterTest();

      $this->setAdminUser();
      $user = $this->getDataGenerator()->create_user();

      $xapiactivityid = 'https://xapi.com/xapi/course/'.self::guidv4();

      // Create the activity.
      $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1, 'idnumber' => $xapiactivityid));
      $externalcontent = $this->getDataGenerator()->create_module('externalcontent',
                            array('course' => $course->id, 'idnumber' => $xapiactivityid, 'completionexternally' => 1),
                            array('completion' => 2, 'completionview' => 1) );

      // Get some additional data.
      $context = context_module::instance($externalcontent->cmid);
      $cm = get_coursemodule_from_instance('externalcontent', $externalcontent->id);

      // Trigger and capture the event.
      $sink = $this->redirectEvents();

      // Create xapistatement.
      $args = [
          'id' => '3f961635-9b58-4683-8e85-372d8c7c5b2a',
          'actor' => [
            'objectType' => 'Agent',
            'account' => [
              'homePage' => 'https://externalprovider.com',
              'name' => $user->username,
            ],
          ],
          'verb' => [
            'id' => 'http://adlnet.gov/expapi/verbs/completed',
            'display' => [
              'en' => 'completed',
            ],
          ],
          'result' => [
            'score' => [
              'raw' => 100,
              'min' => 0,
              'max' => 100,
            ],
            'success' => true,
            'completion' => true,
          ],
          'timestamp' => '2020-12-14T16:22:52.000Z',
          'version' => '1.0.0',
          'object' => [
            'id' => $xapiactivityid,
            'definition' => [
              'name' => [
                'en' => 'Test Activity',
              ],
              'type' => 'http://adlnet.gov/expapi/activities/course',
            ],
            'objectType' => 'Activity',
          ],
      ];

      // Trigger and capture the event.
      $sink = $this->redirectEvents();

      $statement = new TinCan\Statement($args);
      $payload = xapihelper::processstatement('1.0.0', $statement, true);

      $events = $sink->get_events();

      // Filter for course_module_viewed.
      $moduleviewedevents = array_filter($events, function($k) {
        return $k instanceof mod_externalcontent\event\course_module_viewed;
      });

      // Checking that the event exists.
      $this->assertCount(1, $moduleviewedevents);
      $moduleviewedevent = reset($moduleviewedevents);

      // Checking that the event contains the expected values.
      $this->assertInstanceOf('\mod_externalcontent\event\course_module_viewed', $moduleviewedevent);
      $this->assertEquals($context, $moduleviewedevent->get_context());
      $this->assertEventContextNotUsed($moduleviewedevent);
      $this->assertNotEmpty($moduleviewedevent->get_name());

      // Filter for course_module_scoredexternally.
      $modulescoredevents = array_filter($events, function($k) {
        return $k instanceof mod_externalcontent\event\course_module_scoredexternally;
      });

      // Checking that the event exists.
      $this->assertCount(1, $modulescoredevents);
      $modulescoredevent = reset($modulescoredevents);

      // Checking that the event contains the expected values.
      $this->assertInstanceOf('\mod_externalcontent\event\course_module_scoredexternally', $modulescoredevent);
      $this->assertEquals($context, $modulescoredevent->get_context());
      $this->assertEventContextNotUsed($modulescoredevent);
      $this->assertNotEmpty($modulescoredevent->get_name());

      // Filter for course_module_scoredexternally.
      $modulecompletedevents = array_filter($events, function($k) {
        return $k instanceof mod_externalcontent\event\course_module_completedexternally;
      });

      // Checking that the event exists.
      $this->assertCount(1, $modulecompletedevents);
      $modulecompletedevent = reset($modulecompletedevents);

      // Checking that the event contains the expected values.
      $this->assertInstanceOf('\mod_externalcontent\event\course_module_completedexternally', $modulecompletedevent);
      $this->assertEquals($context, $modulecompletedevent->get_context());
      $this->assertEventContextNotUsed($modulecompletedevent);
      $this->assertNotEmpty($modulecompletedevent->get_name());
  }

}
