<?php
/**
 * Created by PhpStorm.
 * User: Admin2
 * Date: 2015/5/7
 * Time: 13:50
 */

namespace C\lib {

	class Text {

		public static function random($length = 8, $s = ['L', 'N']) {

			static $a = ['U' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'L' => 'abcdefghijklmnopqrstuvwxyz', 'N' => '0123456789'];
			$source = '';
			foreach ($s as $i) {
				switch ($i) {
					case 'U':
					case 'L':
					case 'N':
						$source .= $a[$i];
						break;
					default:
						$source .= $i;
				}
			}
			$sourceLength = strlen($source);
			$temp = '';
			for ($i = $length; $i; $i--)
				$temp .= $source[rand(0, $sourceLength - 1)];
			return $temp;
		}
		
		// --- 是否是中国大陆的手机号 ---

		public static function isPhone($p) {

			if (preg_match('/^1[0-9]{10}$/', $p)) return true;
			else return false;

		}
		
		// --- 是否是
		
		public static function isIdCard($idcard) {
			
			if(strlen($idcard) != 18)
				return false;

			// 取出本体码  
			$idcardBase = substr($idcard, 0, 17);

			// 取出校验码  
			$verifyCode = substr($idcard, 17, 1);

			// 加权因子  
			$factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);

			// 校验码对应值  
			$verifyCodeList = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');

			// 根据前17位计算校验码  
			$total = 0;
			for($i=0; $i<17; $i++)
				$total += substr($idcardBase, $i, 1)*$factor[$i];

			// 取模  
			$mod = $total % 11;

			// 比较校验码  
			if($verifyCode == $verifyCodeList[$mod])
				return true;
			else
				return false;
		}

	}

}

