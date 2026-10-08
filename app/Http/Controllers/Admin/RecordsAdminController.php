<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\Record;
use App\Models\RecordMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Records publishing admin, ported from the legacy KICC admin (kicc-v2 branch,
 * App\Http\Controllers\AdminController).
 *
 * Adapted for the current platform:
 *  - media lives in `record_media` / RecordMedia (the platform already owns `media_assets`);
 *  - authorisation reuses the platform gate `admin:kicc` instead of a new `is_admin` column;
 *  - views live under resources/views/admin/* and layouts.admin-records.
 */
class RecordsAdminController extends Controller
{
    private function audit(string $action, string $id, array $details = []): void
    {
        DB::table('audit_events')->insert([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_id' => $id,
            'details' => json_encode($details),
            'created_at' => now(),
        ]);
    }

    private function type(string $type): void
    {
        abort_unless(isset(config('kicc.types')[$type]), 404);
    }

    public function index()
    {
        return view('experience.pages.admin.index', [
            'counts' => Record::selectRaw('type,status,count(*) as total')->groupBy('type', 'status')->get(),
            'mediaCount' => RecordMedia::count(),
            'newEnquiries' => Enquiry::where('status', 'new')->count(),
        ]);
    }

    public function listing(Request $r, string $type)
    {
        $this->type($type);
        $query = Record::where('type', $type)->with('media')->orderBy('name');
        if ($r->filled('q')) {
            $query->where('name', 'like', '%' . $r->string('q') . '%');
        }

        return view('experience.pages.admin.list', [
            'type' => $type,
            'label' => config('kicc.types')[$type],
            'records' => $query->paginate(30)->withQueryString(),
        ]);
    }

    public function create(string $type)
    {
        $this->type($type);

        return view('experience.pages.admin.edit', [
            'record' => new Record(['type' => $type, 'status' => 'draft']),
            'parents' => Record::whereIn('type', ['counties', 'institutions', 'ministries', 'sectors'])
                ->orderBy('name')->get(),
        ]);
    }

    public function edit(Record $record)
    {
        return view('experience.pages.admin.edit', [
            'record' => $record->load('media'),
            'parents' => Record::where('id', '!=', $record->id)
                ->whereIn('type', ['counties', 'institutions', 'ministries', 'sectors'])
                ->orderBy('name')->get(),
        ]);
    }

    private function validated(Request $r, ?Record $record = null): array
    {
        $type = $record?->type ?? $r->input('type');
        $this->type((string) $type);

        $v = $r->validate([
            'name' => 'required|string|max:240',
            'slug' => [
                'required', 'string', 'max:180',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('records')->where('type', $type)->ignore($record?->id),
            ],
            'description' => 'nullable|string|max:100000',
            'status' => ['required', Rule::in(['draft', 'published'])],
            'parent_id' => 'nullable|uuid|exists:records,id',
            'revision' => $record ? 'required|integer|min:1' : 'nullable|integer',
            'payload_json' => 'nullable|json|max:100000',
        ]);

        $payload = json_decode($v['payload_json'] ?? '{}', true);
        if (!is_array($payload) || (array_is_list($payload) && count($payload) > 0)) {
            throw ValidationException::withMessages(['payload_json' => 'Use a JSON object, not a list.']);
        }

        $unknown = array_diff(array_keys($payload), config('kicc.payload_keys'));
        if ($unknown) {
            throw ValidationException::withMessages(['payload_json' => 'Unsupported fields: ' . implode(', ', $unknown)]);
        }

        if (isset($payload['price']) && (!is_numeric($payload['price']) || $payload['price'] < 0)) {
            throw ValidationException::withMessages(['payload_json' => 'Price must be a non-negative number.']);
        }

        foreach (['splat_url', 'website', 'playback_url'] as $k) {
            if (!empty($payload[$k])) {
                $ok = filter_var($payload[$k], FILTER_VALIDATE_URL)
                    && in_array(parse_url($payload[$k], PHP_URL_SCHEME), ['http', 'https'], true);
                if (!$ok) {
                    throw ValidationException::withMessages(['payload_json' => $k . ' must be an HTTP(S) URL.']);
                }
            }
        }

        if ($record && ($v['parent_id'] ?? null) === $record->id) {
            throw ValidationException::withMessages(['parent_id' => 'A record cannot be its own parent.']);
        }

        // Ancestor cycles are rejected, not silently saved.
        $parent = $v['parent_id'] ?? null;
        $seen = [];
        while ($parent) {
            if (isset($seen[$parent]) || ($record && $parent === $record->id)) {
                throw ValidationException::withMessages(['parent_id' => 'Parent relationship would create a cycle.']);
            }
            $seen[$parent] = true;
            $parent = Record::find($parent)?->parent_id;
        }

        unset($v['payload_json']);

        return $v + ['type' => $type, 'payload' => $payload, 'updated_by' => auth()->id()];
    }

