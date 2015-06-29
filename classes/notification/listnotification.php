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

/**
 * Base notification.
 */
abstract class listnotification extends base
{
    /**
     * Add an item to the list.
     */
    public function add_item($key, $data) {
        if (!isset($this->other['items'])) {
            $this->other['items'] = array();
        }

        $this->other['items'][$key] = $data;
    }

    /**
     * Retrieve an item from the list.
     */
    public function get_item($key) {
        if (!isset($this->other['items'])) {
            return null;
        }

        return $this->other['items'][$key];
    }

    /**
     * Retrieve all items from the list.
     */
    public function get_items() {
        if (!isset($this->other['items'])) {
            return array();
        }

        return $this->other['items'];
    }

    /**
     * Remove a given item.
     */
    public function remove_item($key) {
        if (!$this->get_item($k)) {
            throw new \moodle_exception("Invalid list notification item key.");
        }

        unset($this->other['items'][$k]);
    }

    /**
     * Returns the number of "actions" of the notification.
     */
    public function get_actions() {
        return count($this->get_items());
    }

    /**
     * Returns the notification.
     */
    public function render() {
        $items = array();
        foreach ($this->get_items() as $item) {
            $items[] = $this->render_item($item);
        }
        return \html_writer::alist($items, array(
            'class' => 'list'
        ));
    }

    /**
     * Returns a rendered item.
     */
    public abstract function render_item($item);

    /**
     * Checks custom data.
     */
    public function set_custom_data($data) {
        if (empty($data['items'])) {
            debugging('You have not set any items.');
        }

        parent::set_custom_data($data);
    }
}