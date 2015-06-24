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

namespace local_notifications;

defined('MOODLE_INTERNAL') || die();

/**
 * Base notification.
 */
abstract class base
{
    const LEVEL_SUCCESS = 'alert-success';
    const LEVEL_INFO = 'alert-info';
    const LEVEL_WARNING = 'alert-warning';
    const LEVEL_DANGER = 'alert-danger';

    private $_id;
    private $_objectid;
    private $_context;
    private $_data;

    /**
     * Creates an instance of the notification.
     */
    public final static function instance($data) {
        $obj = new static();

        if (!isset($data['objectid'])) {
            throw new \coding_exception("You must set an objectid.");
        }

        if (!isset($data['context'])) {
            throw new \coding_exception("You must set a context.");
        }

        if (isset($data['id'])) {
            $obj->_id = $data['id'];
        }

        $obj->_objectid = $data['objectid'];
        $obj->_context = $data['context'];

        if (isset($data['other'])) {
            $obj->set_custom_data($data['other']);
        }

        return $obj;
    }

    /**
     * Returns the context.
     */
    public function get_context() {
        if (empty($this->_context)) {
            return null;
        }

        if (!is_object($this->_context)) {
            $this->_context = \context::instance_by_id($this->_context);
        }

        return $this->_context;
    }

    /**
     * Returns event data.
     */
    private function get_event_data() {
        // Create the event.
        $event = array(
            'objectid' => $this->_id,
            'context' => $this->get_context()
        );

        $table = $this->get_table();
        if ($table == 'course') {
            $event['courseid'] = $this->_objectid;
        }

        if ($table == 'user') {
            $event['relateduserid'] = $this->_objectid;
        }

        return $event;
    }

    /**
     * Save the notification to DB.
     */
    public function save() {
        global $DB;

        $context = $this->get_context();

        $record = new \stdClass();
        $record->classname = static::class;
        $record->contextid = $context->id;
        $record->objectid = $this->_objectid;
        $record->objecttable = $this->get_table();
        $record->data = serialize((object)$this->_data);

        $this->_id = $DB->insert_record('local_notifications', $record);

        // Create the event.
        $event = \local_notifications\event\notification_created::create($this->get_event_data());
        $event->trigger();
    }

    /**
     * Delete the notification.
     */
    public function delete() {
        global $DB;

        if (!isset($this->_id)) {
            throw new \coding_exception("Cannot delete a notification without an ID.");
        }

        $DB->update_record('local_notifications', array(
            'id' => $this->_id,
            'deleted' => 1
        ));

        $event = \local_notifications\event\notification_deleted::create($this->get_event_data());
        $event->trigger();
    }

    /**
     * Mark this as seen.
     */
    public function mark_seen($userid = null) {
        global $DB, $USER;

        if (!isset($this->_id)) {
            throw new \coding_exception("Cannot mark a notification without an ID.");
        }

        $userid = $userid ? $userid : $USER->id;

        $DB->insert_record('local_notifications_seen', array(
            'nid' => $this->_id,
            'userid' => $userid
        ));

        $event = \local_notifications\event\notification_seen::create(array(
            'objectid' => $this->id,
            'context' => \context_user::instance($userid),
            'relateduserid' => $userid
        ));
        $event->trigger();
    }

    /**
     * Returns the number of "actions" of the notification.
     */
    public function get_actions() {
        return 0;
    }

    /**
     * Is the action dismissable?
     */
    public function is_dismissble() {
        return false;
    }

    /**
     * Checks custom data.
     */
    public function set_custom_data($data) {
        $this->_data['other'] = (array)$data;
    }

    /**
     * Returns the component of the notification.
     */
    public abstract function get_component();

    /**
     * Returns the table name the objectid relates to.
     */
    public abstract function get_table();

    /**
     * Returns the level of the notification.
     */
    public abstract function get_level();

    /**
     * Returns the icon of the notification.
     */
    public abstract function get_icon();

    /**
     * Returns the notification.
     */
    public abstract function render();
}
