<?php
namespace app\admin\controller;

use think\Controller;

use app\admin\model\User as User;

use app\admin\controller\Common as Common;

class Counter extends Controller
{
    public function checkCounter($cid)
    {
        $amount = json_decode(User::checkCounter($cid));
        $counter = $amount->counter;
        if ($amount !== 'null') {
            $response = array(
                'errno' => 0,
                'total' => $counter
            );
            echo json_encode($response);
        }
    }
}

