<?php 

/*
 * PHP Number Base Conversion Functions
 * Version 1.0 - February 2004
 * Version 2.0 - January 2005 - converted to using bcmath
 * Version 2.1 - September 2005 - added decimal point conversion ability
 * (c) 2004,2005 Paul Gregg <pgregg@pgregg.com>
 * http://www.pgregg.com
 *
 * Function: Arbitrary Number Base conversion from base 2 - 62
 * This file should be included by other php scripts
 * For normal base 2 - 36 conversion use the built in base_convert function
 *
 * Open Source Code:   If you use this code on your site for public
 * access (i.e. on the Internet) then you must attribute the author and
 * source web site: http://www.pgregg.com/projects/
 * You must also make this original source code available for download
 * unmodified or provide a link to the source.  Additionally you must provide
 * the source to any modified or translated versions or derivatives.
 *
 */

Function base_dec2base($iNum, $iBase, $iScale=0) { // cope with base 2..62
  $LDEBUG = FALSE;
  $sChars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
  $sResult = ''; // Store the result

  // special case for Base64 encoding
  if ($iBase == 64)
   $sChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';

  $sNum = is_integer($iNum) ? "$iNum" : (string)$iNum;
  $iBase = intval($iBase); // incase it is a string or some weird decimal

  // Check to see if we are an integer or real number
  if (strpos($sNum, '.') !== FALSE) {
    list ($sNum, $sReal) = explode('.', $sNum, 2);
    $sReal = '0.' . $sReal;
  } else
    $sReal = '0';

  while (bccomp($sNum, 0, $iScale) != 0) { // still data to process
    $sRem = bcmod($sNum, $iBase); // calc the remainder
    $sNum = bcdiv( bcsub($sNum, $sRem, $iScale), $iBase, $iScale );
    $sResult = $sChars[$sRem] . $sResult;
  }
  if ($sReal != '0') {
    $sResult .= '.';
    $fraciScale = $iScale;
    while($fraciScale-- && bccomp($sReal, 0, $iScale) != 0) { // still data to process
      if ($LDEBUG) print "<br> -> $sReal * $iBase = ";
      $sReal = bcmul($sReal, $iBase, $iScale); // multiple the float part with the base
      if ($LDEBUG) print "$sReal  => ";
      $sFrac = 0;
      if (bccomp($sReal ,1, $iScale) > -1)
        list($sFrac, $dummy) = explode('.', $sReal, 2); // get the intval
      if ($LDEBUG) print "$sFrac\n";
      $sResult .= $sChars[$sFrac];
      $sReal = bcsub($sReal, $sFrac, $iScale);
    }
  }

  return $sResult;
}


Function base_base2dec($sNum, $iBase=0, $iScale=0) {
  $sChars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
  $sResult = '';

  $iBase = intval($iBase); // incase it is a string or some weird decimal

  // special case for Base64 encoding
  if ($iBase == 64)
   $sChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';

  // clean up the input string if it uses particular input formats
  switch ($iBase) {
    case 16: // remove 0x from start of string
      if (strtolower(substr($sNum, 0, 2)) == '0x') $sNum = substr($sNum, 2);
      break;
    case 8: // remove the 0 from the start if it exists - not really required
      if (strpos($sNum, '0')===0) $sNum = substr($sNum, 1);
      break;
    case 2: // remove an 0b from the start if it exists
      if (strtolower(substr($sNum, 0, 2)) == '0b') $sNum = substr($sNum, 2);
      break;
    case 64: // remove padding chars: =
      $sNum = str_replace('=', '', $sNum);
      break;
    default: // Look for numbers in the format base#number,
             // if so split it up and use the base from it
      if (strpos($sNum, '#') !== false) {
        list ($sBase, $sNum) = explode('#', $sNum, 2);
        $iBase = intval($sBase);  // take the new base
      }
      if ($iBase == 0) {
        print("base_base2dec called without a base value and not in base#number format");
        return '';
      }
      break;
  }

  // Convert string to upper case since base36 or less is case insensitive
  if ($iBase < 37) $sNum = strtoupper($sNum);

  // Check to see if we are an integer or real number
  if (strpos($sNum, '.') !== FALSE) {
    list ($sNum, $sReal) = explode('.', $sNum, 2);
    $sReal = '0.' . $sReal;
  } else
    $sReal = '0';


  // By now we know we have a correct base and number
  $iLen = strlen($sNum);
  
  // Now loop through each digit in the number
  for ($i=$iLen-1; $i>=0; $i--) {
    $sChar = $sNum[$i]; // extract the last char from the number
    $iValue = strpos($sChars, $sChar); // get the decimal value
    if ($iValue > $iBase) {
      print("base_base2dec: $sNum is not a valid base $iBase number");
      return '';
    }
    // Now convert the value+position to decimal
    $sResult = bcadd($sResult, bcmul( $iValue, bcpow($iBase, ($iLen-$i-1))) );
  }

  // Now append the real part
  if (strcmp($sReal, '0') != 0) {
    $sReal = substr($sReal, 2); // Chop off the '0.' characters
    $iLen = strlen($sReal);
    for ($i=0; $i<$iLen; $i++) {
      $sChar = $sReal[$i]; // extract the first, second, third, etc char
      $iValue = strpos($sChars, $sChar); // get the decimal value
      if ($iValue > $iBase) {
        print("base_base2dec: $sNum is not a valid base $iBase number");
        return '';
      }
      $sResult = bcadd($sResult, bcdiv($iValue, bcpow($iBase, ($i+1)), $iScale), $iScale);
    }
  }

  return $sResult;
}
    
