<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Data class for user location preferences
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Data
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2009 StatusNet Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

require_once INSTALLDIR.'/classes/Memcached_DataObject.php';

class Inbox extends Memcached_DataObject
{
    const BOXCAR = 128;

    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'inbox';                           // table name
    public $user_id;                         // int(4)  primary_key not_null
    public $notice_ids;                      // blob

    /* Static get */
    function staticGet($k,$v=NULL) { return Memcached_DataObject::staticGet('Inbox',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE

    function sequenceKey()
    {
        return array(false, false, false);
    }

    static function insertNotice($user_id, $notice_id)
    {
        $inbox = new Inbox();

        $inbox->query(sprintf('UPDATE inbox '.
                              'set notice_ids = concat(cast(%08x as binary(4)), '.
                              'substr(notice_ids, 1, 4092)) '.
                              'WHERE user_id = %d',
                              $notice_id, $user_id));
    }

    static function bulkInsert($notice_id, $user_ids)
    {
        $cnt = count($user_ids);

        for ($off = 0; $off < $cnt; $off += self::BOXCAR) {

            $boxcar = array_slice($user_ids, $off, self::BOXCAR);

            if (empty($boxcar)) { // jump in, hobo!
                break;
            }

            $inbox = new Inbox();

            $inbox->query(sprintf('UPDATE inbox '.
                                  'set notice_ids = concat(cast(%08x as binary(4)), '.
                                  'substr(notice_ids, 1, 4092)) '.
                                  'WHERE user_id in (%s)',
                                  $notice_id, implode(',', $boxcar)));

            $inbox->free();
        }
    }

    function stream($user_id, $offset, $limit, $since_id, $max_id, $since, $own=false)
    {
        $inbox = Inbox::staticGet('user_id', $user_id);

        if (empty($inbox)) {
            return array();
        }

        $ids = unpack('L*', $inbox->notice_ids);

        // XXX: handle since_id
        // XXX: handle max_id

        $ids = array_slice($ids, $offset, $limit);
    }
}
