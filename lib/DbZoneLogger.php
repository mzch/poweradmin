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

class DbZoneLogger
{
    public static function do_log($msg, $zone_id, $priority): void
    {
        global $db;
        $stmt = $db->prepare('INSERT INTO log_zones (zone_id, event, priority) VALUES (:zone_id, :msg, :priority)');
        $stmt->execute([
            ':msg' => $msg,
            ':zone_id' => $zone_id,
            ':priority' => $priority,
        ]);
    }

    public static function count_all_logs()
    {
        global $db;
        $stmt = $db->query("SELECT count(*) AS number_of_logs FROM log_zones");
        return $stmt->fetch()['number_of_logs'];
    }

    public static function count_logs_by_domain($domain)
    {
        global $db;
        $stmt = $db->prepare("
                    SELECT count(domains.id) as number_of_logs
                    FROM log_zones
                    INNER JOIN domains 
                    ON domains.id = log_zones.zone_id
                    WHERE domains.name LIKE :search_by
        ");
        $name = "%$domain%";
        $stmt->execute(['search_by' => $name]);
        return $stmt->fetch()['number_of_logs'];
    }

    public static function get_all_logs($limit, $offset)
    {
        global $db;
        $stmt = $db->prepare("
                    SELECT * FROM log_zones
                    ORDER BY created_at DESC 
                    LIMIT :limit 
                    OFFSET :offset 
        ");

        $stmt->execute([
            'limit' => $limit,
            'offset' => $offset
        ]);

        $records = $stmt->fetchAll();

        foreach ($records as &$record) {
            $details = str_replace(" ", "<br>", $record['event']);
            $details = str_replace(":", ': ', $details);
            $record['details'] = $details;
        }

        return $records;
    }

    public static function get_logs_for_domain($domain, $limit, $offset)
    {
        if (!(self::check_if_domain_exist($domain))) {
            return array();
        }

        global $db;
        $stmt = $db->prepare("
            SELECT log_zones.id, log_zones.event, log_zones.created_at, domains.name FROM log_zones
            INNER JOIN domains ON domains.id = log_zones.zone_id 
            WHERE domains.name LIKE :search_by
            LIMIT :limit 
            OFFSET :offset"
        );

        $domain = "%$domain%";
        $stmt->execute([
            'search_by' => $domain,
            'limit' => $limit,
            'offset' => $offset
        ]);

        $records = $stmt->fetchAll();

        foreach ($records as &$record) {
            $details = str_replace(" ", "<br>", $record['event']);
            $details = str_replace(":", ': ', $details);
            $record['details'] = $details;
        }

        return $records;
    }

    public static function check_if_domain_exist($domain_searched): bool
    {
        if ($domain_searched == "") {
            return false;
        }

        $zones = DnsRecord::get_zones('all');
        foreach ($zones as $zone) {
            if (strpos($zone['name'], $domain_searched) !== false) {
                return true;
            }
        }
        return false;
    }
}
