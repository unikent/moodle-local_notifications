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
 * Local stuff for Moodle Notifications
 *
 * @package    local_notifications
 * @copyright  2015 Skylar Kelty <S.Kelty@kent.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$services = array(
    'Notifications service' => array(
        'functions' => array (
            'local_notifications_dismiss',
            'local_notifications_expanded_toggle'
        ),
        'requiredcapability' => '',
        'restrictedusers' => 0,
        'enabled' => 1
    )
);

$functions = array(
    'local_notifications_dismiss' => array(
        'classname'   => 'local_notifications\external\notification',
        'methodname'  => 'dismiss',
        'description' => 'Dismiss a notification.',
        'type'        => 'write',
        'ajax'        => true
    ),
    'local_notifications_expanded_toggle' => array(
        'classname'   => 'local_notifications\external\notification',
        'methodname'  => 'expanded_toggle',
        'description' => 'Toggle expansion of a notification.',
        'type'        => 'write',
        'ajax'        => true
    )
);
