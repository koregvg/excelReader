<?php
namespace app\admin\controller;

use think\Controller;

use app\admin\model\User as User;

use app\admin\controller\Common as Common;

use think\Session;

use think\Request;

use PHPExcel_IOFactory;

use PHPExcel;

use PHPExcel_Style_NumberFormat;

set_time_limit(0);
ini_set('memory_limit', '2048M');

class Export extends Common
{
    public function exportUser()
    {
        $queryArr = json_decode(json_encode(Request::instance()->post()));
        $searchArr = [];
        $table = '';
        $type = $queryArr->userStatus;
        $isAbroad = $queryArr->isAbroad;

        foreach ($queryArr as $k => $v) {
            $unUse = array(
                'userStatus' => true,
                'isAbroad' => true
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
        $user = json_decode(User::searchUser($table, '', '', $searchArr));

        $objPHPExcel = new \PHPExcel();
        $filename = date('YmdHis');

        // Set document properties
        $objPHPExcel->getProperties()->setCreator("CDC海外特区")
            ->setLastModifiedBy("CDC海外特区")
            ->setTitle("用户数据信息")
            ->setSubject("用户数据信息")
            ->setDescription("用户数据信息")
            ->setKeywords("用户数据信息")
            ->setCategory("用户数据信息");

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth(25);
        $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('N')->setWidth(20);

        // Add some data
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '序号')
            ->setCellValue('B1', '群号')
            ->setCellValue('C1', '姓名')
            ->setCellValue('D1', '性别')
            ->setCellValue('E1', '职务')
            ->setCellValue('F1', '编码')
            ->setCellValue('G1', '日期')
            ->setCellValue('H1', '身份证号')
            ->setCellValue('I1', '电话')
            ->setCellValue('J1', '邮箱')
            ->setCellValue('K1', '地区')
            ->setCellValue('L1', '推荐人')
            ->setCellValue('M1', '推荐人电话')
            ->setCellValue('N1', '备注');

        if ($type == 0) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue('O1', '错误原因');
        }

        $len = count($user);
        for ($i = 0; $i < $len; $i++) {
            $v = $user[$i];
            $rownum = $i + 2;
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $rownum, $i + 1);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $rownum, $v->groupNum);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $rownum, $v->userName);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $rownum, $v->gender);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $rownum, $v->position);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $rownum, $v->code);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $rownum, $v->registerTime);
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $rownum, ' '.$v->idCardNum);
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $rownum, ' '.$v->phoneNum);
            $objPHPExcel->getActiveSheet()->setCellValue('J' . $rownum, $v->email);
            $objPHPExcel->getActiveSheet()->setCellValue('K' . $rownum, $v->region);
            $objPHPExcel->getActiveSheet()->setCellValue('L' . $rownum, $v->referee);
            $objPHPExcel->getActiveSheet()->setCellValue('M' . $rownum, ' '.$v->refereePhoneNum);
            $objPHPExcel->getActiveSheet()->setCellValue('N' . $rownum, $v->remark);
            if ($type == 0) {
                $objPHPExcel->getActiveSheet()->setCellValue('O' . $rownum, $v->reason);
            }
        }

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);

        // Redirect output to a client’s web browser (Excel2007)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename=' . $filename . '.xlsx');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

        function saveExcelToLocalFile($objWriter, $filename)
        {
            // make sure you have permission to write to directory
            $filePath = ROOT_PATH . 'public' . DS . 'exports' . DS . $filename . '.xlsx';
            $objWriter->save($filePath);
            return $filename . '.xlsx';
        }

        //返回已经存好的文件目录地址提供下载
        $response = array(
            'errno' => 0,
            'filename' => saveExcelToLocalFile($objWriter, $filename),
            'token' => Session::get('token')
        );
        echo json_encode($response);
        exit;
    }
}

