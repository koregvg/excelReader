<?php
namespace app\admin\controller;

use think\Controller;

use app\admin\model\User as User;

use think\Session;

use app\admin\controller\Common as Common;

use think\Request;

class Useroperation extends Common
{
    public function searchUser()
    {
        $queryArr = json_decode(json_encode(Request::instance()->post()));
        $searchArr = [];
        $table = '';
        $start = $queryArr->start;
        $len = $queryArr->len;
        $type = $queryArr->userStatus;
        $isAbroad = $queryArr->isAbroad;
        $response = array();

        foreach ($queryArr as $k => $v) {
            $unUse = array(
                'userStatus' => true,
                'isAbroad' => true,
                'start' => true,
                'len' => true
            );
            if (!isset($unUse[$k])) {

                if ($k === 'groupNum') {
                    $v = array('like', $v . '%');
                }
                $searchArr[$k] = $v;
            }
        }
        if ($type == 1) {
            if ($isAbroad == 0) {
                $table = 'tb_cdc_user_china';
            } else {
                $table = 'tb_cdc_user_abroad';
            }
        } else {
            if ($isAbroad == 0) {
                $table = 'tb_cdc_bad_user_china';
            } else {
                $table = 'tb_cdc_bad_user_abroad';
            }
        }
        $total = json_decode(User::checkProcess($table, $searchArr))[0]->total;
        $user = json_decode(User::searchUser($table, $start, $len, $searchArr));
        if ($user !== 'null') {
            $response = array(
                'errno' => 0,
                'total' => $total,
                'user' => $user
            );
            echo json_encode($response);
        }
    }

    public function checkPwd()
    {
        $response = array();
        $accountName = Session::get('loginedUser');
        $accountPassword = Request::instance()->param('password');
        $user = json_decode(User::searchAdminUser($accountName));

        if ($user->accountPassword !== $accountPassword) {
            $response = array(
                'errno' => 104,
                'msg' => '原密码错误'
            );
        } else {
            $response = array(
                'errno' => 0,
                'msg' => '校验正确'
            );
        }
        echo json_encode($response);
    }

    public function changePwd()
    {
        $accountName = Session::get('loginedUser');
        $newPwd = Request::instance()->param('newPwd');
        $searchData = array(
            'accountName' => $accountName
        );
        $updateData = array(
            'accountPassword' => $newPwd
        );
        $flag = json_decode(User::updateAdminUser($searchData, $updateData));
        if ($flag) {
            $response = array(
                'errno' => 0,
                'msg' => '修改成功'
            );
        } else {
            $response = array(
                'errno' => 106,
                'msg' => '系统错误，修改失败'
            );
        }
        echo json_encode($response);
    }

    public function deleteUser()
    {
        $queryArr = json_decode(json_encode(Request::instance()->post()));
        $table = '';
        $code = $queryArr->code;
        $type = $queryArr->userStatus;
        $isAbroad = $queryArr->isAbroad;
        $response = array();

        if ($type == 1) {
            if ($isAbroad == 0) {
                $table = 'tb_cdc_user_china';
            } else {
                $table = 'tb_cdc_user_abroad';
            }
        } else {
            if ($isAbroad == 0) {
                $table = 'tb_cdc_bad_user_china';
            } else {
                $table = 'tb_cdc_bad_user_abroad';
            }
        }
        $flag = json_decode(User::deleteUser($table, $code));
        if ($flag) {
            $response = array(
                'errno' => 0,
                'msg' => '删除成功'
            );
        } else {
            $response = array(
                'errno' => 109,
                'msg' => '系统错误，删除失败'
            );
        }
        echo json_encode($response);
    }
}

