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
 * Simple list notification.
 */
abstract class simplelist extends base
{
    /**
     * Default to false for lists.
     */
    public function is_dismissble() {
        return false;
    }

    /**
     * Add an item to the list.
     * @param $key
     * @param $data
     */
    public function add_item($key, $data) {
        if (!isset($this->other['items'])) {
            $this->other['items'] = array();
        }

        $this->other['items'][$key] = $data;
    }

    /**
     * Retrieve an item from the list.
     * @param $key
     * @return null
     */
    public function get_item($key) {
        $items = $this->get_items();
        return isset($items[$key]) ? $items[$key] : null;
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
     * Cached get_items.
     * Internal use only.
     */
    private function _get_items() {
        static $items = null;
        if (!$items) {
            $items = $this->get_items();
        }

        return $items;
    }

    /**
     * Remove a given item.
     * @param $key
     * @throws \moodle_exception
     */
    public function remove_item($key) {
        if (!$this->get_item($key)) {
            throw new \moodle_exception("Invalid list notification item key.");
        }

        unset($this->other['items'][$key]);
    }

    /**
     * Returns the number of "actions" of the notification.
     */
    public function get_actions() {
        return count($this->_get_items());
    }

    /**
     * Returns any action buttons associated with this notification.
     */
    protected function get_action_icons() {
        $icons = parent::get_action_icons();
        if (empty($this->_get_items())) {
            return $icons;
        }

        $icon = '<i class="fa fa-chevron-down" aria-hidden="true"></i>';
        $icons .= \html_writer::link("#notification{$this->id}", $icon, array(
            'class' => 'alert-link alert-dropdown collapsed close',
            'data-toggle' => 'collapse',
            'aria-expanded' => 'false',
            'aria-controls' => "notification{$this->id}"
        ));

        return $icons;
    }

    /**
     * Returns the notification.
     */
    protected final function get_contents() {
        // Process items.
        $items = array();
        foreach ($this->_get_items() as $item) {
            $items[] = $this->render_item($item);
        }

        if (!empty($items)) {
            $items = \html_writer::alist($items, array(
                'class' => 'list'
            ));

            $items = \html_writer::div($items, 'collapse alert-dropdown-container', array('id' => "notification{$this->id}"));
        } else {
            $items = '';
        }

        $text = $this->render_text();
        if (!$text) {
            return null;
        }

        return $text . $items;
    }

    /**
     * Returns some text (before the items).
     */
    protected abstract function render_text();

    /**
     * Returns a rendered item.
     * @param $item
     * @return
     */
    protected abstract function render_item($item);

    /**
     * Checks custom data.
     * @param $data
     */
    protected function set_custom_data($data) {
        if (empty($data['items'])) {
            debugging('You have not set any items.');
        }

        parent::set_custom_data($data);
    }
}
