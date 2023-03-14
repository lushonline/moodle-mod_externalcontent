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
 * Tests for the External Content Importable Instance.
 *
 * @package     mod_externalcontent
 * @copyright   2019-2023 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \mod_externalcontent\importableinstance_test
 */
class importableinstance_test extends advanced_testcase {

    /**
     * Creates an instance from an importrecord.
     * @covers ::get_from_importrecord
     */
    public function test_get_from_importrecord_create(): void {
        $this->resetAfterTest();
        $importrecord = $this->get_test_importrecord();

        $instance = importableinstance::get_from_importrecord($importrecord);

        $this->assertInstanceOf(instance::class, $instance);
        $this->assertEquals($importrecord->get_courseimport()->idnumber, $instance->get_course_idnumber());
    }

    /**
     * Updates an instance from an importrecord.
     * @covers ::get_from_importrecord
     */
    public function test_get_from_importrecord_update(): void {
        $this->resetAfterTest();

        $importrecord = $this->get_test_importrecord();

        $instance = importableinstance::get_from_importrecord($importrecord);

        $this->assertInstanceOf(instance::class, $instance);
        $this->assertEquals($importrecord->get_courseimport()->fullname, $instance->get_course_var('fullname'));
    }

    /**
     * Get test import record
     *
     * @return importrecord
     */
    protected function get_test_importrecord(): importrecord {

        $courseimport = new \stdClass();
        $courseimport->idnumber = 'test-idnumber';
        $courseimport->shortname = 'Course Short Name';
        $courseimport->fullname = 'Course Full Name';
        $courseimport->category = 1;

        $moduleimport = new \stdClass();
        $moduleimport->name = 'External Content Name';
        $moduleimport->intro = 'External Content Introduction';
        $moduleimport->content = 'External Content Content';

        return new importrecord($courseimport, $moduleimport);
    }

}
