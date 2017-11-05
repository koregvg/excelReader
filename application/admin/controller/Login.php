<?php
namespace app\admin\controller;

use think\Controller;

use app\admin\model\User as User;

use think\Session;

use think\Request;

class Login extends Controller
{
    public function check()
    {
        $token = Request::instance()->param('token');
        $response = array();
        if (Session::get('loginedUser') && $token === Session::get('token')) {
            $response = array(
                'errno' => 0,
                'msg' => '已登录'
            );
        } else {
            $response = array(
                'errno' => 101,
                'msg' => '请先登录'
            );
        }
        echo json_encode($response);
    }

    public function search()
    {
        $userName = Request::instance()->param('userName');
        $hash = Request::instance()->param('password');
        $user = json_decode(User::searchAdminUser($userName));
        $flag = password_verify($user->accountPassword, $hash);
        if ($flag) {
            session_start();
            $token = md5(session_id());
            Session::set('loginedUser',$user->accountName);
            Session::set('token', $token);
            $response = array(
                'errno' => 0,
                'msg' => '登陆成功',
                'token' => Session::get('token')
            );
        } else {
            $response = array(
                'errno' => 1,
                'msg' => '用户名或密码错误'
            );
        }
        echo json_encode($response);
    }
}

