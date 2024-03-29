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
 * @copyright   2019-2023 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_externalcontent;

/**
 * PHPUnit data generator testcase
 *
 * @package     mod_externalcontent
 * @copyright   2019-2023 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_externalcontent_generator
 */
class generator_test extends \advanced_testcase {
    /**
     * Test generator can create externalcontent activities
     * @return void
     * @covers \mod_externalcontent_generator::create_instance
     */
    public function test_generator() {
        global $DB, $SITE;

        $this->resetAfterTest(true);

        $this->assertEquals(0, $DB->count_records('externalcontent'));

        /** @var mod_externalcontent_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_externalcontent');
        $this->assertInstanceOf('mod_externalcontent_generator', $generator);
        $this->assertEquals('externalcontent', $generator->get_modulename());

        $generator->create_instance(array('course' => $SITE->id));
        $generator->create_instance(array('course' => $SITE->id));
        $externalcontent = $generator->create_instance(array('course' => $SITE->id));
        $this->assertEquals(3, $DB->count_records('externalcontent'));

        $cm = get_coursemodule_from_instance('externalcontent', $externalcontent->id);
        $this->assertEquals($externalcontent->id, $cm->instance);
        $this->assertEquals('externalcontent', $cm->modname);
        $this->assertEquals($SITE->id, $cm->course);

        $context = \context_module::instance($cm->id);
        $this->assertEquals($externalcontent->cmid, $context->instanceid);
    }
}
