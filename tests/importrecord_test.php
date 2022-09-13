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
 * Tests for the External Content Import Record.
 *
 * @package     mod_externalcontent
 * @copyright   2019-2022 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_externalcontent\importrecord
 */
class importrecord_test extends advanced_testcase {

    /**
     * Get the courseimport record form importrecord
     * @covers \mod_externalcontent\importrecord::get_courseimport()
     */
    public function test_get_courseimport(): void {
        global $CFG;

        $this->resetAfterTest();

        $defaults = get_config('moodlecourse');

        $importrecord = new importrecord();
        $result = $importrecord->get_courseimport();

        // Check courseimport it is stdClass type.
        $this->assertInstanceOf(\stdClass::class, $result);

        // Check the null values.
        $this->assertNull($result->idnumber);
        $this->assertNull($result->shortname);
        $this->assertNull($result->fullname);
        $this->assertNull($result->summary);
        $this->assertNull($result->visible);
        $this->assertNull($result->thumbnail);
        $this->assertNull($result->category);

        // Check the default values are equal.
        $this->assertEquals($result->format, "singleactivity");
        $this->assertEquals($result->numsections, 0);
        $this->assertEquals($result->newsitems, 0);
        $this->assertEquals($result->showgrades, $defaults->showgrades);
        $this->assertEquals($result->showreports, $defaults->showreports);
        $this->assertEquals($result->showactivitydates, $defaults->showactivitydates);
        $this->assertEquals($result->activitytype, "externalcontent");
        $this->assertEquals($result->enablecompletion, 1);
        $this->assertEquals($result->summaryformat, 1);

        // Assert start time is less than or equal to time now.
        $this->assertLessThanOrEqual($result->startdate, time());

        // Confirm tags is array.
        $this->assertIsArray($result->tags);
    }

    /**
     * Get the moduleimport record from importrecord
     * @covers \mod_externalcontent\importrecord::get_moduleimport()
     */
    public function test_get_moduleimport(): void {
        $this->resetAfterTest();

        $importrecord = new importrecord();
        $moduleimport = $importrecord->get_moduleimport();

        // Check it is stdClass type.
        $this->assertInstanceOf(\stdClass::class, $moduleimport);

        // Check the null values.
        $this->assertNull($moduleimport->name);
        $this->assertNull($moduleimport->intro);
        $this->assertNull($moduleimport->content);

        // Check the default values are equal.
        $this->assertEquals($moduleimport->completionexternally, 0);
        $this->assertEquals($moduleimport->introformat, 1);
        $this->assertEquals($moduleimport->contentformat, 1);
        $this->assertEquals($moduleimport->completion, 2);
        $this->assertEquals($moduleimport->completionview, 1);
    }

    /**
     * The courseimport record updating
     * @covers \mod_externalcontent\importrecord::update_courseimport()
     */
    public function test_update_courseimport(): void {
        $this->resetAfterTest();

        $importrecord = new importrecord();
        $originalcourse = $importrecord->get_courseimport();

        $clonedcourse = clone $originalcourse;
        $clonedcourse->idnumber = "none empty string ".time();

        $importrecord->update_courseimport($clonedcourse);
        $result = $importrecord->get_courseimport();

        // Check the values have been updated.
        $this->assertEquals($result, $clonedcourse);
        $this->assertEquals($result->idnumber, $clonedcourse->idnumber);
    }

    /**
     * The courseimport record ignores values
     * @covers \mod_externalcontent\importrecord::update_courseimport()
     */
    public function test_update_courseimport_ignore(): void {
        $this->resetAfterTest();

        $importrecord = new importrecord();
        $originalcourse = $importrecord->get_courseimport();

        $clonedcourse = clone $originalcourse;
        $clonedcourse->ignored = "none empty string ".time();

        $importrecord->update_courseimport($clonedcourse);
        $result = $importrecord->get_courseimport();

        // Check the the values has been ignored.
        $this->assertEquals($result, $originalcourse);
        $this->assertObjectNotHasAttribute('ignored', $result);
    }

