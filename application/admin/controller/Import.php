<?php
namespace app\admin\controller;

use phpDocumentor\Reflection\Types\Null_;
use think\Controller;

use app\admin\controller\Common as Common;

use app\admin\model\User as User;

use think\File;

use think\Request;

use think\Session;

use PHPExcel_IOFactory;

use PHPExcel;

set_time_limit(0);
ini_set('memory_limit', '2048M');

class Import extends Common
{
    //1917年以上
    const ID_CARD_NUM_REG = '/^[1-9][0-9]{5}(19)(1[7-9]|[2-9][0-9])((01|03|05|07|08|10|12)(0[1-9]|[1-2][0-9]|3[0-1])|(04|06|09|11)(0[1-9]|[1-2][0-9]|30)|02(0[1-9]|[1-2][0-9]))[0-9]{3}([0-9]|x|X)$/';

    const CODE_REG = '/^(ZWSLHMDDDJ((0[1-9])|([1-9][0-9])|([1-9][0-9]{2})))|(ZWSLHMDDD((0[1-9])|([1-9][0-9])|([1-9][0-9]{2}))0[1-3])|(ZWSLHMZZZ((0[1-9])|([1-9][0-9])|([1-9][0-9]{2}))(0[1-9]|([1-9][0-9])))|(ZWSLHMQQQ((0[1-9])|([1-9][0-9])|([1-9][0-9]{2}))(0[1-9]|([1-9][0-9])){2}001)|(ZWSLHMGGG((0[1-9])|([1-9][0-9])|([1-9][0-9]{2}))(0[1-9]|([1-9][0-9])){2}00[2-7])|(ZWSLHMJJJ((0[1-9])|([1-9][0-9])|([1-9][0-9]{2}))(0[1-9]|([1-9][0-9])){2}00[2-7])|(ZWSLHMBBB((0[1-9])|([1-9][0-9])|([1-9][0-9]{2}))(0[1-9]|([1-9][0-9])){2}00[2-7])|(ZWSLHMHHH((0[1-9])|([1-9][0-9])|([1-9][0-9]{2}))(0[1-9]|([1-9][0-9])){2}((00[8-9])|(0[1-9][0-9])|([1-9][0-9]{2})))$/';

    const CODE_SIMPLE_REG = '/^([a-zA-Z]{9,10})([0-9]{2,10})$/';

    const PHONE_NUM_REG = '/^1[3-8][0-9]{9}$/';

    const EMAIL_REG = '/^\w+([.]\w+)?[@]\w+[.]\w+([.]\w+)?$/';

    public function index()
    {
        return $this->fetch();
    }

