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
 * Observers
 */
class observers
{
    /**
     * Clears notifications.
     * @param \core\event\base $event
     */
    public static function clear_notifications(\core\event\base $event) {
        // Delete any notifications.
        $notifications = core::get_notifications($event->objectid);
        foreach ($notifications as $notification) {
            $notification->purge();
        }
    }
}
