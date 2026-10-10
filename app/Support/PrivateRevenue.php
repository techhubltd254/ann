<?php
namespace App\Support;
final class PrivateRevenue {
 public const KEYS=['fee_breakdown','feebreakdown','fee_cascade','pipeline_cascade','pipeline_fee_breakdown','take_rate','take_rate_pct','fee_rate','pool_share','commission_amount','commission_rate','platform_commission','platform_fee_rate','county_share','institution_share','national_share','kicc_share','producer_net','economics'];
 public static function redact(mixed $value):mixed {
  if(!is_array($value))return $value;
  $out=[];foreach($value as $k=>$v){$normalized=strtolower(preg_replace('/(?<!^)[A-Z]/','_$0',(string)$k));
   if(in_array(strtolower((string)$k),self::KEYS,true)||in_array($normalized,self::KEYS,true))continue;
   $out[$k]=self::redact($v);
  }return $out;
 }
}
