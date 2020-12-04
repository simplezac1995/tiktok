<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

// 应用公共文件

/**
 * 获取登入信息
 * @return mixed
 */
function get_login_info(){
    $userInfo = session("loginInfo");
    return $userInfo;
}


/**
 * 设置登入信息
 * @param array $info
 */
function set_login_info($info){
    session("loginInfo",$info);
}

/**
 * 获取用户是否拥有某权限
 * @param string $power 权限标识
 * @return boolean
 */
function has_power($power){
    
    
    $loginInfo = get_login_info();
    if($loginInfo['id']=="1"){
        return true;
    }
    
    $power = strtolower($power);
    $powers = strtolower($loginInfo['powers']);
    
    $powers = explode(",",$powers);
    if(in_array($power, $powers)){
        return true;
    }
    
    $temp = explode(".", $power);//第一个肯定为控制类名
    //$temp[0] = strtolower($temp[0]);
    
    //     if(isset($temp[1])){
    //         $power = $temp[0].".".$temp[1];
    //     }
    
    if(in_array($temp[0].".all", $powers)){
        return true;
    }
    
    return false;
}


//导出EXCEL
function export_excel($data, $title = '未命名'){
    set_time_limit(0);
    ini_set("memory_limit","-1");
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle($title);
    
    
    $sheet->getDefaultColumnDimension()->setWidth('35');//默认单元格宽度
    //$sheet->getDefaultRowDimension()->setRowHeight('20');//默认行高
    //默认垂直居中
    //$sheet->gets->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    
    
    foreach ($data as $key1=>$sub_data) { //列
        foreach ($sub_data as $key2=>$item) { //行
            $sheet->setCellValueExplicitByColumnAndRow($key2+1, $key1+1,$item,DataType::TYPE_STRING);
            $alignment = $sheet->getStyleByColumnAndRow($key2+1, $key1+1)->getAlignment();
            $alignment->setWrapText(true);
            $alignment->setVertical(Alignment::VERTICAL_CENTER);
        }
    }
    
    unset($data);
    $writer = new Xlsx($spreadsheet);
    unset($spreadsheet);
    header("Pragma: public");
    header("Expires: 0");
    header('Access-Control-Allow-Origin:*');
    header('Access-Control-Allow-Headers:content-type');
    header('Access-Control-Allow-Credentials:true');
    header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
    header("Content-Type:application/force-download");
    header("Content-Type:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header("Content-Type:application/octet-stream");
    header("Content-Type:application/download");;
    header("Content-Disposition:attachment;filename=$title.xlsx");
    header("Content-Transfer-Encoding:binary");
    $writer->save('php://output');
    exit();
    
}



















