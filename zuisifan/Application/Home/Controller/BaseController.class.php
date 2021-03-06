<?php
/**
 * Author: xx.com
 */ 
namespace Home\Controller;
use Think\Controller;
class BaseController extends Controller {
    public $session_id;
    public $cateTrre = array();
    /*
     * 初始化操作
     */
    public function _initialize() {  
    	$this->session_id = session_id(); // 当前的 session_id
        define('SESSION_ID',$this->session_id); //将当前的session_id保存为常量，供其它方法调用
        // 判断当前用户是否手机                
        if(isMobile())
            cookie('is_mobile','1',3600);
        else
            cookie('is_mobile','0',3600);
                  
        $this->public_assign(); 
    }
    /**
     * 保存公告变量到 smarty中 比如 导航 
     */
    public function public_assign()
    {
        
       $lnshop_config = array();
       //$tp_config = M('config')->cache(true,LNSHOP_CACHE_TIME)->select();
       $tp_config = M('config')->select();
       foreach($tp_config as $k => $v)
       {
       	  if($v['name'] == 'hot_keywords'){
       	  	 $lnshop_config['hot_keywords'] = explode('|', $v['value']);
       	  }       	  
          $lnshop_config[$v['inc_type'].'_'.$v['name']] = $v['value'];
       }                        
       
       $goods_category_tree = get_goods_category_tree();    
       $this->cateTrre = $goods_category_tree;
       $this->assign('goods_category_tree', $goods_category_tree);
       //$brand_list = M('brand')->cache(true,LNSHOP_CACHE_TIME)->field('id,parent_cat_id,logo,is_hot')->where("parent_cat_id>0")->select();
       $brand_list = M('brand')->field('id,parent_cat_id,logo,is_hot')->where("parent_cat_id>0")->select();
       $this->assign('brand_list', $brand_list);
       $this->assign('lnshop_config', $lnshop_config);
    }


}