<?php
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
Artisan::command('kicc:admin {email} {--name=Administrator}',function(){
 $email=$this->argument('email');if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Valid email required.');
 $existing=User::where('email',$email)->first();if($existing){if(!$existing->is_admin)throw new RuntimeException('Existing non-administrator account; refusing privilege escalation.');$this->info('Administrator already configured; password unchanged.');return;}
 $password=getenv('KICC_BOOTSTRAP_PASSWORD')?:$this->secret('Password (at least 14 characters)');if(strlen($password)<14)throw new RuntimeException('Password must have at least 14 characters.');
 $u=new User(['name'=>$this->option('name'),'email'=>$email,'password'=>$password]);$u->is_admin=true;$u->save();$this->info('Administrator created. Password not displayed.');
})->purpose('Create the first administrator; no hardcoded production credentials.');
Artisan::command('kicc:deployment-check {--local-qa}',function(){
 $qa=$this->option('local-qa');$connection=config('database.default');
 if($connection!=='mysql')throw new RuntimeException('Set DB_CONNECTION=mysql for the droplet release.');
 $c=config('database.connections.mysql');
 foreach(['host','database','username','password'] as $key){if(empty($c[$key])||preg_match('/YOUR_|REPLACE_|<[^>]+>/',$c[$key]))throw new RuntimeException('Complete the SQL connection fields in the runtime environment.');}
 if($qa){if(!in_array(app()->environment(),['testing','local'])||!in_array($c['host'],['localhost','127.0.0.1']))throw new RuntimeException('Local QA is restricted to a local test database.');}
 else {
  if((int)$c['port']!==4000)throw new RuntimeException('Expected TiDB Cloud port 4000.');
  $ca=env('MYSQL_ATTR_SSL_CA');if(!$ca||!is_readable($ca))throw new RuntimeException('Provide a readable TiDB SSL CA file.');
  if(!env('MYSQL_ATTR_SSL_VERIFY_SERVER_CERT',true))throw new RuntimeException('Do not disable server certificate verification.');
  if(!str_starts_with((string)config('app.url'),'https://')||str_contains((string)config('app.url'),'YOUR_'))throw new RuntimeException('Set APP_URL to the real HTTPS domain.');
 }
 try{\Illuminate\Support\Facades\DB::select('SELECT 1');$version=\Illuminate\Support\Facades\DB::selectOne('SELECT VERSION() AS version')->version;}
 catch(\Throwable $e){throw new RuntimeException('SQL connection failed. Check host, prefixed SQL username, password, TLS CA and network access.');}
 if(\Illuminate\Support\Facades\Schema::hasTable('counties')||(\Illuminate\Support\Facades\Schema::hasTable('media_assets')&&!\Illuminate\Support\Facades\Schema::hasTable('records')))throw new RuntimeException('Legacy schema detected. Use a NEW database; the original live schema must not be overwritten.');
 if(!$qa&&!str_contains(strtolower($version),'tidb'))throw new RuntimeException('Server is not identifiable as TiDB; confirm the target SQL endpoint.');
 if(preg_match('/TiDB-v([0-9]+\.[0-9]+\.[0-9]+)/i',$version,$m)&&version_compare($m[1],'6.6.0','<'))throw new RuntimeException('TiDB >=6.6 required for the foreign-key schema; >=8.5 recommended.');
 $this->info($qa?'Local MySQL-compatible QA preflight passed (not TiDB certification).':'TiDB SQL connectivity and schema-isolation preflight passed.');
})->purpose('Validate runtime SQL/TLS settings and refuse the legacy schema.');
Artisan::command('kicc:import-legacy-data',function(){$cmd=new \App\Console\Commands\ImportLegacyData;return $cmd->handle();})->purpose('Import data from the legacy kicc database into Record-based schema.');
