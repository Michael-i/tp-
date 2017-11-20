<?php
// 本类由系统自动生成，仅供测试用途
class LoginAction extends Action {
	//登录
	public function login(){
		//cookie('app_login','true');
		// 获取来源信息
		$login_back     =   I('login_back');
		$referer        =   I('referer')? I('referer') : $_SERVER["HTTP_REFERER"];
		$host           =   $_SERVER["HTTP_HOST"];
		$this->assign('login_back',$login_back);
		$this->assign('referer',$referer);
		//1.用户同意授权，获取code
		$appid			=	C('APPID');     						 //公众号的唯一标识
		$redirect_uri	=	urlencode("http://域名/Login/register_");//跳转注册判断页面，需要使用urlEncode对链接进行处理
		$scope			=	"snsapi_base";							 //默认方式  
		$scope			=	"snsapi_userinfo";						 //弹出授权页面
		$url			=	"https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$redirect_uri}&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect";
		redirect($url);
	}
	//判断该用户是否已授权注册过
	public function register_(){
		if(I('get.code')){
			$appid		=	C('APPID');								//公众号的唯一标识
			$appsecret  =	C('APPSECRET');							//公众号的appsecret
			$code		=	I('get.code');							//填写第一步获取的code参数
			//2.通过code换取网页授权access_token
			$url="https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appid}&secret={$appsecret}&code={$code}&grant_type=authorization_code";
			$ch=curl_init();
			curl_setopt($ch,CURLOPT_URL,$url);
			curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
			curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
			$output=curl_exec($ch);
			curl_close($ch);
			$jsoninfo=json_decode($output,true);
			/**$jsoninfo数据分析：
			{
			 "access_token":"ACCESS_TOKEN",     //网页授权接口调用凭证,注意：此access_token与基础支持的access_token不同

			 "expires_in":7200,    				//access_token接口调用凭证超时时间，单位（秒）

			 "refresh_token":"REFRESH_TOKEN",   //用户刷新access_token

			 "openid":"OPENID",    				//用户唯一标识，请注意，在未关注公众号时，用户访问公众号的网页，也会产生一个用户和公众号唯一的OpenID

			 "scope":"SCOPE"					//用户授权的作用域，使用逗号（,）分隔
			 } 	
			*/
			$user_info=M("users")->where("`openid`='".$jsoninfo['openid']."'")->field("user_id,user_name")->find();
			//判断该用户是否已授权注册过
			if($user_info['user_id']){
				//已注册
				cookie("user_name",$user_info['user_name']);
				cookie("user_id",$user_info['user_id']);
				$this->redirect('Index/index');
			}else{//未注册，跳转注册页面
				//保存refresh_token，后续获取openid  
				//ps:access_token也可获取，但其安全级别非常高，不允许传给客户端
				$this->assign('refresh_token',$jsoninfo['refresh_token']);
				$this->display('register');
			}
			
		}
	}
	//注册
	public function register(){
		if(I('post.refresh_token')){
			//3.刷新access_token（如果需要）
			$appid   		=	C('APPID');					//公众号的唯一标识
			$appsecret  	=	C('APPSECRET');				//填写为refresh_token
			$refresh_token	=	I('post.refresh_token');	//填写通过access_token获取到的refresh_token参数  
			$url			=	"https://api.weixin.qq.com/sns/oauth2/refresh_token?appid={$appid}&grant_type=refresh_token&refresh_token={$refresh_token} ";
			$ch=curl_init();
			curl_setopt($ch,CURLOPT_URL,$url);
			curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
			curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
			$output=curl_exec($ch);
			curl_close($ch);
			$jsoninfo=json_decode($output,true);
			/**$jsoninfo数据分析：
			{
			 "access_token":"ACCESS_TOKEN",     //网页授权接口调用凭证,注意：此access_token与基础支持的access_token不同

			 "expires_in":7200,    				//access_token接口调用凭证超时时间，单位（秒）

			 "refresh_token":"REFRESH_TOKEN",   //用户刷新access_token

			 "openid":"OPENID",    				//用户唯一标识，请注意，在未关注公众号时，用户访问公众号的网页，也会产生一个用户和公众号唯一的OpenID

			 "scope":"SCOPE"					//用户授权的作用域，使用逗号（,）分隔
			 } 	
			 */
			if(I("post.user_phone") && I("post.user_pass")){
				//4.拉取用户信息(需scope为 snsapi_userinfo)
				$access_token	=	$jsoninfo['access_token'];			//网页授权接口调用凭证,注意：此access_token与基础支持的access_token不同
				$openid			=	$jsoninfo['openid'];				//用户的唯一标识
				$lang			=	"zh_CN";							//返回国家地区语言版本，zh_CN 简体，zh_TW 繁体，en 英语
				$url="https://api.weixin.qq.com/sns/userinfo?access_token={$access_token}&openid={$openid}&lang={$lang}";
				
				$ch=curl_init();
				curl_setopt($ch,CURLOPT_URL,$url);
				curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
				curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
				curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
				$output=curl_exec($ch);
				curl_close($ch);
				$user_info            = json_decode($output,true);
				/*正确时返回的JSON数据包如下：

					{   
					 "openid":" OPENID",  			//用户的唯一标识

					 " nickname": NICKNAME,   		//用户昵称

					 "sex":"1",   					//用户的性别，值为1时是男性，值为2时是女性，值为0时是未知

					 "province":"PROVINCE"   		//用户个人资料填写的省份

					 "city":"CITY",   				//普通用户个人资料填写的城市

					 "country":"COUNTRY",    		//国家，如中国为CN
					 
					 "headimgurl":    "http://wx.qlogo.cn/mmopen/g3MonUZtNHkdmzicIlibx6iaFqAc56vxLSUfpb6n5WKSYVY0ChQKkiaJSgQ1dZuTOgvLLrhJbERQQ4eMsv84eavHiaiceqxibJxCfHe/46", //用户特权信息，json 数组，如微信沃卡用户为（chinaunicom）
													//用户头像，最后一个数值代表正方形头像大小（有0、46、64、96、132数值可选，0代表640*640正方形头像），用户没有头像时该项为空。若用户更换头像，原有头像URL将失效。
					
					 "privilege":[ "PRIVILEGE1" "PRIVILEGE2"     ],   //用户特权信息，json 数组，如微信沃卡用户为（chinaunicom）  

					 "unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL" 	  //只有在用户将公众号绑定到微信开放平台帐号后，才会出现该字段。

					}
				*/
				$data['alias']        = $user_info['nickname'];
				$data['sex']          = $user_info['sex'];
				$data['mobile_phone'] = I("post.user_phone");
				$data['user_name']    = I("post.user_phone");
				$data['password']	  = md5(md5(I("post.user_phone")));
				$data['openid'] 	  = $jsoninfo['openid'];
				$res=M("users")->add($data);

				if ($res){
					$self_userdata['photo']=$user_info['headimgurl'];
					$self_userdata['user_id']=$res;
					M("self_userdata")->add($self_userdata);
					show_msg("",U("Mobil/Index/index"));
				}else{
					show_msg("",U("Mobil/Login/login"));
				}

			}
		}
	}
	
}