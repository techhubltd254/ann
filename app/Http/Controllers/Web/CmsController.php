<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FaqItem;
use App\Models\Page;
use App\Models\ServiceItem;
use App\Models\TeamMember;
use App\Models\TimelineEvent;
use App\Models\VideoItem;
use App\Models\Venue;
use Illuminate\Http\Request;

class CmsController extends Controller
{
    // ─── PUBLIC PAGES (read from DB) ───

    public function page($slug)
    {
        $page = Page::where('slug', $slug)->where('is_published', true)->firstOrFail();
        return view('experience.pages.kicc-website.cms-page', compact('page'));
    }

    public function about()
    {
        $page = Page::where('slug', 'about')->firstOrNew(['title' => 'About KICC']);
        $boardMembers = TeamMember::board()->where('is_active', true)->orderBy('sort_order')->get();
        $managementTeam = TeamMember::management()->where('is_active', true)->orderBy('sort_order')->get();
        return view('experience.pages.kicc-website.about', compact('page', 'boardMembers', 'managementTeam'));
    }

    public function mission()
    {
        $page = Page::where('slug', 'mission')->firstOrNew(['title' => 'Mission, Vision & Mandate']);
        return view('experience.pages.kicc-website.mission', compact('page'));
    }

    public function history()
    {
        $page = Page::where('slug', 'history')->firstOrNew(['title' => 'KICC History']);
        $events = TimelineEvent::orderBy('sort_order')->orderBy('year')->get();
        return view('experience.pages.kicc-website.history', compact('page', 'events'));
    }

    public function board()
    {
        $page = Page::where('slug', 'board')->firstOrNew(['title' => 'KICC Board']);
        $members = TeamMember::board()->where('is_active', true)->orderBy('sort_order')->get();
        return view('experience.pages.kicc-website.board', compact('page', 'members'));
    }

    public function management()
    {
        $page = Page::where('slug', 'management')->firstOrNew(['title' => 'KICC Management']);
        $members = TeamMember::management()->where('is_active', true)->orderBy('sort_order')->get();
        return view('experience.pages.kicc-website.management', compact('page', 'members'));
    }

    public function faq()
    {
        $faqs = FaqItem::where('is_published', true)->orderBy('sort_order')->get();
        $categories = FaqItem::where('is_published', true)->select('category')->distinct()->pluck('category');
        return view('experience.pages.kicc-website.faq', compact('faqs', 'categories'));
    }

    public function pricing()
    {
        $page = Page::where('slug', 'pricing')->firstOrNew(['title' => 'Pricing Guideline']);
        $venues = Venue::orderBy('name')->get();
        return view('experience.pages.kicc-website.pricing', compact('page', 'venues'));
    }

    public function videoGallery()
    {
        $videos = VideoItem::where('is_published', true)->orderBy('sort_order')->get();
        return view('experience.pages.kicc-website.video-gallery', compact('videos'));
    }

    public function services()
    {
        $services = ServiceItem::where('is_published', true)->orderBy('sort_order')->get();
        return view('experience.pages.kicc-website.services', compact('services'));
    }

    // ─── ADMIN CMS ───

    public function adminIndex()
    {
        $pages = Page::orderBy('category')->orderBy('sort_order')->get();
        $teamMembers = TeamMember::orderBy('category')->orderBy('sort_order')->get();
        $timelineEvents = TimelineEvent::orderBy('year')->get();
        $faqs = FaqItem::orderBy('sort_order')->get();
        $services = ServiceItem::orderBy('sort_order')->get();
        $videos = VideoItem::orderBy('sort_order')->get();
        return view('experience.pages.cms.admin-index', compact('pages', 'teamMembers', 'timelineEvents', 'faqs', 'services', 'videos'));
    }

