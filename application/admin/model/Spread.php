<?php
namespace app\admin\model;
use think\Model;
use think\Db;
class Spread extends Model
{
    protected $autoWriteTimestamp = true;
    protected $createTime = "createtime";
    protected $updateTime = "updatetime";
}

