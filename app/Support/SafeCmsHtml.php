<?php
namespace App\Support;
class SafeCmsHtml {
 public static function clean(?string $html):string {
  $doc=new \DOMDocument();$old=libxml_use_internal_errors(true);$doc->loadHTML('<?xml encoding="UTF-8"><div id="cms-safe">'.($html??'').'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);libxml_clear_errors();libxml_use_internal_errors($old);
  $safe=['div','p','br','strong','b','em','i','u','ul','ol','li','h2','h3','h4','blockquote','a','table','thead','tbody','tr','td','th','span'];
  $nodes=iterator_to_array($doc->getElementsByTagName('*'));foreach(array_reverse($nodes) as $n){if(!in_array(strtolower($n->nodeName),$safe,true)){if($n->parentNode)$n->parentNode->removeChild($n);continue;}foreach(iterator_to_array($n->attributes) as $a){if($a->name==='href'&&$n->nodeName==='a'&&preg_match('~^(https://|/|mailto:|tel:)~i',trim($a->value)))continue;if($a->name==='id'&&$a->value==='cms-safe')continue;$n->removeAttribute($a->name);}}
  $root=$doc->getElementById('cms-safe');$out='';if($root)foreach($root->childNodes as $child)$out.=$doc->saveHTML($child);return $out;
 }
}
