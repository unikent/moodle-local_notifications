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
 * Local stuff for Moodle Kent
 *
 * @package    local_notifications
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_notifications\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

class arbitrary extends \moodleform
{
    /**
     * Form definition
     */
    public function definition() {
        $mform =& $this->_form;
        $mform->addElement('text', 'courseid', 'Course ID');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('select', 'type', 'Type', array(
            \local_notifications\notification\base::LEVEL_SUCCESS => 'Success',
            \local_notifications\notification\base::LEVEL_INFO => 'Info',
            \local_notifications\notification\base::LEVEL_WARNING => 'Warning',
            \local_notifications\notification\base::LEVEL_DANGER => 'Danger'
        ));

        $mform->addElement('textarea', 'message', 'Message', array(
            'rows' => 10
        ));
        $mform->setType('message', PARAM_RAW);

        $mform->addElement('text', 'actions', 'Actions');
        $mform->setDefault('actions', '0');
        $mform->setType('actions', PARAM_INT);

        $mform->addElement('checkbox', 'dismissable', 'Dismissable');

        $this->add_action_buttons(true);
    }

    /**
     * Set default.
     * @param $field
     * @param int $val
     */
    public function set_field_default($field, $val = 0) {
        $mform =& $this->_form;
        $mform->setDefault($field, $val);
    }
}
