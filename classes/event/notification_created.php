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

namespace local_notifications\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event Class
 */
class notification_created extends \core\event\base
{
    /**
     * Init method.
     */
    protected function init() {
        $this->data['objecttable'] = 'course_notifications';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return "Notification Created";
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return s($this->other['contextid']) . '/' . s($this->other['extref']) . ' notified course.';
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['contextid'])) {
            throw new \coding_exception('The \'contextid\' must be set.');
        }

        if (!isset($this->other['extref'])) {
            throw new \coding_exception('The \'extref\' must be set.');
        }
    }
}