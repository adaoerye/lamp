<?php 
namespace app\index\controller;
use RedisClient;
use think\Controller;
use think\Session;


class Weibo extends Controller
{	//发送微博
	public function send()
	{
		$uid = $_GET['uid'];
		// var_dump($id);
		return view('weibo/send',['uid'=>$uid]);
	}
	//保存到数据库中
	public function upload()
	{	
		//使用post方式接收数据
		$data = $_POST;
		//获取发微博的时间
		$data['create_at'] = time();
		$redis = RedisClient::getInstance();
		//微博自增ID
		$weibo_id = $redis->incr('weibo_id');
		//获取作者
		$author_id = $data['author_id']; 
		//将数据写入表中
		$res = $redis -> hmset('weibo:'.$weibo_id,$data);
		//获取每条微博是由谁发出的
		$res2 = $redis -> sadd('weibo:list:'.$author_id,$weibo_id);
		//判断 如果发送成功 
		if($res && $res2){
			$this->success('发送成功','/center?id='.$author_id,'',1);
		}else{
			$this->error('发送失败','/weibo/send','',1);
		}
		

	}

}



 ?>