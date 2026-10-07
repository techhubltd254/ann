<?php
namespace Database\Seeders;
use App\Models\Record;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
class ReferenceContentSeeder extends Seeder {
 private function save(string $type,array $r,string $source,string $status='draft'):void{
  $name=$r['name'];$slug=$r['slug']??Str::slug($name);$description=$r['description']??$r['desc']??null;$payload=$r;unset($payload['name'],$payload['slug'],$payload['description'],$payload['desc'],$payload['status']);$payload['source_file']=$source;$payload['requires_editorial_review']=true;
  Record::firstOrCreate(['type'=>$type,'slug'=>$slug],['name'=>$name,'description'=>$description,'status'=>$status,'payload'=>$payload]);
 }
 public function run():void{
  foreach(json_decode(file_get_contents(database_path('data/counties.json')),true) as $r){$r['source_id']=$r['id'];unset($r['id']);$this->save('counties',$r,'database/data/counties.json','published');}
  foreach(json_decode(file_get_contents(database_path('data/sectors.json')),true) as $r){$r['source_id']=$r['id'];$r['county_ids']=$r['counties']??[];unset($r['id'],$r['counties']);$this->save('sectors',$r,'database/data/sectors.json','published');}
  $reference=json_decode(file_get_contents(database_path('data/original-reference.json')),true);
  foreach(['venues','ministries','agencies','products'] as $type)foreach($reference[$type]['rows'] as $r){if($type==='products'){$r['category']=$r['cat'];$r['county_slug']=$r['county'];$r['price']=min(array_column($r['variants'],'price'));unset($r['cat'],$r['county']);}if($type==='venues')$r['conflicting_sources']=['database/seeders/KiccServicesSeeder.php'];$this->save($type,$r,$reference[$type]['source_file']);}
  foreach($reference['service_venues']['rows'] as $r){$r['slug']='reference-'.$r['slug'];$this->save('services',$r,$reference['service_venues']['source_file']);}
  foreach($reference['cms_pages']['rows'] as $r){$r['name']=$r['title'];$r['description']=html_entity_decode(strip_tags(preg_replace('/<\/(p|h[1-6]|li|ul)>/i',"\n\n",$r['content'])));unset($r['content']);$this->save('pages',$r,$reference['cms_pages']['source_file']);}
  foreach(['team','timeline','faqs','cms_services'] as $bucket)foreach($reference[$bucket]['rows'] as $r){$type=$bucket==='cms_services'?'services':$bucket;$r['name']=$r['name']??$r['title']??$r['question'];$r['description']=$r['description']??$r['bio']??$r['answer']??null;$this->save($type,$r,$reference[$bucket]['source_file']);}
  foreach(json_decode(file_get_contents(database_path('data/original-pages.json')),true) as $r){$payload=$r['payload'];Record::firstOrCreate(['type'=>'pages','slug'=>$r['slug']],['name'=>$r['name'],'description'=>$r['description'],'status'=>'draft','payload'=>$payload]);}
  Record::firstOrCreate(['type'=>'pages','slug'=>'home'],['name'=>'Home hero','description'=>'Forty-seven county economies arrive here as goods, produce and people — and leave as trade.','status'=>'published','payload'=>['requires_editorial_review'=>true,'source_file'=>'Approved HTML design: parts/06b.home.js']]);
  // Preserve ministry-agency relationships without publishing draft reference rosters.
  foreach(Record::where('type','agencies')->get() as $a){$ministry=Record::where('type','ministries')->get()->first(fn($m)=>($m->payload['code']??null)===($a->payload['ministry']??null));if($ministry&&!$a->parent_id){$a->parent_id=$ministry->id;$a->save();}}
  $this->command?->info('Imported source reference content. Only county/sector navigation and the home container are published. All other reference records remain draft for owner review.');
 }
}
