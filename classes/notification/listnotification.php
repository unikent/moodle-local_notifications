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
     * For now, we don't support dismissable list notifications.
     * A list implies an action anyway - so this kinda makes sense.
     */
    public final function is_dismissble() {
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
        return count($this->get_items());
    }

    /**
     * Returns the notification.
     */
    protected final function get_contents() {
        $items = array();
        foreach ($this->get_items() as $item) {
            $items[] = $this->render_item($item);
        }

        $items = \html_writer::alist($items, array(
            'class' => 'list'
        ));

        $text = $this->render_text();
        if (!$text) {
            return null;
        }

        return <<<HTML5
        <a class="alert-link alert-dropdown collapsed close" role="button" data-toggle="collapse" href="#notification{$this->id}" aria-expanded="false" aria-controls="notification{$this->id}">
            <i class="fa fa-chevron-down"></i>
        </a>
        {$text}
        <div id="notification{$this->id}" class="collapse alert-dropdown-container">
        {$items}
        </div>
HTML5;
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