<?php
/*
 *  公共操作函数
 */
/*
 * 用于调试输出
 */
function printr($msg){
	echo '<pre>';
	print_r($msg);die;
	echo '</pre>';
}
/*
* 把字符串或数据转换安全模式
* @string array/string $string: 要过滤的数据
* @return: 返回过滤后的数据
*/
function saddslashes($string) {
	if(is_array($string)) {
		foreach($string as $key => $val) {
			$string[$key] = saddslashes($val);
		}
	} else {
		$string = addslashes($string);
	}
	return $string;
}
/*
* 对话框,URL跳转
* @param string $msgkey:       对话框内显示的信息
* @param string $url_forward:  要跳转的URL
* @param int    $second:       此对话框停留的时间
*/
function showmessage($msgkey, $url_forward='', $second=5) {
    
    global $_IGLOBAL;

	if(empty($_IGLOBAL['inajax']) && $url_forward && empty($second)) {
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: $url_forward");
	} else {

		$message = $msgkey;

		if($_IGLOBAL['inajax']) {
			if($url_forward) {
				$message = "<a href=\"$url_forward\">$message</a><ajaxok>";
			}
			echo 'true||##alert(\''.$message.'\');';
			//ob_out();
		} else {
			$site = SITE;
			$iisssite = IISSSITE;
			$blogurl = C("BLOGURL");
			$message = stristr($message, '</a>') == false ? "<a href=\"$url_forward\">$message</a>" : $message;
			if($url_forward) {
				$message .= "<script>setTimeout(\"window.location.href ='$url_forward';\", ".($second*1000).");</script>";
			}else{
				if($second>0){
					$message = str_replace("=\"\">", "=\"javascript:history.go(-1);\">", $message);
					//$message .= "<script>setTimeout(\"location.href='".IConfig::BASE."'\", ".($second*1000).");</script>";
				}
			}
			if(strstr($message,$blogurl)){
				$jumpname = '博客';
			}else{
				$jumpname = '首页';
			}
			$str = <<<SHOWMESSAGE
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>网页信息提示页面</title>
<link href="$site/Public/style/basic.v1.4.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="$site/Public/js/jquery.min.js"></script>
<script type="text/javascript" src="$site/Public/js/function.comm.js"></script>
<body>
<div id="main">
	<div class="bug_block">
		<div class="bug_outside">
			<div class="bug_inside">
			<img src="$site/Public/images/blog/blog_bug.jpg" width="67" height="67" />
			<p><span>$message</span><br />{$second}秒钟页面将自动跳转至{$jumpname}</p>
		</div>
		<div class="bug_insides">
			<a class="blue" href="javascript:history.go(-1);">返回上一页</a><a class="blue" href="$iisssite">返回首页</a><a class="grayfont" href="$iisssite">战略网</a>
		</div>
		</div></div>
	</div>
</div>
</body>
</html>
SHOWMESSAGE;
          echo $str;
		}
	}
	exit();
}
//获取字符串
function getstr($string, $length, $in_slashes=0, $out_slashes=0, $censor=0, $html=0, $json=0) {
	global $_IGLOBAL;
//	$string = trim($string);

	if($in_slashes) {
		//传入的字符有slashes
		$string = sstripslashes($string);
	}
	if($html < 0) {
		//去掉html标签
		$string = preg_replace("/(\<[^\<]*\>|\r|\n|\s|\[.+?\])/is", ' ', $string);
		$string = shtmlspecialchars($string);
	} elseif ($html == 0) {
		//转换html标签
		$string = shtmlspecialchars($string);
	}

	if($censor) {
		//词语屏蔽
		//取得系统设置参数
		$disablewords = file_get_contents(IISSSITE."/sysinfo.php?var=sysconfig&datavalue=disablewords");
		$replacewords = file_get_contents(IISSSITE."/sysinfo.php?var=sysconfig&datavalue=replacewords");
		$reviewwords = file_get_contents(IISSSITE."/sysinfo.php?var=sysconfig&datavalue=reviewwords");
		$comments_check = file_get_contents(IISSSITE."/sysinfo.php?var=sysconfig&datavalue=comments_check");
		$_IGLOBAL['ischeck'] = $comments_check ? 0 : 1;//评论审核

		if($disablewords && preg_match('/'.$disablewords.'/i', $string)) {
			if($json){
				return false;
			}else{
				showmessage('内容里含有非法字符');
			}
		}elseif(!empty($replacewords)) {
			 $reparr = explode('|', $replacewords);
             foreach($reparr as $value){
				$rarr = array();
				$rarr = explode('=', $value);
			    $arr['find'][]= isset($rarr[0]) ? '/'.$rarr[0].'/i' : '';
				$arr['replace'][]= isset($rarr[1]) ? $rarr[1] : '';
			 }

			 $string = @preg_replace($arr['find'], $arr['replace'], $string);
		}
		if(!empty($reviewwords)){
			if(preg_match('/'.$reviewwords.'/i', $string)) {
				$_IGLOBAL['ischeck'] = 0;
			}
		}
		
	}
	if($length && strlen($string) > $length) {
		//截断字符
		$wordscut = '';
		if(strtolower(C("DB_CHARSET")) == 'utf-8') {
			//utf8编码
			$n = 0;
			$tn = 0;
			$noc = 0;
			while ($n < strlen($string)) {
				$t = ord($string[$n]);
				if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
					$tn = 1;
					$n++;
					$noc++;
				} elseif(194 <= $t && $t <= 223) {
					$tn = 2;
					$n += 2;
					$noc += 2;
				} elseif(224 <= $t && $t < 239) {
					$tn = 3;
					$n += 3;
					$noc += 2;
				} elseif(240 <= $t && $t <= 247) {
					$tn = 4;
					$n += 4;
					$noc += 2;
				} elseif(248 <= $t && $t <= 251) {
					$tn = 5;
					$n += 5;
					$noc += 2;
				} elseif($t == 252 || $t == 253) {
					$tn = 6;
					$n += 6;
					$noc += 2;
				} else {
					$n++;
				}
				if ($noc >= $length) {
					break;
				}
			}
			if ($noc > $length) {
				$n -= $tn;
			}
			$wordscut = substr($string, 0, $n);
		} else {
			for($i = 0; $i < $length - 1; $i++) {
				if(ord($string[$i]) > 127) {
					$wordscut .= $string[$i].$string[$i + 1];
					$i++;
				} else {
					$wordscut .= $string[$i];
				}
			}
		}
		$string = $wordscut;
	}

	if($out_slashes) {
		$string = saddslashes($string);
	}
	return $string;
}
//事件发布
function feed_add($icon, $title_template='', $title_data=array(), $body_template='', $body_data=array(), $body_general='', $images=array(), $image_links=array(), $target_ids='', $friend='', $appid=3, $returnid=0) {
	global $_IGLOBAL;

	$db = M();

	$feedarr = array(
		'appid' => $appid,//获取appid myop为0
		'icon' => $icon,
		'uid' => $_IGLOBAL['supe_uid'],
		'username' => $_IGLOBAL['supe_username'],
		'dateline' => $_IGLOBAL['timestamp'],
		'title_template' => $title_template,
		'body_template' => $body_template,
		'body_general' => $body_general,
		'image_1' => empty($images[0])?'':$images[0],
		'image_1_link' => empty($image_links[0])?'':$image_links[0],
		'image_2' => empty($images[1])?'':$images[1],
		'image_2_link' => empty($image_links[1])?'':$image_links[1],
		'image_3' => empty($images[2])?'':$images[2],
		'image_3_link' => empty($image_links[2])?'':$image_links[2],
		'image_4' => empty($images[3])?'':$images[3],
		'image_4_link' => empty($image_links[3])?'':$image_links[3],
		'target_ids' => $target_ids,
		'friend' => $friend
	);
	$feedarr = sstripslashes($feedarr);//去掉转义
	$feedarr['title_data'] = serialize(sstripslashes($title_data));//数组转化
	$feedarr['body_data'] = serialize(sstripslashes($body_data));//数组转化
	$feedarr['hash_template'] = md5($feedarr['title_template']."\t".$feedarr['body_template']);//喜好hash
	$feedarr['hash_data'] = md5($feedarr['title_template']."\t".$feedarr['title_data']."\t".$feedarr['body_template']."\t".$feedarr['body_data']);//合并hash
	$feedarr = saddslashes($feedarr);//增加转义
	
	//去重
	$query = $db->query("SELECT feedid FROM iissblog_feed WHERE uid='$feedarr[uid]' AND hash_data='$feedarr[hash_data]' LIMIT 0,1");
	if($oldfeed = $query[0]) {
		$db -> table("iissblog_feed") -> where("feedid={$oldfeed['feedid']}") -> save($feedarr);
		return 0;
	}
	
	if($returnid) {
		$db -> table("iissblog_feed") -> add($feedarr);
		$feedid = $db -> table("iissblog_feed") -> field("max(feedid) feedid") -> find();
		return $feedid['feedid'];
	} else {
		$db -> table("iissblog_feed") -> add($feedarr);
		return 1;
	}
}

