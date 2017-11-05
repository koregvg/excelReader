<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/20 0020
 * Time: 23:57
 */

namespace app\admin\model;

use think\Model;

use think\Db;

class User extends Model
{
    public static function search($key, $value, $type, $isLike = false)
    {
        $table = '';
        if ($type === 'chinaUser') {
            $table = 'tb_cdc_user_china';
        } else if ($type === 'abroadUser') {
            $table = 'tb_cdc_user_abroad';
        }

        if (!$isLike) {
            $user = Db::table($table)
                ->where($key, $value)
                ->find(); // 查询数据
            return json_encode($user);
        } else {
            $user = Db::table($table)
                ->where($key, 'like', $value)
                ->find();
            return json_encode($user);
        }
    }

    public static function searchUser($table, $start, $len, $queryArr)
    {
        $where = $queryArr;
        if($start && $len) {
            $user = Db::table($table)
                ->where($where)
                ->field(['uid', 'reasonType'], true)
                ->page($start, $len)
                ->order('uid', 'desc')
                ->select();
        }else {
            $user = Db::table($table)
                ->where($where)
                ->field(['uid', 'reasonType'], true)
                ->order('uid', 'desc')
                ->select();
        }
        return json_encode($user);
    }

    public static function checkProcess($table, $queryArr)
    {
        $where = $queryArr;
        $total = Db::table($table)
            ->where($where)
            ->field(['count(*)' => 'total'])
            ->select();
        return json_encode($total);
    }

    public static function setCounter($data)
    {
        $result = Db::table('tb_cdc_user_counter')->insert($data);
        return json_encode($result);
    }

    public static function updateCounter($cid, $total)
    {
        $result = Db::table('tb_cdc_user_counter')
            ->where('cid', $cid)
            ->update(['counter' => $total]);
        return json_encode($result);
    }

    public static function checkCounter($cid)
    {
        $result = Db::table('tb_cdc_user_counter')
            ->where('cid', $cid)
            ->find(); // 查询数据
        return json_encode($result);
    }

    public static function deleteCounter($cid)
    {
        $result = Db::table('tb_cdc_user_counter')
            ->where('cid', $cid)
            ->delete();
        return json_encode($result);
    }

    public static function checkBadData($type)
    {
        $table = '';
        if ($type === 'chinaUser') {
            $table = 'tb_cdc_bad_user_china';
        } else if ($type === 'abroadUser') {
            $table = 'tb_cdc_bad_user_abroad';
        }
        $result = Db::query('select count(*) as total from ' . $table);


        return json_encode($result);
    }

    public static function insertData($data, $type)
    {
        $table = '';
        if ($type === 'chinaUser') {
            $table = 'tb_cdc_user_china';
        } else if ($type === 'abroadUser') {
            $table = 'tb_cdc_user_abroad';
        }
        return $result = Db::table($table)->insert($data); //插入数据
    }

    public static function insertBadData($data, $type)
    {
        $table = '';
        if ($type === 'chinaUser') {
            $table = 'tb_cdc_bad_user_china';
        } else if ($type === 'abroadUser') {
            $table = 'tb_cdc_bad_user_abroad';
        }
        return $result = Db::table($table)->insert($data); //插入数据
    }

    public static function deleteUser($table,$code)
    {
        $result = Db::table($table)
            ->where('code',$code)
            ->delete();
        return json_encode($result);
    }

    public static function createAdminUser($data)
    {
        return $result = Db::table('tb_cdc_admin_user')->insert($data); // 插入管理员
    }

    public static function searchAdminUser($userName)
    {
        $where['accountName'] = $userName;
        $result = Db::table('tb_cdc_admin_user')
            ->where($where)
            ->find(); // 查询数据
        return json_encode($result);
    }

    public static function updateAdminUser($searchData,$updateData)
    {
        $where = $searchData;
        $update = $updateData;
        $result = Db::table('tb_cdc_admin_user')
            ->where($where)
            ->update($update);
        return json_encode($result);
    }
}