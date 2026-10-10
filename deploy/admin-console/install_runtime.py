from pathlib import Path
import re,shutil
b=Path('/opt/kicc-backups/admin-product-20261010/runtime');b.mkdir(parents=True,exist_ok=True)
for f in Path('/etc/php/8.4/fpm/conf.d').glob('*uploads.ini'):shutil.copy2(f,b/f.name)
p=Path('/etc/php/8.4/fpm/conf.d/zzzz-kicc-uploads.ini');p.write_text('upload_max_filesize=2G\npost_max_size=2100M\nmax_execution_time=600\nmax_input_time=600\nmemory_limit=1024M\nmax_file_uploads=50\n')
p=Path('/etc/nginx/sites-enabled/kicc');s=p.read_text();s,n=re.subn(r'    location /admin \{[^\n]+\}', '    location /admin { try_files $uri $uri/ /index.php?$query_string; }',s);assert n==1,n;p.write_text(s)
p=Path('/opt/kicc-laravel/.env');shutil.copy2(p,b/'env-before');s=p.read_text();s=re.sub(r'^APP_ENV=.*$', 'APP_ENV=production',s,flags=re.M);s=re.sub(r'^APP_DEBUG=.*$', 'APP_DEBUG=false',s,flags=re.M);p.write_text(s)
print('Admin routed to Laravel, not the Java frontend. Authoritative FPM limits and production error handling installed.')
