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
namespace mod_externalcontent;

use advanced_testcase;
use moodle_exception;

/**
 * Tests for the External Content Instance.
 *
 * @package     mod_externalcontent
 * @copyright   2019-2023 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_externalcontent\instance
 */
class instance_test extends advanced_testcase {

    /**
     * Get an instance from a cmid.
     * @covers ::get_from_cmid
     */
    public function test_get_from_cmid(): void {
        $this->resetAfterTest();

        [
            'module' => $module,
            'cm' => $cm,
        ] = $this->get_test_instance();

        $instance = instance::get_from_cmid($cm->id);

        $this->assertInstanceOf(instance::class, $instance);
        $this->assertEquals($module->id, $instance->get_module_id());
        $this->assertEquals($cm->id, $instance->get_cm()->id);
    }

    /**
     * Get from id number
     *
     * @covers ::get_from_cmidnumber
     */
    public function test_get_from_cmidnumber(): void {
        $this->resetAfterTest();

        [
            'module' => $module,
            'cm' => $cm,
        ] = $this->get_test_instance();

        $instance = instance::get_from_cmidnumber($cm->idnumber);

        $this->assertInstanceOf(instance::class, $instance);
        $this->assertEquals($module->id, $instance->get_module_id());
        $this->assertEquals($module->cmid, $instance->get_cm_id());
        $this->assertEquals($module->cmid, $instance->get_cm()->id);
    }

    /**
     * If the instance was not found, and exception should be thrown.
     * @covers ::get_from_cmid
     */
    public function test_get_from_cmid_not_found(): void {
        $this->assertNull(instance::get_from_cmid(100));
    }

    /**
     * If the instance was not found, and exception should be thrown.
     * @covers ::get_from_moduleid
     */
    public function test_get_from_instance_not_found(): void {
        $this->assertNull(instance::get_from_moduleid(100));
    }

    /**
     * If the instance was not found, and exception should be thrown.
     * @covers ::get_from_cmidnumber
     */
    public function test_get_from_cmidnumber_not_found(): void {
        $this->assertNull(instance::get_from_cmidnumber('none-existent'));
    }

    /**
     * Test the get_all_instances_in_course function.
     *
     * @covers ::get_all_instances_in_course
     */
    public function test_get_all_instances_in_course(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $modules = [];
        for ($i = 0; $i < 5; $i++) {
            $modules[] = $this->getDataGenerator()->create_module('externalcontent', [
                'course' => $course->id,
            ]);
        }

        $instances = instance::get_all_modules_in_course($course->id);
        $this->assertCount(5, $instances);
        foreach ($instances as $instance) {
            $this->assertInstanceOf(instance::class, $instance);
        }
    }

    /**
     * Get test instance from data
     *
     * @param array $coursedata The extra course data.
     * @param array $courseoptions The extra course options data.
     * @param array $moduledata The extra external content data.
     * @param array $moduleoptions The extra module options data.
     * @return array
     */
    protected function get_test_instance(array $coursedata = [],
                                         array $courseoptions = [],
                                         array $moduledata = [],
                                         array $moduleoptions = []): array {

        $defaultcoursedata = array('idnumber' => 'test-idnumber');
        $defaultcourseopts = array('idnumber' => 'test-idnumber');
        $defaultmoddata = array('idnumber' => 'test-idnumber');
        $defaultmodopts = array('idnumber' => 'test-idnumber');

        $course = $this->getDataGenerator()->create_course(
            array_merge($defaultcoursedata, $coursedata),
            array_merge($defaultcourseopts, $courseoptions)
        );

        $module = $this->getDataGenerator()->create_module('externalcontent',
            array_merge(array('course' => $course->id), $defaultmoddata, $moduledata),
            array_merge($defaultmodopts, $moduleoptions)
        );

        $cm = get_fast_modinfo($course)->instances['externalcontent'][$module->id];

        return [
            'course' => $course,
            'module' => $module,
            'cm' => $cm,
        ];
    }
}
