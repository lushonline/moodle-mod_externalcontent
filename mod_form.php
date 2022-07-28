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
 * The main mod_externalcontent configuration form.
 *
 * @package     mod_externalcontent
 * @copyright   2019-2022 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/externalcontent/locallib.php');

/**
 * Module instance settings form.
 *
 * @package    mod_externalcontent
 * @copyright  2019-2022 LushOnline
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_externalcontent_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        $config = get_config('externalcontent');

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('name'), array('size' => '48'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        // Add content editor.
        $mform->addElement('header', 'contentsection', get_string('contentheader', 'externalcontent'));
        $mform->addElement('editor', 'externalcontent', get_string('content', 'externalcontent'), null,
                            externalcontent_get_editor_options($this->context));
        $mform->addRule('externalcontent', get_string('required'), 'required', null, 'client');

        // Add display settings.
        $mform->addElement('header', 'appearancehdr', get_string('appearance'));
        $mform->addElement('advcheckbox', 'printintro', get_string('printintro', 'externalcontent'));
        $mform->setDefault('printintro', $config->printintro);
        $mform->addElement('advcheckbox', 'printlastmodified', get_string('printlastmodified', 'externalcontent'));
        $mform->setDefault('printlastmodified', $config->printlastmodified);

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    /**
     * Enforce defaults here.
     *
     * @param array $defaultvalues Form defaults
     * @return void
     **/
    public function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);

        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('externalcontent');
            $defaultvalues['externalcontent']['format'] = $defaultvalues['contentformat'];
            $defaultvalues['externalcontent']['text']   = file_prepare_draft_area($draftitemid, $this->context->id,
                    'mod_externalcontent', 'content', 0, externalcontent_get_editor_options($this->context),
                    $defaultvalues['content']);
            $defaultvalues['externalcontent']['itemid'] = $draftitemid;
        }

        if (!empty($defaultvalues['displayoptions'])) {
            $displayoptions = unserialize($defaultvalues['displayoptions']);
            if (isset($displayoptions['printintro'])) {
                $defaultvalues['printintro'] = $displayoptions['printintro'];
            }
            if (isset($displayoptions['printlastmodified'])) {
                $defaultvalues['printlastmodified'] = $displayoptions['printlastmodified'];
            }
        }

        // Set up the completion checkboxes which aren't part of standard data.
        // We also make the default value (if you turn on the checkbox) for those
        // numbers to be 1, this will not apply unless checkbox is ticked.
        $defaultvalues['completionexternallyenabled'] = !empty($defaultvalues['completionexternally']) ? 1 : 0;
        if (empty($defaultvalues['completionexternally'])) {
            $defaultvalues['completionexternally'] = 0;
        }
    }

    /**
     * Allows module to modify the data returned by form get_data().
     * This method is also called in the bulk activity completion form.
     *
     * Only available on moodleform_mod.
     *
     * @param stdClass $data the form data to be modified.
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);
        if (!empty($data->completionunlocked)) {
            // Turn off completion settings if the checkboxes aren't ticked.
            $autocompletion = !empty($data->completion) &&
                $data->completion == COMPLETION_TRACKING_AUTOMATIC;
            if (!$autocompletion || empty($data->completionexternally)) {
                $data->completionexternally = 0;
            }
        }
    }

    /**
     * Add completion rules to form.
     * @return array
     */
    public function add_completion_rules() {
        $mform =& $this->_form;

        $mform->addElement('checkbox', 'completionexternally', '', get_string('completionexternally', 'externalcontent'));
        // Enable this completion rule by default.
        $mform->setDefault('completionexternally', 0);

        return array('completionexternally');
    }

    /**
     * Enable completion rules
     * @param stdclass $data
     * @return array
     */
    public function completion_rule_enabled($data) {
        $completion = !empty($data['completionexternally']);
        return $completion;
    }

}