/**
* 字符串解密加密
*/
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {

	$ckey_length = 4;	// 随机密钥长度 取值 0-32;
				// 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
				// 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方
				// 当此值为 0 时，则不产生随机密钥

	$key = md5($key ? $key : 'c2J0r9pfn72975jeE4E6HdP11cN6a625Y5Kfd3U2yb0cA7s3Bf28l418Z67fb2J3');
	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

	$cryptkey = $keya.md5($keya.$keyc);
	$key_length = strlen($cryptkey);

	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
	$string_length = strlen($string);

	$result = '';
	$box = range(0, 255);

	$rndkey = array();
	for($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}

	for($j = $i = 0; $i < 256; $i++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}

	for($a = $j = $i = 0; $i < $string_length; $i++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}

	if($operation == 'DECODE') {
		if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		return $keyc.str_replace('=', '', base64_encode($result));
	}
}
/*
 * 获取时间
 */
function getDayAMPM() {
	  $hour = (int)date("H", time());

	  if($hour>=6 && $hour<=12){
	
		  $showtxt = '上午';
	  }elseif($hour>12 && $hour<= 18){
	      $showtxt = '下午';
	  }else{
	      $showtxt = '晚上';
	  }
	
	  return $showtxt;
}
/**
*  清空cookie
*/
function clearcookie() {
	global $_IGLOBAL;

	ssetcookie('auth', '', -86400 * 365);
	$_IGLOBAL['supe_uid'] = 0;
	$_IGLOBAL['supe_username'] = '';
	$_IGLOBAL['member'] = array();
}

