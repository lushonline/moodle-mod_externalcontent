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
 * @copyright   2019-2023 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_externalcontent;

use advanced_testcase;
use cm_info;
use coding_exception;
use mod_externalcontent\completion\custom_completion;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/completionlib.php');

/**
 * Class for unit testing mod_externalcontent/custom_completion.
 *
 * @package     mod_externalcontent
 * @category    external
 * @copyright   2019-2023 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \mod_externalcontent\completion\custom_completion
 */
class custom_completion_test extends advanced_testcase {

    /**
     * Data provider for get_state().
     *
     * @return array[]
     */
    public function get_state_provider(): array {
        return [
            'Undefined completion requirement' => [
                'somenonexistentrule', COMPLETION_ENABLED, 1, null, coding_exception::class
            ],
            'Completion Externally requirement not available' => [
                'completionexternally', COMPLETION_DISABLED, 1, null, moodle_exception::class
            ],
            'Completion Externally requirement, user has not completed externally' => [
                'completionexternally', 1, false, COMPLETION_INCOMPLETE, null
            ],
            'Completion Externally requirement, user has completed externally' => [
                'completionexternally', 1, true, COMPLETION_COMPLETE, null
            ],
        ];
    }

    /**
     * Test for get_state().
     *
     * @dataProvider get_state_provider
     * @param string $rule The custom completion condition.
     * @param int $rulevalue The custom completion rule value.
     * @param mixed $uservalue The database value returned when checking the rule for the user.
     * @param int|null $status Expected completion status for the rule.
     * @param string|null $exception Expected exception.
     * @covers \mod_externalcontent\completion\custom_completion::get_state
     */
    public function test_get_state(string $rule, int $rulevalue, $uservalue, ?int $status, ?string $exception) {
        global $DB;

        if (!is_null($exception)) {
            $this->expectException($exception);
        }

        // Custom completion rule data for cm_info::customdata.
        $customdataval = [
            'customcompletionrules' => [
                $rule => $rulevalue
            ]
        ];

        // Build a mock cm_info instance.
        $mockcminfo = $this->getMockBuilder(cm_info::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__get'])
            ->getMock();

        // Mock the return of the magic getter method when fetching the cm_info object's
        // customdata and instance values.
        $mockcminfo->expects($this->any())
            ->method('__get')
            ->will($this->returnValueMap([
                ['customdata', $customdataval],
                ['instance', 1],
            ]));

        if ($rule === 'completionexternally') {
            // Mock the DB call fetching user's end reached state.
            $DB = $this->createMock(get_class($DB));
            $DB->expects($this->atMost(1))
                ->method('record_exists')
                ->willReturn($uservalue);
        }

        $customcompletion = new custom_completion($mockcminfo, 2);

        $this->assertEquals($status, $customcompletion->get_state($rule));
    }

    /**
     * Test for get_defined_custom_rules().
     */
    public function test_get_defined_custom_rules() {
        $expectedrules = [
            'completionexternally',
        ];

        $definedrules = custom_completion::get_defined_custom_rules();
        $this->assertCount(1, $definedrules);

        foreach ($definedrules as $definedrule) {
            $this->assertContains($definedrule, $expectedrules);
        }
    }

    /**
     * Test for get_defined_custom_rule_descriptions().
     * @covers \mod_externalcontent\completion\custom_completion::get_defined_custom_rules
     */
    public function test_get_custom_rule_descriptions() {
        // Get defined custom rules.
        $rules = custom_completion::get_defined_custom_rules();

        // Build a mock cm_info instance.
        $mockcminfo = $this->getMockBuilder(cm_info::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__get'])
            ->getMock();

        // Instantiate a custom_completion object using the mocked cm_info.
        $customcompletion = new custom_completion($mockcminfo, 1);

        // Get custom rule descriptions.
        $ruledescriptions = $customcompletion->get_custom_rule_descriptions();

        // Confirm that defined rules and rule descriptions are consistent with each other.
        $this->assertEquals(count($rules), count($ruledescriptions));
        foreach ($rules as $rule) {
            $this->assertArrayHasKey($rule, $ruledescriptions);
        }
    }

    /**
     * Test for is_defined().
     * @covers \mod_externalcontent\completion\custom_completion
     */
    public function test_is_defined() {
        // Build a mock cm_info instance.
        $mockcminfo = $this->getMockBuilder(cm_info::class)
            ->disableOriginalConstructor()
            ->getMock();

        $customcompletion = new custom_completion($mockcminfo, 1);

        // All rules are defined.
        $this->assertTrue($customcompletion->is_defined('completionexternally'));

        // Undefined rule is not found.
        $this->assertFalse($customcompletion->is_defined('somerandomrule'));
    }

    /**
     * Data provider for test_get_available_custom_rules().
     *
     * @return array[]
     */
    public function get_available_custom_rules_provider(): array {
        return [
            'No completion conditions enabled' => [
                [
                    'completionexternally' => COMPLETION_DISABLED,
                ],
                [],
            ],
            'Completion end reached enabled only' => [
                [
                    'completionexternally' => COMPLETION_ENABLED,
                ],
                ['completionexternally'],
            ],
        ];
    }

    /**
     * Test for get_available_custom_rules().
     *
     * @dataProvider get_available_custom_rules_provider
     * @param array $values
     * @param array $expected
     * @covers \mod_externalcontent\completion\custom_completion::get_available_custom_rules
     */
    public function test_get_available_custom_rules(array $values, array $expected) {
        $rules = [
            'customcompletionrules' => $values,
        ];

        // Build a mock cm_info instance.
        $mockcminfo = $this->getMockBuilder(cm_info::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__get'])
            ->getMock();

        // Mock the return of magic getter for the customdata attribute.
        $mockcminfo->expects($this->any())
            ->method('__get')
            ->with('customdata')
            ->willReturn($rules);

        $customcompletion = new custom_completion($mockcminfo, 1);
        $this->assertEquals($expected, $customcompletion->get_available_custom_rules());
    }
}