    /**
     * The courseimport record does not update readonly
     * @covers \mod_externalcontent\importrecord::update_courseimport()
     */
    public function test_update_courseimport_readonly(): void {
        $this->resetAfterTest();

        $importrecord = new importrecord();
        $originalcourse = $importrecord->get_courseimport();

        $clonedcourse = clone $originalcourse;
        $clonedcourse->activitytype = "none empty string ".time();
        $clonedcourse->summaryformat = "none empty string ".time();
        $clonedcourse->format = "none empty string ".time();
        $clonedcourse->numsections = "none empty string ".time();
        $clonedcourse->newsitems = "none empty string ".time();
        $clonedcourse->startdate = "none empty string ".time();
        $clonedcourse->activitytype = "none empty string ".time();
        $clonedcourse->enablecompletion = "none empty string ".time();

        $importrecord->update_courseimport($clonedcourse);
        $result = $importrecord->get_courseimport();

        // Check the courseimport was not updated.
        $this->assertEquals($result, $originalcourse);
    }

    /**
     * The moduleimport record updating
     * @covers \mod_externalcontent\importrecord::update_moduleimport()
     */
    public function test_update_moduleimport(): void {
        $this->resetAfterTest();

        $importrecord = new importrecord();
        $originalmodule = $importrecord->get_moduleimport();

        $clonedmodule = clone $originalmodule;
        $clonedmodule->name = "none empty string ".time();

        $importrecord->update_moduleimport($clonedmodule);
        $result = $importrecord->get_moduleimport();

        // Check the default values are equal.
        $this->assertEquals($result, $clonedmodule);
    }

    /**
     * The moduleimport record ignores values
     * @covers \mod_externalcontent\importrecord::update_moduleimport()
     */
    public function test_update_moduleimport_ignore(): void {
        $this->resetAfterTest();

        $importrecord = new importrecord();
        $originalmodule = $importrecord->get_moduleimport();

        $clonedmodule = clone $originalmodule;
        $clonedmodule->ignored = "none empty string ".time();

        $importrecord->update_moduleimport($clonedmodule);
        $result = $importrecord->get_moduleimport();

        // Check the the values has been ignored.
        $this->assertEquals($result, $originalmodule);
        $this->assertObjectNotHasAttribute('ignored', $result);
    }

    /**
     * The moduleimport record does not update readonly
     * @covers \mod_externalcontent\importrecord::update_moduleimport()
     */
    public function test_update_moduleimport_readonly(): void {
        $this->resetAfterTest();

        $importrecord = new importrecord();
        $originalmodule = $importrecord->get_moduleimport();

        $clonedmodule = clone $originalmodule;
        $clonedmodule->introformat = "none empty string ".time();
        $clonedmodule->contentformat = "none empty string ".time();
        $clonedmodule->completion = "none empty string ".time();
        $clonedmodule->completionview = "none empty string ".time();

        $importrecord->update_moduleimport($clonedmodule);
        $result = $importrecord->get_moduleimport();

        // Check the moduleimport was not updated.
        $this->assertEquals($result, $originalmodule);
    }

    /**
     * Test validate fails
     * @covers \mod_externalcontent\importrecord::validate()
     */
    public function test_validate_fail(): void {
        $this->resetAfterTest();

        $importrecord = new importrecord();

        // Check it successed.
        $this->assertFalse($importrecord->validate());
    }

    /**
     * Test validate passes
     * @covers \mod_externalcontent\importrecord::validate()
     */
    public function test_validate_pass(): void {
        $this->resetAfterTest();

        $importrecord = new importrecord();
        $courseimport = $importrecord->get_courseimport();
        $moduleimport = $importrecord->get_moduleimport();

        // Set the minimum values.
        $courseimport->idnumber = "none empty string";
        $courseimport->shortname = "none empty string";
        $courseimport->fullname = "none empty string";
        $courseimport->category = 0;
        $importrecord->update_courseimport($courseimport);

        $moduleimport->name = "none empty string";
        $moduleimport->intro = "none empty string";
        $moduleimport->content = "none empty string";
        $importrecord->update_moduleimport($moduleimport);

        // Check it succeeds.
        $this->assertTrue($importrecord->validate());
    }
}