/*
*  cookie设置
*/
function ssetcookie($var, $value, $life=0) {
	global $_IGLOBAL, $_SERVER;
	setcookie($var, $value, $life?($_IGLOBAL['timestamp']+$life):0, C('COOKIE_PATH'), C('COOKIE_DOMAIN'), $_SERVER['SERVER_PORT']==443?1:0);
}
/**
* 删除非本站链接
*/
function removelink($matches) {

    if(strpos($matches[3], 'chinaiiss.com') || strpos($matches[3], 'chinaiiss.org') || strpos($matches[3], IISSSITE)) {
		 return $matches[0];
	}

	return $matches[4];
	// echo preg_replace_callback('~<a\s*href=[\"'](http:\/\/[^\/]([^>]*)(["\"\'])?(.*?)\\1>(.*?)<\/a>~i','removelink',$str);
}
//产生form防伪码
function formhash() {
	global $_IGLOBAL;

	if(empty($_IGLOBAL['formhash'])) {
		//$hashadd = defined('IN_ADMINIISS') ? 'Only For Chinaiiss' : '';
		$hashadd = '';
		$_IGLOBAL['formhash'] = substr(md5(substr($_IGLOBAL['timestamp'], 0, -7).'|'.$_IGLOBAL['supe_uid'].'|'.$hashadd), 8, 8);
	}
	return $_IGLOBAL['formhash'];
}

