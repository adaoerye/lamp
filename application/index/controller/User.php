<?php
namespace app\index\controller;
use RedisClient;
use think\Controller;
use think\Session;
class User extends Controller
{
    //用户添加
    public function create()
    {
        // echo 'aaa';
        return view('/user/create');
    }

    //用户插入
    public function store()
    {
        //提取参数
        $data = $_POST;
        // var_dump($data);
        //文件上传
        $file = request()->file('pic');
        // var_dump($file);
        //移动到框架应用根目录/public/uploads/ 目录下
        if($file){
            //图片存储路径
            $path = ROOT_PATH . 'public' . DS . 'uploads';
            $info = $file -> move($path);
            if($info){
                //成功上传后 获取上传信息
                $data['pic'] = '/uploads/'.$info->getSaveName();
                // var_dump($data['pic']);
            }else{
                //上传失败获取错误信息
                echo $file->getError();
                die;
            }
        }
        $data['create_at'] = time();
        // var_dump($data);
        // die;

        //插入到redis中
        $redis = RedisClient::getInstance();

        //将数据写入到hash中
        $user_id = $redis->incr('user_id');
        $data['id'] = $user_id;
        $key = 'user:'.$user_id;//user:1
        $res = $redis -> hmset($key,$data);
        // var_dump($data);
        //将数据id写入到列表中
        $res2 = $redis -> rpush('user_ids',$user_id);

        //添加 登录key
        $key = sprintf('login:%s',$data['username']);
        $res3 = $redis -> hmset($key,['password'=>$data['password'],'id'=>$user_id,'pic'=>$data['pic']]);

        if($res && $res2 && $res3){
            $this -> success('添加成功','/index','',1);
        }else{
            $this -> error('添加失败');
        }
    }

    //列表显示
    public function index()
    {
         // echo 'aaa';
        //读取用户id
        $ids = RedisClient::getInstance()->lrange('user_ids',0,9);
        // var_dump($ids);die;
        
        //遍历显示用户的信息
        $users = [];
        foreach ($ids as $key => $value) {
            //拼接用户的键名
            $key = 'user:'.$value;
            $user = RedisClient::getInstance()->hmget($key,['id','username','password','pic','create_at']);
            // var_dump($user);
            $users[] = $user;
        }
        // var_dump($users);
        // die;
        
        return view('user/index', ['users'=>$users]);
    }

    public function delete()
    {
        $id = $_GET['id'];
        $key = 'user:'.$id; 
        $redis = RedisClient::getInstance();
        $user = RedisClient::getInstance()->hget($key,'username'); 
        $delid = RedisClient::getInstance()->delete('login:'.$user);
        $delid1 = RedisClient::getInstance()->delete('user:'.$id);
        $delid2 = RedisClient::getInstance()->lrem('user_ids',$id);
        //删除粉丝
        $key2 = 'fans:'.$id;
        $res = $redis -> smembers($key2);
        if($res){
            //遍历我的粉丝
            foreach($res as $v){
                //获取我的粉丝的关注列表
                $key3 = 'follow:'.$v;
                $res2 = $redis -> smembers($key3);
                if($res2){
                    //遍历我的粉丝的人的关注列表找到我删除
                    foreach($res2 as $val){
                        if($val == $id){
                            $delid3 = $redis->srem($key3,$val);
                        }
                    }
                }else{
                    $delid3 = true;
                }
            }
        }else{
            $delid3 = true;
        }
        //删除我的粉丝列表
        $res3 = $redis->smembers($key2);
        if($res3){
            foreach($res3 as $v){
                $delid4 = $redis->srem($key2,$v);
            }
        }else{
            $delid4 = true;
        }
        //删除我的关注
        $key3 = 'follow:'.$id;
        $res4 = $redis -> smembers($key3); 
        if($res4){
            //遍历我的关注
            foreach($res4 as $v){
                //获取我关注的人的粉丝列表
                $key4 = 'fens:'.$v;
                $res5 = $redis->smembers($key4);
                if($res5){
                    //遍历我关注的人的粉丝列表找到我删除
                    foreach($res5 as $v){
                        if($v == $id){
                            $delid5 = $redis -> srem($key4,$v);
                        }
                    }
                }else{
                    $delid5 = true;
                }
            }
        }else{
            $delid5 = true;
        }
        //删除我的关注列表
        $res5 = $redis -> smembers($key3);
        if($res5){
            foreach($res5 as $v){
                $delid6 = $redis -> srem($key3,$v);
            }
        }else{
            $delid6 = true;
        }
        //删除我发过微博
        $key6 = 'weibo:list:'.$id;
        $res6 = $redis->smembers($key6);
        if($res6){
            //遍历我发过的微博
            foreach($res6 as $v){
                //获取我发过的微博的ID 删除
                $key7 = 'weibo:'.$v;
                $delid7 = $redis -> del($key7);
                $redis -> srem($key6,$v);
            }
        }else{
            $delid7 = true;
        }
        if($delid && $delid1 && $delid2 && $delid3 && $delid4 && $delid5 && $delid6 && $delid7){
            $this -> success('删除成功','/index','',1);
        }else{
            $this -> error('删除失败','/index','',1);
        }
    }

