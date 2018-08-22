<?php


use think\Route;

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------


Route::get('/home','index/Home/index');

//用户添加
Route::get('/create','index/User/create');

//用户插入
Route::post('/user','index/User/store');

//列表
Route::get('/index','index/User/index');

//删除
Route::get('/delete','index/User/delete');

//修改
Route::get('/edit','index/User/edit');

//更新
Route::post('/update','index/User/update');

//登录
Route::get('/login','index/User/login');

//执行登录
Route::post('/dologin','index/User/dologin');

//退出登录
Route::get('/logout','index/User/logout');

//个人中心
Route::get('/center','index/User/center');

//粉丝数量
Route::post('/fanss','index/User/fanss');

//关注
Route::post('/focus','index/User/focus');

//关注数量
Route::post('/follow','index/User/follow');

//分析列表
Route::post('/flists','index/User/flists');

//取消关注
Route::post('/cancel','index/User/cancel');

//发微博
Route::get('/send','index/Weibo/send');

//保存微博
Route::post('/upload','index/Weibo/upload');