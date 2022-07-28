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
 * @copyright   2019-2022 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_externalcontent;

/**
 * Unit tests for mod_externalcontent lib
 *
 * @package     mod_externalcontent
 * @category    external
 * @copyright   2019-2022 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lib_test extends \advanced_testcase {

    /**
     * Prepares things before this test case is initialised
     * @return void
     */
    public static function setUpBeforeClass() : void {
        global $CFG;
        require_once($CFG->dirroot.'/mod/externalcontent/lib.php');
    }

    /**
     * Check support
     *
     * @covers ::externalcontent_supports
     */
    public function test_externalcontent_supports() {
        $this->resetAfterTest();
        $this->assertTrue(externalcontent_supports(FEATURE_COMPLETION_TRACKS_VIEWS));
        $this->assertTrue(externalcontent_supports(FEATURE_COMPLETION_HAS_RULES));
        $this->assertTrue(externalcontent_supports(FEATURE_MOD_INTRO));
        $this->assertTrue(externalcontent_supports(FEATURE_BACKUP_MOODLE2));
        $this->assertTrue(externalcontent_supports(FEATURE_SHOW_DESCRIPTION));
        $this->assertFalse(externalcontent_supports(FEATURE_GRADE_HAS_GRADE));
        $this->assertFalse(externalcontent_supports(FEATURE_GRADE_OUTCOMES));
    }

    /**
     * Test externalcontent_view
     * @return void
     * @covers ::externalcontent_view
     */
    public function test_externalcontent_view() {
        global $CFG;

        $CFG->enablecompletion = 1;
        $this->resetAfterTest();

        // Setup test data.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $externalcontent = $this->getDataGenerator()->create_module('externalcontent', array('course' => $course->id),
                                                            array('completion' => 2, 'completionview' => 1));
        $context = \context_module::instance($externalcontent->cmid);
        $cm = get_coursemodule_from_instance('externalcontent', $externalcontent->id);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        $this->setAdminUser();
        externalcontent_view($externalcontent, $course, $cm, $context);

        $events = $sink->get_events();
        // 2 additional events thanks to completion.
        $this->assertCount(3, $events);
        $event = array_shift($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_externalcontent\event\course_module_viewed', $event);
        $this->assertEquals($context, $event->get_context());
        $moodleurl = new \moodle_url('/mod/externalcontent/view.php', array('id' => $cm->id));
        $this->assertEquals($moodleurl, $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());

        // Check completion status.
        $completion = new \completion_info($course);
        $completiondata = $completion->get_data($cm);
        $this->assertEquals(1, $completiondata->completionstate);
    }

    /**
     * Test mod_externalcontent_core_calendar_provide_event_action
     * @return void
     * @covers ::mod_externalcontent_core_calendar_provide_event_action
     */
    public function test_externalcontent_core_calendar_provide_event_action() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create the activity.
        $course = $this->getDataGenerator()->create_course();
        $externalcontent = $this->getDataGenerator()->create_module('externalcontent', array('course' => $course->id));

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $externalcontent->id,
            \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_externalcontent_core_calendar_provide_event_action($event, $factory);

        // Confirm the event was decorated.
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('view'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    /**
     * Test mod_externalcontent_core_calendar_provide_event_action when already completed
     * @return void
     * @covers ::mod_externalcontent_core_calendar_provide_event_action
     */
    public function test_externalcontent_core_calendar_provide_event_action_already_completed() {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();

        $CFG->enablecompletion = 1;

        // Create the activity.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $externalcontent = $this->getDataGenerator()->create_module('externalcontent', array('course' => $course->id),
            array('completion' => 2, 'completionview' => 1, 'completionexpected' => time() + DAYSECS));

        // Get some additional data.
        $cm = get_coursemodule_from_instance('externalcontent', $externalcontent->id);

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $externalcontent->id,
            \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);

        // Mark the activity as completed.
        $completion = new \completion_info($course);
        $completion->set_module_viewed($cm);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        // Decorate action event.
        $actionevent = mod_externalcontent_core_calendar_provide_event_action($event, $factory);

        // Ensure result was null.
        $this->assertNull($actionevent);
    }

    /**
     * Test mod_externalcontent_core_calendar_provide_event_action with user override
     * @return void
     * @covers ::mod_externalcontent_core_calendar_provide_event_action
     */
    public function test_externalcontent_core_calendar_provide_event_action_user_override() {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();
        $CFG->enablecompletion = 1;

        // Create the activity.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));
        $externalcontent = $this->getDataGenerator()->create_module('externalcontent', array('course' => $course->id),
            array('completion' => 2, 'completionview' => 1, 'completionexpected' => time() + DAYSECS));

        // Get some additional data.
        $cm = get_coursemodule_from_instance('externalcontent', $externalcontent->id);

        // Create a calendar event.
        $event = $this->create_action_event($course->id, $externalcontent->id,
            \core_completion\api::COMPLETION_EVENT_TYPE_DATE_COMPLETION_EXPECTED);

        // Mark the activity as completed.
        $completion = new \completion_info($course);
        $completion->set_module_viewed($cm);

        // Create an action factory.
        $factory = new \core_calendar\action_factory();

        mod_externalcontent_core_calendar_provide_event_action($event, $factory);

        // Decorate action with a userid override.
        $actionevent = mod_externalcontent_core_calendar_provide_event_action($event, $factory, $user->id);

        // Confirm the event was decorated.
        $this->assertNotNull($actionevent);
        $this->assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        $this->assertEquals(get_string('view'), $actionevent->get_name());
        $this->assertInstanceOf('moodle_url', $actionevent->get_url());
        $this->assertEquals(1, $actionevent->get_item_count());
        $this->assertTrue($actionevent->is_actionable());
    }

    /**
     * Creates an action event.
     *
     * @param int $courseid The course id.
     * @param int $instanceid The instance id.
     * @param string $eventtype The event type.
     * @return bool|calendar_event
     */
    private function create_action_event($courseid, $instanceid, $eventtype) {
        $event = new \stdClass();
        $event->name = 'Calendar event';
        $event->modulename  = 'externalcontent';
        $event->courseid = $courseid;
        $event->instance = $instanceid;
        $event->type = CALENDAR_EVENT_TYPE_ACTION;
        $event->eventtype = $eventtype;
        $event->timestart = time();

        return \calendar_event::create($event);
    }
}