    public function edit()
    {
        // echo '111';
        // die;
        $id = $_GET['id'];                          //user:2
        $users = RedisClient::getInstance()->hmget('user:'.$id,['username','password','pic','create_at','id']);
        // var_dump($users);
        return view('user/edit',['users'=>$users]);
        
    }

    public function update()
    {
        $data = $_POST;
        // var_dump($data);
        // die;
        $id = $data['id'];
        // var_dump($id);
        // die;
        $file = request()->file('pic');
        // var_dump($file);
        //移动到框架应用根目录/public/uploads/ 目录下
        if($file){
            //图片存储路径
            $path = ROOT_PATH . 'public' . DS . 'uploads';
            $info = $file -> move($path);
            if($info){
                //成功上传后 获取上传信息
                $data['pic'] = '/uploads/'.$info->getSaveName();
                // var_dump($data['pic']);
            }else{
                //上传失败获取错误信息
                echo $file->getError();
                die;
            }
        }
        $data['create_at'] = time();
        // var_dump($data);
        // die;
        //插入到redis中
        $redis = RedisClient::getInstance();

        $key = 'user:'.$id;
        $res = $redis -> hmset($key,$data);

        if($res){
            $this -> success('修改成功','/index','',1);
        }else{
            $this -> error('修改失败');
        }   
    }
    //登录
    public function login()
    {
        // echo 'aaa';
        // die;
        return view('user/login');
    }
    //执行登录
    public function dologin()
    {
        // echo 'aaa';
        $data = $_POST;
        // var_dump($data);
        // die;
        //检索redis中
        $redis = RedisClient::getInstance();
        $key = sprintf('login:%s',$data['username']);
        $res = $redis -> exists($key);
        //获取该用户的密码和id值和头像
        $user = $redis->hmget($key,['password','id','pic']);
        if(!$res){
            $this->error('该用户不存在','/login','',1);
        }
        //检测密码
        if($data['password'] != $user['password']){
            $this->error('密码错误','/login','',1);
        }
        
        //写入session
        session('uid',$user['id']);
        session('username',$data['username']);
        session('pic',$user['pic']);
        //标记登录
        session('homeFlag',true);

        $redirectUrl = $_POST['redirectUrl'] ? : '/index';
        $this->success('登陆成功',$redirectUrl,'',1);
    }
    //退出登录
    public function logout()
    {
        Session::clear();
        $this->success('退出登录','/login','',1);
    }

    //个人中心
    public function center()
    {   
        $id = $_GET['id'];
        $redis =  RedisClient::getInstance();
        $data = $redis->hmget('user:'.$id,['username','pic']);
        //查询粉丝表中的数据
        $pid = $redis->smembers('fans:'.$id);
        $ppids = [];
        foreach($pid as $v){
            //拼接user:1
            $ppid = $redis->hmget('user:'.$v,['username','pic']);
            //压入数组中
            $ppids[] = $ppid;
        }
        $weibosize = $redis->ssize('weibo:list:'.$id);
        $weibo = $redis->smembers('weibo:list:'.$id);
        // var_dump($weibo);
        $weibos = [];
        foreach($weibo as $v){
            $weibos[] = $redis->hmget('weibo:'.$v,['title','content','create_at','author_id']);
        };
        // var_dump($weibos);
        
        // var_dump($weibos);
        // return view('user/center',['data'=>$data,'ppids'=>$ppids,'weibos'=>$weibos,'result'=>$result,'weibosize'=>$weibosize]);
        return view('user/center',['data'=>$data,'ppids'=>$ppids,'weibos'=>$weibos,'weibosize'=>$weibosize]);
    }

    //点击关注
    public function focus()
    {
        $uid = $_POST['uid'];
        $id = $_POST['id'];
        $redis = RedisClient::getInstance();
        $fanss = $redis->sadd('fans:'.$id,$uid);
        $follow = $redis->sadd('follow:'.$uid,$id);
        if($fanss && $follow){
            echo '1';
        }else{
            echo '0';
        }
    }


    //粉丝数量
    public function fanss()
    {
        // $did = $_POST['did'];
        $id = $_POST['id'];
        // var_dump($id);
        // die;
        $redis = RedisClient::getInstance();
        // $fans = $redis->sadd('fans:'.$id,$did);
        // $follow = $redis->sadd('follow:'.$did,$id);
        $fanss = $redis->ssize('fans:'.$id);
        echo $fanss;
    }
    //关注数量
    public function follow()
    {
        $id = $_POST['id'];
        $redis = RedisClient::getInstance();
        $follows = $redis->ssize('follow:'.$id);
        echo $follows;
    }

    //查询粉丝列表中是否含有该登录用户
    public function flists()
    {
        $id = $_POST['id'];
        $uid = $_POST['uid'];
        $redis = RedisClient::getInstance();
        $flists = $redis->sismember('fans:'.$id,$uid);
        if($flists){
            echo '1';
        }else{
            echo '0';
        }
    }

    //取消关注
    public function cancel()
    {
        $id = $_POST['id'];
        $uid = $_POST['uid'];
        $redis = RedisClient::getInstance();
        $fanss = $redis->srem('fans:'.$id,$uid);
        $follow = $redis->srem('follow:'.$uid,$id);
        if($fanss && $follow){
            echo '1';
        }else{
            echo '0';
        }

    }
    
}