//判断提交是否正确
function submitcheck($var) {
	if(!empty($_POST[$var]) && $_SERVER['REQUEST_METHOD'] == 'POST') {
		if($_POST['formhash'] == formhash()) {
			return true;
		} else {
			showmessage('你的行为疑是非法操作，被终止！', $_SERVER['HTTP_REFERER'], 5);
		}
	} else {
		return false;
	}
}
//去掉slassh
function sstripslashes($string) {
	if(is_array($string)) {
		foreach($string as $key => $val) {
			$string[$key] = sstripslashes($val);
		}
	} else {
		$string = stripslashes($string);
	}
	return $string;
}
/*
* 取消HTML代码
* @string array/string $string: 要处理的数据
* @return: 返回处理后的数据
*/
function shtmlspecialchars($string) {
	if(is_array($string)) {
		foreach($string as $key => $val) {
			$string[$key] = shtmlspecialchars($val);
		}
	} else {
		$string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4})|[a-zA-Z][a-z0-9]{2,5});)/', '&\\1',
			str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $string));
	}
	return $string;
}
//反序列化之前把标记的字节数修改
function mb_unserialize($serial_str) { 
	$out = preg_replace('!s:(\d+):"(.*?)";!se', "'s:'.strlen('$2').':\"$2\";'", $serial_str ); 
	return sstripslashes(unserialize($out)); 
}
//编码转换
function siconv($str, $out_charset, $in_charset='') {

	$in_charset = empty($in_charset)?'utf-8':strtoupper($in_charset);
	$out_charset = strtoupper($out_charset);
	if($in_charset != $out_charset) {
		if (function_exists('iconv') && (@$outstr = iconv("$in_charset//IGNORE", "$out_charset//IGNORE", $str))) {
			return $outstr;
		} elseif (function_exists('mb_convert_encoding') && (@$outstr = mb_convert_encoding($str, $out_charset, $in_charset))) {
			return $outstr;
		}
	}
	return $str;//转换失败
}