Function base_base2base($iNum, $iBase, $oBase, $iScale=0) {

  if ($iBase != 10) $oNum = base_base2dec($iNum, $iBase, $iScale);
  else $oNum = $iNum;
  $oNum = base_dec2base($oNum, $oBase, $iScale);
  return $oNum;

}


/**
 * Translates a number to a short alhanumeric version
 *
 * Translated any number up to 9007199254740992
 * to a shorter version in letters e.g.:
 * 9007199254740989 --> PpQXn7COf
 *
 * specifiying the second argument true, it will
 * translate back e.g.:
 * PpQXn7COf --> 9007199254740989
 *
 * this function is based on any2dec && dec2any by
 * fragmer[at]mail[dot]ru
 * see: http://nl3.php.net/manual/en/function.base-convert.php#52450
 *
 * If you want the alphaID to be at least 3 letter long, use the
 * $pad_up = 3 argument
 *
 * In most cases this is better than totally random ID generators
 * because this can easily avoid duplicate ID's.
 * For example if you correlate the alpha ID to an auto incrementing ID
 * in your database, you're done.
 *
 * The reverse is done because it makes it slightly more cryptic,
 * but it also makes it easier to spread lots of IDs in different
 * directories on your filesystem. Example:
 * $part1 = substr($alpha_id,0,1);
 * $part2 = substr($alpha_id,1,1);
 * $part3 = substr($alpha_id,2,strlen($alpha_id));
 * $destindir = "/".$part1."/".$part2."/".$part3;
 * // by reversing, directories are more evenly spread out. The
 * // first 26 directories already occupy 26 main levels
 *
 * more info on limitation:
 * - http://blade.nagaokaut.ac.jp/cgi-bin/scat.rb/ruby/ruby-talk/165372
 *
 * if you really need this for bigger numbers you probably have to look
 * at things like: http://theserverpages.com/php/manual/en/ref.bc.php
 * or: http://theserverpages.com/php/manual/en/ref.gmp.php
 * but I haven't really dugg into this. If you have more info on those
 * matters feel free to leave a comment.
 *
 * @author  Kevin van Zonneveld <kevin@vanzonneveld.net>
 * @author  Simon Franz
 * @author  Deadfish
 * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
 * @version   SVN: Release: $Id: alphaID.inc.php 344 2009-06-10 17:43:59Z kevin $
 * @link    http://kevin.vanzonneveld.net/
 *
 * @param mixed   $in    String or long input to translate
 * @param boolean $to_num  Reverses translation when true
 * @param mixed   $pad_up  Number or boolean padds the result up to a specified length
 * @param string  $passKey Supplying a password makes it harder to calculate the original ID
 *
 * @return mixed string or long
 */
function alphaID($in, $to_num = false, $pad_up = false, $passKey = null)
{
  $index = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
  if ($passKey !== null) {
    // Although this function's purpose is to just make the
    // ID short - and not so much secure,
    // with this patch by Simon Franz (http://blog.snaky.org/)
    // you can optionally supply a password to make it harder
    // to calculate the corresponding numeric ID
 
    for ($n = 0; $n<strlen($index); $n++) {
      $i[] = substr( $index,$n ,1);
    }
 
    $passhash = hash('sha256',$passKey);
    $passhash = (strlen($passhash) < strlen($index))
      ? hash('sha512',$passKey)
      : $passhash;
 
    for ($n=0; $n < strlen($index); $n++) {
      $p[] =  substr($passhash, $n ,1);
    }
 
    array_multisort($p,  SORT_DESC, $i);
    $index = implode($i);
  }
 
  $base  = strlen($index);
 
  if ($to_num) {
    // Digital number  <<--  alphabet letter code
    $in  = strrev($in);
    $out = 0;
    $len = strlen($in) - 1;
    for ($t = 0; $t <= $len; $t++) {
      $bcpow = bcpow($base, $len - $t);
      $out   = $out + strpos($index, substr($in, $t, 1)) * $bcpow;
    }
 
    if (is_numeric($pad_up)) {
      $pad_up--;
      if ($pad_up > 0) {
        $out -= pow($base, $pad_up);
      }
    }
    $out = sprintf('%F', $out);
    $out = substr($out, 0, strpos($out, '.'));
  } else {
    // Digital number  -->>  alphabet letter code
    if (is_numeric($pad_up)) {
      $pad_up--;
      if ($pad_up > 0) {
        $in += pow($base, $pad_up);
      }
    }
 
    $out = "";
    for ($t = floor(log($in, $base)); $t >= 0; $t--) {
      $bcp = bcpow($base, $t);
      $a   = floor($in / $bcp) % $base;
      $out = $out . substr($index, $a, 1);
      $in  = $in - ($a * $bcp);
    }
    $out = strrev($out); // reverse
  }
 
  return $out;
}
