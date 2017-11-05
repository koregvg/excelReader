<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/9/25 0025
 * Time: 18:24
 */
namespace app\admin\controller;

use think\Cookie;

use think\Session;

use think\Controller;

use think\Request;

class Common extends Controller
{
    public function _initialize()
    {
        $token = Request::instance()->param('token');
        if (!Session::has('loginedUser') || $token !== Session::get('token')) {
            echo json_encode(
                array(
                    'errno' => 101,
                    'msg' => '请先登录'
                )
            );
            exit();
        }
    }
}