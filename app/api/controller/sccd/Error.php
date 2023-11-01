<?php
namespace app\api\controller\sccd;
use think\Response;

class Error extends  Base{

     public  function  index(){

         return  $this->create([],'资源不存在~');

     }


}