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

namespace local_notifications\notification;

defined('MOODLE_INTERNAL') || die();

class manualguest extends base {
    /**
     * Returns the component of the notification.
     */
    public static function get_component() {
        return 'local_notifications';
    }

    /**
     * Returns the table name the objectid relates to.
     */
    public static function get_table() {
        return 'course';
    }

    /**
     * Returns the level of the notification.
     */
    public function get_level() {
        return base::LEVEL_WARNING;
    }

    /**
     * Is the action dismissable?
     */
    public function is_dismissble() {
        return true;
    }

    /**
     * Returns the notification.
     */
    protected function get_contents() {
        global $DB;

        // Is guest access enabled?
        $instance = $DB->get_record('enrol', array(
            'courseid' => $this->objectid,
            'enrol' => 'guest',
            'status' => \ENROL_INSTANCE_ENABLED
        ));
        if (!$instance) {
            return;
        }

        $url = new \moodle_url('/enrol/instances.php', array(
            'sesskey' => sesskey(),
            'id' => $this->objectid,
            'action' => 'disable',
            'instance' => $instance->id
        ));
        $link = \html_writer::link($url, 'Disable guest access.', array('class' => 'alert-link'));
        return 'You have guest access enabled. ' . $link;
    }
}
