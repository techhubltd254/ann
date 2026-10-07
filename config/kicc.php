<?php
return [
 'types'=>['counties'=>'County portals','sectors'=>'Economic sectors','institutions'=>'Institutions','ministries'=>'Ministries','agencies'=>'Agencies','venues'=>'Venues & rooms','services'=>'Services','products'=>'Products','exhibitions'=>'Exhibitions','screens'=>'Digital screens','streams'=>'Live streams','travel'=>'Travel','trade-agreements'=>'Trade agreements','pages'=>'Page content','team'=>'Leadership & team','timeline'=>'History & timeline','faqs'=>'FAQ','source-assets'=>'Imported source assets'],
 'media_disk'=>env('KICC_MEDIA_DISK','local'),
 'max_upload_kb'=>(int)env('KICC_MAX_UPLOAD_KB',204800),
 'payload_keys'=>['amenities','answer','area_km2','bio','capacity','capital','cat','category','city','code','color','conflicting_sources','content','coolest_month','counties','county','county_ids','county_slug','desc','description','dry_season','economic_zone','email','emoji','featured','former_province','icon','icon_emoji','id','is_active','latitude','longitude','ministry','name','original_name','original_view_sha256','phone','playback_url','population_2024','price','primary_sectors','question','rainy_season','region','requires_editorial_review','slug','sort_order','source_file','source_id','splat_format','splat_url','sub_sectors','tagline','title','tourism_highlights','unit','variants','venue_type','warmest_month','weather_station_id','website','year'],
];
