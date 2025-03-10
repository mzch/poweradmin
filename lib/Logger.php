<?php

/*  Poweradmin, a friendly web-based admin tool for PowerDNS.
 *  See <https://www.poweradmin.org> for more details.
 *
 *  Copyright 2007-2009  Rejo Zenger <rejo@zenger.nl>
 *  Copyright 2010-2022  Poweradmin Development Team
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Poweradmin;

class Logger
{
    private static function do_log($message, $priority, $zone_id = NULL)
    {
        global $syslog_use, $syslog_ident, $syslog_facility, $dblog_use;

        if ($syslog_use) {
            openlog($syslog_ident, LOG_PERROR, $syslog_facility);
            syslog($priority, $message);
            closelog();
        }

        if ($dblog_use) {
            // TODO: This distinction would be better handled with special type enum
            if ($zone_id) {
                DbZoneLogger::do_log($message, $zone_id, $priority);
            } else {
                DbUserLogger::do_log($message, $priority);
            }
        }
    }

    public static function log_error($message, $zone_id = NULL)
    {
        self::do_log($message, LOG_ERR, $zone_id);
    }

    public static function log_warn($message, $zone_id = NULL)
    {
        self::do_log($message, LOG_WARNING, $zone_id);
    }

    public static function log_notice($message)
    {
        self::do_log($message, LOG_NOTICE);
    }

    public static function log_info($message, $zone_id = NULL)
    {
        self::do_log($message, LOG_INFO, $zone_id);
    }
}