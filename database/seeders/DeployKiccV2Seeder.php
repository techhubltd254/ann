<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeployKiccV2Seeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('=== KICC V2 Deployment Seeder ===');

        // Step 1: Create kicc_v2 database
        try { DB::statement('CREATE DATABASE IF NOT EXISTS kicc_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'); $this->command->info('✓ kicc_v2 ready'); }
        catch (\Throwable $e) { $this->command->warn('DB: '.$e->getMessage()); }

        // Step 2: Create V2 tables (no cross-DB foreign keys — TiDB limitation)
        $this->createV2Tables();

        // Step 3: Seed reference content (counties, sectors)
        $this->seedReference();

        // Step 4: Import legacy data (institutions, media, products)
        $this->importLegacy();

        // Step 5: Ensure admin user exists
        $this->ensureAdmin();

        $this->command->info('=== Complete ===');
    }

    private function createV2Tables(): void
    {
        DB::statement('CREATE TABLE IF NOT EXISTS kicc_v2.records (id CHAR(36) PRIMARY KEY,type VARCHAR(32) NOT NULL,slug VARCHAR(180) NOT NULL,name VARCHAR(240) NOT NULL,description LONGTEXT NULL,status VARCHAR(16) NOT NULL DEFAULT "draft",payload JSON NULL,parent_id CHAR(36) NULL,revision INT UNSIGNED NOT NULL DEFAULT 1,updated_by BIGINT UNSIGNED NULL,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL,INDEX r_type(type),INDEX r_status(status),UNIQUE r_type_slug(type,slug),INDEX r_parent(parent_id))');
        DB::statement('CREATE TABLE IF NOT EXISTS kicc_v2.media_assets (id CHAR(36) PRIMARY KEY,record_id CHAR(36) NOT NULL,disk VARCHAR(16) NOT NULL,path VARCHAR(500) NOT NULL,original_name VARCHAR(500) NOT NULL,mime VARCHAR(120) NOT NULL,bytes BIGINT UNSIGNED NOT NULL,title VARCHAR(240) NOT NULL,description TEXT NULL,status VARCHAR(16) NOT NULL DEFAULT "draft",format VARCHAR(16) NOT NULL DEFAULT "standard",created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL,INDEX ma_status(status),INDEX ma_record(record_id))');
        DB::statement('CREATE TABLE IF NOT EXISTS kicc_v2.audit_events (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id BIGINT UNSIGNED NULL,action VARCHAR(80) NOT NULL,subject_id VARCHAR(64) NULL,details JSON NULL,created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP)');
        DB::statement('CREATE TABLE IF NOT EXISTS kicc_v2.enquiries (id CHAR(36) PRIMARY KEY,record_id CHAR(36) NOT NULL,name VARCHAR(180) NOT NULL,email VARCHAR(240) NOT NULL,phone VARCHAR(60) NULL,message TEXT NOT NULL,status VARCHAR(16) NOT NULL DEFAULT "new",created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL)');
        $this->command->info('✓ Tables created');
    }

    private function seedReference(): void
    {
        $counties = json_decode(file_get_contents(database_path('data/counties.json')), true);
        foreach ($counties as $c) {
            $c['source_id'] = $c['id']; unset($c['id']);
            $slug = Str::slug($c['name']);
            $p = $c; unset($p['name'],$p['description'],$p['desc']);
            $p['source_file'] = 'database/data/counties.json'; $p['requires_editorial_review'] = true;
            DB::table('kicc_v2.records')->updateOrInsert(
                ['type'=>'counties','slug'=>$slug],
                ['id'=>(string)Str::uuid(),'name'=>$c['name'],'description'=>$c['description']??$c['desc']??null,'status'=>'published','payload'=>json_encode($p),'created_at'=>now(),'updated_at'=>now()]
            );
        }
        $sectors = json_decode(file_get_contents(database_path('data/sectors.json')), true);
        foreach ($sectors as $s) {
            $s['source_id'] = $s['id']; $s['county_ids'] = $s['counties']??[]; unset($s['id'],$s['counties']);
            $slug = Str::slug($s['name']);
            $p = $s; unset($p['name'],$p['description']);
            $p['source_file'] = 'database/data/sectors.json';
            DB::table('kicc_v2.records')->updateOrInsert(
                ['type'=>'sectors','slug'=>$slug],
                ['id'=>(string)Str::uuid(),'name'=>$s['name'],'description'=>$s['description']??null,'status'=>'published','payload'=>json_encode($p),'created_at'=>now(),'updated_at'=>now()]
            );
        }
        $this->command->info('✓ Reference content seeded');
    }

    private function importLegacy(): void
    {
        $idMap = [];
        $insts = DB::table('county_institutions')->orderBy('name')->get();
        foreach ($insts as $inst) {
            $countyRec = DB::table('kicc_v2.records')->where('type','counties')->where('payload->source_id',$inst->county_id)->first();
            $p = ['type'=>$inst->type??null,'location'=>$inst->location??null,'phone'=>$inst->phone??null,'email'=>$inst->email??null,'website'=>$inst->website??null,'headquarters'=>$inst->headquarters??null,'founded_year'=>$inst->founded_year??null,'story'=>$inst->story??null,'latitude'=>$inst->lat??null,'longitude'=>$inst->lng??null,'sector_mappings'=>is_string($inst->sector_mappings??null)?json_decode($inst->sector_mappings,true):($inst->sector_mappings??null),'products'=>is_string($inst->products??null)?json_decode($inst->products,true):($inst->products??null),'county_id'=>$inst->county_id??null,'source_id'=>(int)$inst->id,'source_file'=>'kicc.county_institutions'];
            $uuid = (string)Str::uuid();
            DB::table('kicc_v2.records')->insert(['id'=>$uuid,'type'=>'institutions','slug'=>$inst->slug,'name'=>$inst->name,'description'=>$inst->description??null,'status'=>($inst->isPublished??false)?'published':'draft','parent_id'=>$countyRec?->id,'payload'=>json_encode($p),'created_at'=>$inst->created_at??now(),'updated_at'=>$inst->updated_at??now()]);
            $idMap[$inst->id] = $uuid;
        }
        $this->command->info('✓ '.count($insts).' institutions');

        $assets = DB::table('media_assets')->where('kind','video')->where('status','ready')->get();
        $mc = 0;
        foreach ($assets as $a) {
            $rid = null;
            if (in_array($a->owner_type,['App\\Models\\County','county'])) $rid = DB::table('kicc_v2.records')->where('type','counties')->where('payload->source_id',$a->owner_id)->value('id');
            elseif (in_array($a->owner_type,['App\\Models\\CountyInstitution','institution'])) $rid = $idMap[$a->owner_id]??null;
            if (!$rid) continue;
            try { DB::table('kicc_v2.media_assets')->insert(['id'=>(string)Str::uuid(),'record_id'=>$rid,'disk'=>$a->disk??'r2','path'=>$a->path,'original_name'=>$a->original_name??basename($a->path),'mime'=>$a->mime??'video/mp4','bytes'=>$a->size_bytes??0,'title'=>$a->original_name??'Video','description'=>$a->slot??null,'status'=>'published','format'=>'standard','created_at'=>$a->created_at??now(),'updated_at'=>$a->updated_at??now()]); $mc++; }
            catch (\Throwable $e) { $this->command->warn('⚠ Media #'.$a->id.': '.$e->getMessage()); }
        }
        $this->command->info('✓ '.$mc.' media assets');

        $pc = 0;
        foreach ($insts as $inst) {
            $rid = $idMap[$inst->id]??null; if(!$rid) continue;
            $prods = is_string($inst->products)?json_decode($inst->products,true):$inst->products;
            if(!is_array($prods)) continue;
            foreach($prods as $prod) {
                $n = $prod['name']??'Product'; $s = Str::slug($n.'-'.substr($rid,0,6));
                if(DB::table('kicc_v2.records')->where('type','products')->where('name',$n)->where('parent_id',$rid)->exists()) continue;
                DB::table('kicc_v2.records')->insert(['id'=>(string)Str::uuid(),'type'=>'products','slug'=>$s,'name'=>$n,'description'=>$prod['description']??null,'status'=>'published','parent_id'=>$rid,'payload'=>json_encode(['price'=>$prod['price']??null,'unit'=>$prod['unit']??null,'category'=>$prod['category']??null,'stock'=>$prod['stock']??null,'source_file'=>'kicc.institutions.products']),'created_at'=>now(),'updated_at'=>now()]); $pc++;
            }
        }
        $this->command->info('✓ '.$pc.' products');
    }

    private function ensureAdmin(): void
    {
        try { DB::statement('ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0'); } catch (\Throwable) {}
        DB::table('users')->where('email','admin@kicc.go.ke')->update(['is_admin'=>true]);
        if (!DB::table('users')->where('email','admin@kicc.go.ke')->exists()) {
            DB::table('users')->insert(['name'=>'Administrator','email'=>'admin@kicc.go.ke','password'=>bcrypt('KICC@Admin2026'),'is_admin'=>true,'created_at'=>now(),'updated_at'=>now()]);
        }
        $this->command->info('✓ Admin confirmed');
    }
}