    public function updatePage(Request $request, Page $page)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string|max:500',
            'featured_image' => 'nullable|url|max:500',
            'sort_order' => 'nullable|integer',
            'is_published' => 'nullable|boolean',
        ]);
        $data['content']=\App\Support\SafeCmsHtml::clean($data['content']??'');$page->update($data);\Illuminate\Support\Facades\Cache::increment('kicc_cache_version');
        return back()->with('success', "Page '{$page->title}' updated.");
    }

    public function storePage(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:50',
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer','is_published'=>'required|boolean',
        ]);
        $data['content']=\App\Support\SafeCmsHtml::clean($data['content']??'');$data['slug']=\Illuminate\Support\Str::slug($data['title']).'-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(6));
        Page::create($data);
        return back()->with('success', 'Page created.');
    }

    public function storeTeamMember(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:2000',
            'category' => 'required|in:board,management',
            'photo_url' => 'nullable|url|max:500',
        ]);
        TeamMember::create($data);
        return back()->with('success', "Team member {$data['name']} added.");
    }

    public function updateTeamMember(Request $request, TeamMember $member)
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'title' => 'nullable|string|max:255', 'bio' => 'nullable|string|max:2000', 'category' => 'required|in:board,management', 'photo_url' => 'nullable|url|max:500', 'is_active' => 'nullable|boolean']);
        $member->update($data);
        return back()->with('success', 'Team member updated.');
    }

    public function deleteTeamMember(TeamMember $member)
    {
        $member->delete();
                app(\App\Services\CacheSyncService::class)->national();
return back()->with('success', 'Team member removed.');
    }

    public function storeTimelineEvent(Request $request)
    {
        $data = $request->validate(['year' => 'required|string|max:10', 'title' => 'required|string|max:255', 'description' => 'nullable|string|max:1000']);
        TimelineEvent::create($data);
        return back()->with('success', 'Timeline event added.');
    }

    public function deleteTimelineEvent(TimelineEvent $event)
    {
        $event->delete();
                app(\App\Services\CacheSyncService::class)->national();
return back()->with('success', 'Timeline event removed.');
    }

    public function storeFaq(Request $request)
    {
        $data = $request->validate(['question' => 'required|string|max:255', 'answer' => 'nullable|string', 'category' => 'nullable|string|max:50']);
        FaqItem::create($data);
        return back()->with('success', 'FAQ added.');
    }

    public function updateFaq(Request $request, FaqItem $faq)
    {
        $data = $request->validate(['question' => 'required|string|max:255', 'answer' => 'nullable|string', 'ategory' => 'nullable|string|max:50', 'is_published' => 'nullable|boolean']);
        $faq->update($data);
        return back()->with('success', 'FAQ updated.');
    }

    public function deleteFaq(FaqItem $faq)
    {
        $faq->delete();
                app(\App\Services\CacheSyncService::class)->national();
return back()->with('success', 'FAQ removed.');
    }

    public function storeVideo(Request $request)
    {
        $data = $request->validate(['title' => 'required|string|max:255', 'video_url' => 'required|url|max:500', 'description' => 'nullable|string|max:500', 'thumbnail_url' => 'nullable|url|max:500']);
        VideoItem::create($data);
        return back()->with('success', 'Video added.');
    }

    public function deleteVideo(VideoItem $video)
    {
        $video->delete();
                app(\App\Services\CacheSyncService::class)->national();
return back()->with('success', 'Video removed.');
    }

 public function deletePage(Page $page){$page->delete();\Illuminate\Support\Facades\Cache::increment('kicc_cache_version');return back()->with('success','Page removed.');}
 public function storeService(Request $r){ServiceItem::create($r->validate(['title'=>'required|string|max:255','description'=>'nullable|string|max:5000','category'=>'nullable|string|max:80','is_published'=>'required|boolean']));return back()->with('success','Service saved.');}
 public function updateService(Request $r,ServiceItem $service){$service->update($r->validate(['title'=>'required|string|max:255','description'=>'nullable|string|max:5000','category'=>'nullable|string|max:80','is_published'=>'required|boolean']));return back()->with('success','Service updated.');}
 public function deleteService(ServiceItem $service){$service->delete();return back()->with('success','Service removed.');}
}