    public function upload()
    {
        // 获取表单上传文件 excel可以改名，但要保证一致。
        $file = request()->file('file');
        $type = Request::instance()->param('type');
        $cid = Request::instance()->param('cid');

        //创建计数器
        $counter = [];
        $counter['cid'] = $cid;
        $counter['counter'] = 0;
        User::setCounter($counter);
        // 移动到框架应用根目录/public/uploads/ 目录下public也可以改名，如果改成upload/asd则会自动创建两个文件夹。
        $info = $file->validate(['ext' => 'xlsx'])->move(ROOT_PATH . 'public' . DS . 'uploads');
        $response = array();
        if ($info) {
            // echo $info->getFilename();
            $exclePath = $info->getSaveName();  //获取文件名
            $file_name = ROOT_PATH . 'public' . DS . 'uploads' . DS . $exclePath;   //上传文件的地址
            $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
            $objReader->setReadDataOnly(true);
            $obj_PHPExcel = $objReader->load($file_name, $encode = 'utf-8');  //加载文件内容,编码utf-8

            $excel_array = $obj_PHPExcel->getsheet(0)->toArray();   //转换为数组格式


            if (!isset($excel_array[0][13])) {
                $response = array(
                    'errno' => 107,
                    'msg' => '文件列数有误'
                );
                echo json_encode($response);
                exit();
            }

            array_shift($excel_array);  //删除第一个数组(标题);

            $wrongFlag = true;
            $goodUser = true;
            $badUser = true;
            $errObj = array(
                'goodUser' => [],
                'badUser' => []
            );
            $successAmount = 0;
            $failAmount = 0;

            $errList = array(
                'ERR001' => '数据项存在重复',
                'ERR002' => '关键数据项为空',
                'ERR003' => '身份证格式有误',
                'ERR004' => '编码格式有误',
                'ERR005' => '电话号码格式有误',
                'ERR006' => '推荐人电话号码格式有误',
                'ERR007' => '邮箱格式有误',
                'ERR008' => '群架构下没有会员',
                'ERR009' => '编码与职位信息不符'
            );

            $positionMap = array(
                'ZWSLHMDDDJ' => '大区经理',
                'ZWSLHMDDD' => '大区负责人',
                'ZWSLHMZZZ' => '大区总监',
                'ZWSLHMQQQ' => '群主',
                'ZWSLHMGGG' => '群管',
                'ZWSLHMJJJ' => '讲师',
                'ZWSLHMBBB' => '报单员',
                'ZWSLHMHHH' => '会员',
            );

            $starttime = explode(' ', microtime());
            foreach ($excel_array as $k => $v) {
                $flag = true;
                $userDataTmp = [];
                $userData = [];
                $userDataSearch = [];
                $errno = ''; // 储存错误信息
                $map = [];
                $positionNum = '';
                $groupNum = '';

                // 纯空表
                if (!trim($v[2])) {
                    continue;
                };

                if ($type === 'chinaUser') {

                    if (preg_match(self::CODE_SIMPLE_REG, trim(strtoupper($v[5])), $matches)) {
                        $positionNum = $matches[1];
                        $groupNum = $matches[2];
                    }

                    if ($positionNum !== 'ZWSLHMDDDJ' && $positionNum !== 'ZWSLHMDDD' && $positionNum !== 'ZWSLHMZZZ') {
                        $groupNum = substr($groupNum, 0, -3);
                    }

                    $userDataTmp['groupNum'] = trim($groupNum);
                    $userDataTmp['userName'] = trim($v[2]);
                    $userDataTmp['gender'] = trim($v[3]);
                    $userDataTmp['position'] = trim($v[4]);
                    $userDataTmp['code'] = trim(strtoupper($v[5]));
                    $userDataTmp['idCardNum'] = strval(trim(preg_replace('/(,|，|：|\'|:|·)+/', '', preg_replace('/(o|O)+/', '0', preg_replace('/×|Ⅹ|ⅹ/', 'x', $v[7])))));
                    $userDataTmp['phoneNum'] = strval(trim(preg_replace('/(,|，|\'|：|:|·)+/', '', $v[8])));
                    $userDataTmp['email'] = trim(preg_replace('/。|·/', '.', $v[9]));
                    $userDataTmp['region'] = trim($v[10]);
                    $userDataTmp['referee'] = trim($v[11]);
                    $userDataTmp['refereePhoneNum'] = strval(trim(preg_replace('/(,|，|：|\'|:|·)+/', '', $v[12])));
                    $userDataTmp['remark'] = trim($v[13]);
                    $userDataTmp['reasonType'] = '';
                    $userDataTmp['reason'] = '';

                    $userData = $userDataTmp;
                    for ($i = 2; $i < 12; $i++) {
                        // 非空校验;
                        if ($i !== 6 && $i !== 9 && !$v[$i]) {
                            $flag = false;
                            $errno = 'ERR002';
                        }
                    }

                    if ($flag) {
                        // 邮箱错误不影响用户导入
                        if (!preg_match(self::EMAIL_REG, $userData['email'])) {
                            $errno = 'ERR007';
                        }

                        // 身份证格式校验
                        if ($flag && !preg_match(self::ID_CARD_NUM_REG, $userData['idCardNum'])) {
                            $flag = false;
                            $errno = 'ERR003';
                        }

                        // 有效性检验
                        // 编码格式校验

                        if ($flag && !preg_match(self::CODE_REG, $userData['code'])) {
                            $flag = false;
                            $errno = 'ERR004';
                        }

                        // 编码与职位匹配性校验
                        if ($flag && (!isset($positionMap[$positionNum]) || $userData['position'] !== $positionMap[$positionNum])) {
                            $flag = false;
                            $errno = 'ERR009';
                        }

                        // 电话号码格式校验
                        if ($flag && !preg_match(self::PHONE_NUM_REG, $userData['phoneNum'])) {
                            $flag = false;
                            $errno = 'ERR005';
                        }

//                        if ($flag && $userData['refereePhoneNum'] && !preg_match(self::PHONE_NUM_REG, $userData['refereePhoneNum'])) {
//                            $flag = false;
//                            $errno = 'ERR006';
//                        }
                    }

                    // 关键字段重复性检验查询
//                    if ($flag) {
//                        $userDataSearch = [
//                            'code' => $userData['code'],
//                            'idCardNum' => $userData['idCardNum'],
//                            'phoneNum' => $userData['phoneNum']
//                        ];
//                        foreach ($userDataSearch as $key => $value) {
//                            $result = User::search($key, $value, $type);
//                            if ($result !== 'null') {
//                                $flag = false;
//                                $jsonData = json_decode($result);
//                                $errno = 'ERR001';
//                                $userData['reason'] = '内容与 ' . $jsonData->code . ' 编码用户存在重复';
//                                break;
//                            }
//                        }
//                    }

                    if ($flag) {
                        if ($positionNum !== 'ZWSLHMHHH' && $positionNum !== 'ZWSLHMDDDJ' && $positionNum !== 'ZWSLHMDDD' && $positionNum !== 'ZWSLHMZZZ') {
                            $result = User::search('code', 'ZWSLHMHHH' . $groupNum . '%', $type, true);
                            if ($result === 'null') {
                                $flag = false;
                                $errno = 'ERR008';
                            }
                        }
                    }

                    // 执行插入操作
                    if ($flag) {
                        try {
                            User::insertData($userData, $type);
                        } catch (\Exception $e) {
                            if (strpos($e->getMessage(), 'SQLSTATE[23000]') !== false) {
                                $flag = false;
                                $errno = 'ERR001';
                            } else {
                                $goodUser = false;
                                array_push($errObj['goodUser'], $e->getMessage());
                            }
                        }
                    }

                    // 结果处理
                    // 没有错误
                    if ($flag) {
                        $successAmount = $successAmount + 1;
                        if ($errno) {
                            $userData['reasonType'] = $errno;
                            if (!$userData['reason']) {
                                $userData['reason'] = $errList[$errno];
                            }
                        }
                        // 出现错误
                    } else {
                        $failAmount = $failAmount + 1;
                        $wrongFlag = false;
                        $userData['reasonType'] = $errno;
                        if (!$userData['reason']) {
                            $userData['reason'] = $errList[$errno];
                        }
                        $result = User::insertBadData($userData, $type);
                        if (!$result) {
                            $badUser = false;
                            array_push($errObj['badUser'], $userData['code']);
                        }
                    }
                } else
                    if ($type === 'abroadUser') {

                        if (preg_match(self::CODE_SIMPLE_REG, trim(strtoupper($v[5])), $matches)) {
                            $positionNum = $matches[1];
                            $groupNum = $matches[2];
                        }

                        if ($positionNum !== 'ZWSLHMDDDJ' && $positionNum !== 'ZWSLHMDDD' && $positionNum !== 'ZWSLHMZZZ') {
                            $groupNum = substr($groupNum, 0, -3);
                        }

                        $userDataTmp['groupNum'] = trim($groupNum);
                        $userDataTmp['userName'] = trim($v[2]);
                        $userDataTmp['gender'] = trim($v[3]);
                        $userDataTmp['position'] = trim($v[4]);
                        $userDataTmp['code'] = trim(strtoupper($v[5]));
                        $userDataTmp['idCardNum'] = strval(trim(preg_replace('/(,|，|\'|：|:|·)+/', '', preg_replace('/(o|O)+/', '0', preg_replace('/×|Ⅹ/', 'x', $v[7])))));
                        $userDataTmp['phoneNum'] = strval(trim(preg_replace('/(,|，|：|\'|:|·)+/', '', $v[8])));
                        $userDataTmp['email'] = trim(preg_replace('/。|·/', '.', $v[9]));
                        $userDataTmp['region'] = trim($v[10]);
                        $userDataTmp['referee'] = trim($v[11]);
                        $userDataTmp['refereePhoneNum'] = strval(trim(preg_replace('/(,|，|：|\'|:|·)+/', '', $v[12])));
                        $userDataTmp['remark'] = trim($v[13]);
                        $userDataTmp['reasonType'] = '';
                        $userDataTmp['reason'] = '';

                        $userData = $userDataTmp;
                        for ($i = 2; $i < 12; $i++) {
                            // 非空校验;
                            if ($i !== 6 && $i !== 9 && !$v[$i]) {
                                $flag = false;
                                $errno = 'ERR002';
                            }
                        }
                        // 无校验
                        if ($flag) {
//                            $userDataSearch = [
//                                'code' => $userData['code'],
//                                'idCardNum' => $userData['idCardNum'],
//                                'phoneNum' => $userData['phoneNum']
//                            ];
//                            foreach ($userDataSearch as $key => $value) {
//                                $result = User::search($key, $value, $type);
//                                if ($result !== 'null') {
//                                    $flag = false;
//                                    $jsonData = json_decode($result);
//                                    $errno = 'ERR001';
//                                    $userData['reason'] = '内容与 ' . $jsonData->code . ' 编码用户存在重复';
//                                    break;
//                                }
//                            }
                            try {
                                User::insertData($userData, $type);
                            } catch (\Exception $e) {
                                if (strpos($e->getMessage(), 'SQLSTATE[23000]') !== false) {
                                    $flag = false;
                                    $errno = 'ERR001';
                                } else {
                                    $goodUser = false;
                                    array_push($errObj['goodUser'], $e->getMessage());
                                }
                            }

                            if ($flag) {
                                $successAmount = $successAmount + 1;
                                // 出现错误
                            } else {
                                $failAmount = $failAmount + 1;
                                $wrongFlag = false;
                                $userData['reasonType'] = $errno;
                                if (!$userData['reason']) {
                                    $userData['reason'] = $errList[$errno];
                                }
                                $result = User::insertBadData($userData, $type);
                                if (!$result) {
                                    $badUser = false;
                                    array_push($errObj['badUser'], $userData['code']);
                                }
                            }
                        }
                    }
                // 记录入入计数器
                if (($successAmount + $failAmount) % 300 === 0) {
                    User::updateCounter($counter['cid'], $successAmount + $failAmount);
                }
            }
            // 删除计数器
            User::deleteCounter($counter['cid']);

            $endtime = explode(' ', microtime());
            $thistime = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
            $thistime = round($thistime, 3);

            $dbMessage = '';
            if (!$goodUser) {
                $dbMessage = $dbMessage . ' 存在合规用户导入出错';
            }
            if (!$badUser) {
                $dbMessage = $dbMessage . ' 存在不合规用户导入出错';
            }
            if ($wrongFlag) {
                $response = array(
                    'errno' => 0,
                    'msg' => '导入成功',
                    'successAmount' => $successAmount,
                    'failAmount' => $failAmount,
                    'time' => $thistime . " s"
                );
            } else if (!$wrongFlag && $dbMessage !== '') {
                $response = array(
                    'errno' => 103,
                    'msg' => $dbMessage,
                    'errobj' => $errObj
                );
            } else if (!$wrongFlag && $dbMessage === '') {
                $response = array(
                    'errno' => 0,
                    'msg' => '导入成功',
                    'errobj' => $errObj,
                    'successAmount' => $successAmount,
                    'failAmount' => $failAmount,
                    'time' => $thistime . " s"
                );
            }
        } else {
            User::deleteCounter($counter['cid']);
            $response = array(
                'errno' => 102,
                'msg' => '文件上传出错',
                'errdetail' => $file->getError()
            );
        }
        echo json_encode($response);
    }
}