    public function store(Request $r)
    {
        $v = $this->validated($r);
        unset($v['revision']);

        $record = DB::transaction(function () use ($v) {
            $record = Record::create($v);
            $this->audit('record.create', $record->id);

            return $record;
        });

        return redirect()->route('admin.edit', $record)->with('success', 'Record saved in the database.');
    }

    public function update(Request $r, Record $record)
    {
        $v = $this->validated($r, $record);

        DB::transaction(function () use ($record, $v) {
            $locked = Record::whereKey($record->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->revision != (int) $v['revision'], 409, 'Another editor changed this record. Reload before saving.');
            unset($v['revision']);
            $locked->fill($v);
            $locked->revision++;
            $locked->save();
            $this->audit('record.update', $locked->id, ['revision' => $locked->revision]);
        });

        return back()->with('success', 'Changes saved. Published records appear immediately on the public site.');
    }

    public function delete(Record $record)
    {
        $type = $record->type;
        abort_if($record->media()->exists(), 409, 'Delete attached media first so no storage objects are orphaned.');

        DB::transaction(function () use ($record) {
            $this->audit('record.delete', $record->id);
            $record->delete();
        });

        return redirect()->route('admin.list', $type)->with('success', 'Record deleted.');
    }

    public function upload(Request $r, Record $record)
    {
        $v = $r->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,webp,mp4,webm,mov|max:' . (int) config('kicc.max_upload_kb', 204800),
            'title' => 'required|string|max:240',
            'description' => 'nullable|string|max:5000',
            'format' => ['required', Rule::in(['standard', '360'])],
            'status' => ['required', Rule::in(['draft', 'published'])],
        ]);

        $file = $r->file('file');
        $disk = (string) config('kicc.media_disk', 'local');
        abort_unless(array_key_exists($disk, config('filesystems.disks')), 500, 'Configured media disk is not available.');

        $path = $file->store('experience', $disk);
        if (!$path) {
            throw new \RuntimeException('Media storage failed.');
        }

        try {
            $media = DB::transaction(function () use ($file, $record, $disk, $path, $v) {
                $media = RecordMedia::create([
                    'record_id' => $record->id,
                    'disk' => $disk,
                    'path' => $path,
                    'original_name' => basename($file->getClientOriginalName()),
                    'mime' => $file->getMimeType(),
                    'bytes' => $file->getSize(),
                    'title' => $v['title'],
                    'description' => $v['description'] ?? null,
                    'format' => $v['format'],
                    'status' => $v['status'],
                ]);
                $this->audit('media.upload', $media->id, ['record_id' => $record->id, 'bytes' => $media->bytes]);

                return $media;
            });
        } catch (\Throwable $e) {
            Storage::disk($disk)->delete($path);
            throw $e;
        }

        return back()->with('success', 'Media stored and linked to this record.');
    }

    public function showMedia(RecordMedia $media)
    {
        abort_unless(auth()->check(), 403);
        $disk = Storage::disk($media->disk);
        abort_unless($disk->exists($media->path), 404, 'Media object is missing from storage.');

        return $disk->response($media->path, $media->original_name, [
            'Content-Type' => $media->mime,
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function mediaStatus(Request $r, RecordMedia $media)
    {
        $v = $r->validate(['status' => ['required', Rule::in(['draft', 'published'])]]);

        DB::transaction(function () use ($media, $v) {
            $media->update($v);
            $this->audit('media.publication', $media->id, $v);
        });

        return back()->with('success', 'Media publication updated.');
    }

    public function deleteMedia(RecordMedia $media)
    {
        $disk = Storage::disk($media->disk);
        if ($disk->exists($media->path) && !$disk->delete($media->path)) {
            return back()->withErrors(['media' => 'Storage deletion failed; metadata was retained.']);
        }

        DB::transaction(function () use ($media) {
            $this->audit('media.delete', $media->id);
            $media->delete();
        });

        return back()->with('success', 'Media deleted from storage and the database.');
    }

    public function enquiries()
    {
        return view('experience.pages.admin.enquiries', ['enquiries' => Enquiry::with('record')->latest()->paginate(30)]);
    }

    public function enquiryStatus(Request $r, Enquiry $enquiry)
    {
        $v = $r->validate(['status' => ['required', Rule::in(['new', 'reviewed', 'closed'])]]);

        DB::transaction(function () use ($enquiry, $v) {
            $enquiry->update($v);
            $this->audit('enquiry.status', $enquiry->id, $v);
        });

        return back()->with('success', 'Enquiry status updated.');
    }

    public function auditLog()
    {
        return view('experience.pages.admin.audit', ['events' => DB::table('audit_events')->orderByDesc('id')->paginate(50)]);
    }
}