//设置静态页面的URL名称
function makesiteurl($fix = ''){
	$url = '';
    $num_args = func_num_args();
    
	if($num_args<4){
	    return '';
	}

	for($i = 1; $i<$num_args; $i++){
		 $url .= func_get_arg($i).'/';
	}
	$fix = $fix ? '.'.$fix : '';
	return IISSSITE.'/'.substr($url,0,-1).$fix;
}
/**
* 取得客户端IP
*/
function getClientIP($format=0) {
	global $_IGLOBAL;

	if(empty($_IGLOBAL['clientip'])) {
		if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
			$onlineip = getenv('HTTP_CLIENT_IP');
		} elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
			$onlineip = getenv('HTTP_X_FORWARDED_FOR');
		} elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
			$onlineip = getenv('REMOTE_ADDR');
		} elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
			$onlineip = $_SERVER['REMOTE_ADDR'];
		}
		preg_match("/[\d\.]{7,15}/", $onlineip, $onlineipmatches);
		$_IGLOBAL['clientip'] = $onlineipmatches[0] ? $onlineipmatches[0] : 'unknown';
	}

	return $_IGLOBAL['clientip'];

}
/*pk分页*/
function pkPage($num, $perpage, $curpage, $url, $type=0)
{

 $mutipage = '';

 if($num > $perpage){

    //一个列表显示几个数字
	$pn = 10;
	$offset = $pn/2;
	$pages = @ceil($num / $perpage);
    $total = 0;
	if($pn > $pages){
	    $start=1;
		$total=$pages;
	}else{
		$start = $curpage - $offset;
		$total = $start + $pn -1;

		if($start < 1){
			$total = $curpage +1 - $start;
			$start = 1;
			if($total - $start < $pn){
			   $total = $pn;
			}
		}elseif($total > $pages){
			$start = $pages - $pn + 1;
			$total = $pages;
		}

	}
	if($type){
		$fblock = '';
		$pblock = '&lt;&lt;上一页';
		$nblock = '下一页&gt;&gt;';
		$lblock = '';
	}else{
		$fblock = '[首页]';
		$pblock = '[上一页]';
		$nblock = '[下一页]';
		$lblock = '[末页]';
	}
	if($curpage>1){
		$mutipage .= '<a href="'.str_replace('{pagenum}', '1', $url).'">'.$fblock.'</a>';
		$mutipage .= '<a href="'.str_replace('{pagenum}', ($curpage-1), $url).'">'.$pblock.'</a>';
	}
	if($type){
		$mutipage .=  '<span id="pagenum">'.pageNumComm($start, $total, $curpage, $url, 'nowpage').'</span>';
	}else{
		$mutipage .=  pageNumComm($start, $total, $curpage, $url);
	}

	if($curpage < $pages){
	   
	   $mutipage .= '<a href="'.str_replace('{pagenum}', ($curpage+1), $url).'">'.$nblock.'</a>';
	   $mutipage .= '<a href="'.str_replace('{pagenum}', $pages, $url).'">'.$lblock.'</a>';
	}

 }else{
	$multipage = 1;
 }

return $mutipage;

}
/**
* 评论数字列
*/
function pageNumComm($start, $total, $cur, $url, $curstyle='', $linkstyle = '', $linktag='')
{
 $numlist = '';
 for($i=$start; $i<=$total; $i++){
     $cururl =  str_replace('{pagenum}', ''.$i, $url);
     $numlist .= '<a href="';
     $numlist .= ($cur == $i && !empty($curstyle)) ? '#" class="'.$curstyle.'"' : $cururl.'"'.(!empty($linkstyle) ? ' class="'.$linkstyle.'"' : '');
	 $numlist .= '>'.$i.'</a>';
 }

 return $numlist;
}
/*
 * 网站统一分页
* @param I $num,进行分页的总数
* @param I $perpage,每页显示条数
* @param I $curpage,当前页
* @param S $url,分页的url地址
* @param S $style,分页链接跳转样式(评论comment，正文页mutipage，文章列表页list,专题列表页spec,pk台列表页pkt,PK台查看全部评论页app,全球议事厅评论底部conference,	“你知道吗”我的问答中心knowcenter,评论正文页commentart,专栏作家列表页send,普通图库正文页picview)
* @return S,返回分页字符串
*/	
function showPage($num, $perpageNum="20" , $curpage="1" , $url , $style="comment") {
	if($num < 0 || $perpageNum <= 0 || $curpage < 1 || $url ==''){
		return false;
	}
	//判断是否是这三种
	if(!in_array($style, array('comment','mutipage','list','spec','pkt','app','conference','knowcenter','commentart','send','picview'))){
		return false;
	}

	//总页数
	$pageCount = ceil ( $num / $perpageNum );

	if($pageCount<=1) return '';

	//分页字符串
	$result = '<div class="pages_fy"><div class="pages">';


	//上一页
	if ($curpage > 1) {
		if ($style == "list" || $style == "spec") {
			$href = str_replace ( '.html', '/' . ($curpage - 1) . '.html', $url );
		} else if ($style == "mutipage" || $style == "picview") {
			$href = ($curpage-2<=0) ?  $url : str_replace('.html', '_'.($curpage - 2).'.html', $url);
		} else {
			$href = str_replace ( '{pagenum}', ($curpage - 1), $url );
		}

		$result .= '<a href="' . $href . '">上一页</a>';
	}

	//总体页码
	//小于等于10页时，页码全部展示。
	if ( $pageCount <= 10) {
		for($i = 1; $i <= $pageCount; $i ++) {
			if ($i == $curpage) {
				$result .= "<span class='cur'>" . $i . "</span>";
			} else {
				if ($style == "list" || $style == "spec") {
					$href = str_replace ( '.html', '/' . $i . '.html', $url );
				} else if ($style == "mutipage" || $style == "picview") {
					$href = ($i-1<=0) ?  $url : str_replace('.html', '_'.($i - 1).'.html', $url);
				} else {
					$href = str_replace ( '{pagenum}', $i, $url );
				}

				$result .= "<a href=" . $href . ">" . $i . "</a>";
			}
		}
	} else {
		//第一页和第二页
		for($i = 1; $i <= 2; $i ++) {
			if ($i == $curpage) {
				$result .= "<span class='cur'>" . $curpage . "</span>";
			} else {
				if ($style == "list" || $style == "spec") {
					$result .= "<a href=" . str_replace ( '.html', '/' . $i . '.html', $url ) . ">$i</a>";
				} else if ($style == "mutipage" || $style == "picview") {
					if($i==1){
						$result .= "<a href=" . $url . ">1</a>";
					}else{
						$result .= "<a href=" . str_replace ( '.html', '_' . ($i - 1) . '.html', $url ) . ">$i</a>";
					}
				} else {
					$result .= "<a href=" . str_replace ( '{pagenum}', $i, $url ) . ">$i</a>";
				}
			}
		}    		
		
		if($curpage<6){
			//当前页是否是第三页
			if($curpage==3){
				$result .= "<span class='cur'>" . $curpage . "</span>";
				//第4，5页
				if ($style == "list" || $style == "spec") {
					$result .= "<a href=" . str_replace ( '.html', '/' . 4 . '.html', $url ) . ">4</a>";
					$result .= "<a href=" . str_replace ( '.html', '/' . 5 . '.html', $url ) . ">5</a>";
				} else if ($style == "mutipage" || $style == "picview") {
					$result .= "<a href=" . str_replace ( '.html', '_' .  3 . '.html', $url ) . ">4</a>";
					$result .= "<a href=" . str_replace ( '.html', '_' .  4 . '.html', $url ) . ">5</a>";
				} else {
					$result .= "<a href=" . str_replace ( '{pagenum}', 4, $url ) . ">4</a>";
					$result .= "<a href=" . str_replace ( '{pagenum}', 5, $url ) . ">5</a>";
				}
			}else{
				if ($style == "list" || $style == "spec") {
					$result .= "<a href=" . str_replace ( '.html', '/' . 3 . '.html', $url ) . ">3</a>";
				} else if ($style == "mutipage" || $style == "picview") {
					$result .= "<a href=" . str_replace ( '.html', '_' . 2 . '.html', $url ) . ">3</a>";
				} else {
					$result .= "<a href=" . str_replace ( '{pagenum}', 3, $url ) . ">3</a>";
				}
			}
			
			//当前页是否是第二页
			if($curpage==2){
				if ($style == "list" || $style == "spec") {
					$result .= "<a href=" . str_replace ( '.html', '/' . 4 . '.html', $url ) . ">4</a>";
				} else if ($style == "mutipage" || $style == "picview") {
					$result .= "<a href=" . str_replace ( '.html', '_' . 3 . '.html', $url ) . ">4</a>";
				} else {
					$result .= "<a href=" . str_replace ( '{pagenum}', 4, $url ) . ">4</a>";
				}
			}
			//当前页是否是第四页
			if($curpage==4){
				$result .= "<span class='cur'>" . $curpage . "</span>";
				//第5,6页
				if ($style == "list" || $style == "spec") {
					$result .= "<a href=" . str_replace ( '.html', '/' . 5 . '.html', $url ) . ">5</a>";
					$result .= "<a href=" . str_replace ( '.html', '/' . 6 . '.html', $url ) . ">6</a>";
				} else if ($style == "mutipage" || $style == "picview") {
					$result .= "<a href=" . str_replace ( '.html', '_' .  4 . '.html', $url ) . ">5</a>";
					$result .= "<a href=" . str_replace ( '.html', '_' .  5 . '.html', $url ) . ">6</a>";
				} else {
					$result .= "<a href=" . str_replace ( '{pagenum}', 5, $url ) . ">5</a>";
					$result .= "<a href=" . str_replace ( '{pagenum}', 6, $url ) . ">6</a>";
				}
			}
			//当前页是否是第五页
			if($curpage==5){
				//第四页
				if ($style == "list" || $style == "spec") {
					$result .= "<a href=" . str_replace ( '.html', '/' . 4 . '.html', $url ) . ">4</a>";
				} else if ($style == "mutipage" || $style == "picview") {
					$result .= "<a href=" . str_replace ( '.html', '_' . 3 . '.html', $url ) . ">4</a>";
				} else {
					$result .= "<a href=" . str_replace ( '{pagenum}', 4, $url ) . ">4</a>";
				}
				$result .= "<span class='cur'>" . $curpage . "</span>";
				//加5,6页
				if ($style == "list" || $style == "spec") {
					$result .= "<a href=" . str_replace ( '.html', '/' . 6 . '.html', $url ) . ">6</a>";
					$result .= "<a href=" . str_replace ( '.html', '/' . 7 . '.html', $url ) . ">7</a>";
				} else if ($style == "mutipage" || $style == "picview") {
					$result .= "<a href=" . str_replace ( '.html', '_' . 5 . '.html', $url ) . ">6</a>";
					$result .= "<a href=" . str_replace ( '.html', '_' . 6 . '.html', $url ) . ">7</a>";
				} else {
					$result .= "<a href=" . str_replace ( '{pagenum}', 6, $url ) . ">6</a>";
					$result .= "<a href=" . str_replace ( '{pagenum}', 7, $url ) . ">7</a>";
				}
			}
			$result .= "<span style='border:0px solid;'>...</span>";
		}else if($curpage>=6 && $curpage<=$pageCount-5){
			if ($style == "list" || $style == "spec") {
				$hrefPre = str_replace ( '.html', '/' . ($curpage - 1) . '.html', $url );
				$hrefNext=str_replace ( '.html', '/' . ($curpage + 1) . '.html', $url );
			} else if ($style == "mutipage" || $style == "picview") {
				$hrefPre = ($curpage-2<=0) ?  $url : str_replace('.html', '_'.($curpage - 2) .'.html', $url);
				$hrefNext=str_replace ( '.html', '_' . $curpage  . '.html', $url );
			} else {
				$hrefPre = str_replace ( '{pagenum}', ($curpage - 1), $url );
				$hrefNext=str_replace ( '{pagenum}', ($curpage + 1), $url );
			}
			$result .= "<span style='border:0px solid;'>...</span><a href=" . $hrefPre . ">" . ($curpage - 1) . "</a>";
			$result .= "<span class='cur'>" . $curpage . "</span>";
			$result .= "<a href=" . $hrefNext . ">" . ($curpage + 1) . "</a><span style='border:0px solid;'>...</span>";
		}else{
			$result .= "<span style='border:0px solid;'>...</span>";
			//当前页是否是倒数第五页
			if($curpage==$pageCount-4){
				//倒数第七页，第六页
				if ($style == "list" || $style == "spec") {
					$result .= "<a href=" . str_replace ( '.html', '/' . ($pageCount-6) . '.html', $url ) . ">".($pageCount-6) . "</a>";
					$result .= "<a href=" . str_replace ( '.html', '/' . ($pageCount-5) . '.html', $url ) . ">".($pageCount-5) . "</a>";
				} else if ($style == "mutipage" || $style == "picview") {
					$result .= "<a href=" . str_replace ( '.html', '_' .($pageCount-7). '.html', $url ) . ">".($pageCount-6) . "</a>";
					$result .= "<a href=" . str_replace ( '.html', '_' .($pageCount-6). '.html', $url ) . ">".($pageCount-5) . "</a>";
				} else {
					$result .= "<a href=" . str_replace ( '{pagenum}', ($pageCount-6), $url ) . ">".($pageCount-6) . "</a>";
					$result .= "<a href=" . str_replace ( '{pagenum}',($pageCount-5), $url ) . ">".($pageCount-5). "</a>";
				}
				$result .= "<span class='cur'>" . $curpage . "</span>";
				if ($style == "list" || $style == "spec") {
					$result .= "<a href=" . str_replace ( '.html', '/' . ($curpage+1) . '.html', $url ) . ">". ($curpage+1) . "</a>";
				} else if ($style == "mutipage" || $style == "picview") {
					$result .= "<a href=" . str_replace ( '.html', '_' . $curpage . '.html', $url ) . ">".($curpage+1) ."</a>";
				} else {
					$result .= "<a href=" . str_replace ( '{pagenum}', ($curpage+1), $url ) . ">".($curpage+1). "</a>";
				}
			}
			
			//当前页是否是倒数第四页
			if($curpage==$pageCount-3){
				//倒数第六页，第五页
				if ($style == "list" || $style == "spec") {
					$result .= "<a href=" . str_replace ( '.html', '/' . ($pageCount-5) . '.html', $url ) . ">" .($pageCount-5). "</a>";
					$result .= "<a href=" . str_replace ( '.html', '/' . ($pageCount-4) . '.html', $url ) . ">" .($pageCount-4). "</a>";
				} else if ($style == "mutipage" || $style == "picview") {
					$result .= "<a href=" . str_replace ( '.html', '_' . ($pageCount-6) . '.html', $url ) . ">" .($pageCount-5) . "</a>";
					$result .= "<a href=" . str_replace ( '.html', '_' . ($pageCount-5) . '.html', $url ) . ">" .($pageCount-4). "</a>";
				} else {
					$result .= "<a href=" . str_replace ( '{pagenum}', ($pageCount-5), $url ) . ">" .($pageCount-5) . "</a>";
					$result .= "<a href=" . str_replace ( '{pagenum}', ($pageCount-4), $url ) . ">" .($pageCount-4) . "</a>";
				}
				$result .= "<span class='cur'>" . $curpage . "</span>";
			}
			
			
			//当前页是否是倒数第二页
			if($curpage==$pageCount-1){
				if ($style == "list" || $style == "spec") {
					$result .= "<a href=" . str_replace ( '.html', '/' .($pageCount-3) . '.html', $url ) . ">".($pageCount-3)."</a>";
				} else if ($style == "mutipage" || $style == "picview") {
					$result .= "<a href=" . str_replace ( '.html', '_' .($pageCount-4) . '.html', $url ) . ">".($pageCount-3) . "</a>";
				} else {
					$result .= "<a href=" . str_replace ( '{pagenum}', ($pageCount-3), $url ) . ">".($pageCount-3). "</a>";
				}
			}
			
			//当前页是否是倒数第三页
			if($curpage==$pageCount-2){
				//倒数第五页，倒数第六页
				if ($style == "list" || $style == "spec") {
					$result .= "<a href=" . str_replace ( '.html', '/' . ($pageCount-4) . '.html', $url ) . ">" . ($pageCount-4) . "</a>";
					$result .= "<a href=" . str_replace ( '.html', '/' . ($pageCount-3) . '.html', $url ) . ">" . ($pageCount-3) . "</a>";
				} else if ($style == "mutipage" || $style == "picview") {
					$result .= "<a href=" . str_replace ( '.html', '_'. ($pageCount-5) . '.html', $url ) . ">" . ($pageCount-4) . "</a>";
					$result .= "<a href=" . str_replace ( '.html', '_' . ($pageCount-4) . '.html', $url ) . ">" . ($pageCount-3) . "</a>";
				} else {
					$result .= "<a href=" . str_replace ( '{pagenum}', ($pageCount-4), $url ) . ">" . ($pageCount-4) . "</a>";
					$result .= "<a href=" . str_replace ( '{pagenum}',($pageCount-3), $url ) . ">" . ($pageCount-3) . "</a>";
				}
				$result .= "<span class='cur'>" . $curpage . "</span>";
			}else{
				if ($style == "list" || $style == "spec") {
					$result .= "<a href=" . str_replace ( '.html', '/' . ($pageCount-2) . '.html' , $url ) . ">" . ($pageCount-2) . "</a>";
				} else if ($style == "mutipage" || $style == "picview") {
					$result .= "<a href=" . str_replace ( '.html', '_' . ($pageCount-3) . '.html', $url ) . "> ". ($pageCount-2) . "</a>";
				} else {
					$result .= "<a href=" . str_replace ( '{pagenum}', ($pageCount-2), $url ) . ">" .($pageCount-2) . "</a>";
				}
			}
		}
		
		
		
		
		//最后一页和倒数第二页
		for($i = $pageCount-1; $i <= $pageCount; $i ++) {
			if ($i == $curpage) {
				$result .= "<span class='cur'>" . $i . "</span>";
			} else {
				if ($style == "list" || $style == "spec") {
					$result .= "<a href=" . str_replace ( '.html', '/' . $i . '.html', $url ) . ">$i</a>";
				} else if ($style == "mutipage" || $style == "picview") {
					$result .= "<a href=" . str_replace ( '.html', '_' . ($i-1) . '.html', $url ) . ">$i</a>";
				} else {
					$result .= "<a href=" . str_replace ( '{pagenum}', $i, $url ) . ">$i</a>";
				}
			}
		}
	}

	//展示下一页
	if ($curpage < $pageCount) {
		if ($style == "list" || $style == "spec") {
			$href = str_replace ( '.html', '/' . ($curpage + 1) . '.html', $url );
		} else if ($style == "mutipage" || $style == "picview") {
			$href = str_replace ( '.html', '_' . $curpage . '.html', $url );
		} else {
			$href = str_replace ( '{pagenum}', ($curpage + 1), $url );
		}
		$result .= '<a href="' . $href . '">下一页</a>';
	}
	if ( $pageCount > 10) {
		$result .= "<span class='jump_page'><p class='fl'>至</p> <input class='int_jump' type='text'id='int_jump'/> 页</span>";
		$result .= '<a href="###" target="_self" id="jump" onclick="page(' . "'$style'" .');">跳转</a>';
		$result .= '<input type="hidden" id="pagecount" value="' . $pageCount . '" ><input type="hidden" id="pageUrl" value="'.$url.'"><input type="hidden" id="style" value="' . $style . '" >';
	}
	
	$result .= '</div></div>';
	return $result;
}
