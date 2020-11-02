<?php
declare(strict_types = 1);

namespace App\Service;


use Hyperf\DB\DB;

class UserFriendService
{
    /**
     * 获取指定用户的所有朋友的用户ID
     * @param int $uid 指定用户ID
     *
     * @return mixed
     */
    public function getFriends(int $uid)
    {
        $prefix = config('databases.default.prefix');
        $table  = $prefix . '_users_friends';
        $sql    = <<<SQL
SELECT user2 as uid
from im_users_friends
where user1 = :uid
  and `status` = 1
UNION all
SELECT user1 as uid
from im_users_friends
where user2 = :uid
  and `status` = 1
SQL;
        return DB::query($sql, [
            'uid' => $uid
        ]);
    }
}
