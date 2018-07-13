<?php
/**
 * Author: xx.com
 */ 
namespace Home\Controller;
class PaymentController extends BaseController {
    
    public $payment; //  具体的支付类
    public $pay_code; //  具体的支付code
 
    /**
     * 析构流函数
     */
    public function  __construct() {   
        parent::__construct();                                                  
        // lnshop 订单支付提交
        $pay_radio = $_REQUEST['pay_radio'];
        if(!empty($pay_radio)) 
        {                         
            $pay_radio = parse_url_param($pay_radio);
            $this->pay_code = $pay_radio['pay_code']; // 支付 code
        }
        else // 第三方 支付商返回
        {            
            $_GET = I('get.');            
            //file_put_contents('./a.html',$_GET,FILE_APPEND);    
            $this->pay_code = I('get.pay_code');
            unset($_GET['pay_code']); // 用完之后删除, 以免进入签名判断里面去 导致错误
        }                        
        //获取通知的数据
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];               
        // 导入具体的支付类文件                
        include_once  "plugins/payment/{$this->pay_code}/{$this->pay_code}.class.php"; // D:\wamp\www\svn_lnshop\www\plugins\payment\alipay\alipayPayment.class.php
        $code = '\\'.$this->pay_code; // \alipay
        $this->payment = new $code();
    }
   
    /**
     * lnshop 提交支付方式
     */
    public function getCode(){        
            C('TOKEN_ON',false); // 关闭 TOKEN_ON
            header("Content-type:text/html;charset=utf-8");            
            $order_id = I('order_id'); // 订单id
            // 修改订单的支付方式
            $payment_arr = M('Plugin')->where("`type` = 'payment'")->getField("code,name");
            M('order')->where("order_id = $order_id")->save(array('pay_code'=>$this->pay_code,'pay_name'=>$payment_arr[$this->pay_code]));
          
            $order = M('order')->where("order_id = $order_id")->find();
            if($order['pay_status'] == 1){
            	$this->error('此订单，已完成支付!');
            }            
            // lnshop 订单支付提交
            $pay_radio = $_REQUEST['pay_radio'];
            $config_value = parse_url_param($pay_radio); // 类似于 pay_code=alipay&bank_code=CCB-DEBIT 参数
            $code_str = $this->payment->get_code($order,$config_value);
            //微信JS支付
           if($this->pay_code == 'weixin' && $_SESSION['openid'] && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
               $code_str = $this->payment->getJSAPI($order,$config_value);
               exit($code_str);
           }
           $this->assign('code_str', $code_str); 
           $this->assign('order_id', $order_id); 
           $this->display('payment');  // 分跳转 和不 跳转 
    }

    public function getPay(){
    	C('TOKEN_ON',false); // 关闭 TOKEN_ON
    	header("Content-type:text/html;charset=utf-8"); 
    	$order_id = I('order_id'); // 订单id
    	// 修改充值订单的支付方式
    	$payment_arr = M('Plugin')->where("`type` = 'payment'")->getField("code,name");
    	
    	M('recharge')->where("order_id = $order_id")->save(array('pay_code'=>$this->pay_code,'pay_name'=>$payment_arr[$this->pay_code]));    
    	$order = M('recharge')->where("order_id = $order_id")->find();
    	if($order['pay_status'] == 1){
    		$this->error('此订单，已完成支付!');
    	}
    	$pay_radio = $_REQUEST['pay_radio'];
    	$config_value = parse_url_param($pay_radio); // 类似于 pay_code=alipay&bank_code=CCB-DEBIT 参数
        $order['order_amount'] = $order['account'];
    	$code_str = $this->payment->get_code($order,$config_value);
    	//微信JS支付
    	if($this->pay_code == 'weixin' && $_SESSION['openid'] && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
    		$code_str = $this->payment->getJSAPI($order,$config_value);
    		exit($code_str);
    	}
    	$this->assign('code_str', $code_str);
    	$this->assign('order_id', $order_id);
    	$this->display('recharge'); //分跳转 和不 跳转
    }
    
    // 服务器点对点 // http://www.xx.com/index.php/Home/Payment/notifyUrl
    public function notifyUrl(){            
        $this->payment->response();            
        exit();
    }

    // 页面跳转 // http://www.xx.com/index.php/Home/Payment/returnUrl
    public function returnUrl(){
        $result = $this->payment->respond2(); // $result['order_sn'] = '201512241425288593';
        
        if(stripos($result['order_sn'],'recharge') !== false)
        {
            $order = M('recharge')->where("order_sn = '{$result['order_sn']}'")->find();
            $this->assign('order', $order);
            if($result['status'] == 1)
                $this->display('recharge_success');   
            else
                $this->display('recharge_error');   
            exit();            
        }
                
        $order = M('order')->where("order_sn = '{$result['order_sn']}'")->find();
                    
        $this->assign('order', $order);
        if($result['status'] == 1) {  
        	M('users')->where(array('user_id'=>$order['user_id']))->save(array('is_distribut'=>1));     	
            $this->display('success');  
        } 
        else
            $this->display('error');   
    }                
}
