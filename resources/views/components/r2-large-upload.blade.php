@props(['ownerType', 'ownerId', 'r2Path', 'redirectAfter'])

{{-- Direct R2 upload handler — bypasses Cloudflare's 100MB Worker limit.
     Add <form ... data-r2-upload> to any form with a file input. This
     script intercepts the submit if the file exceeds 90MB and uploads
     directly to R2 via a presigned URL instead of going through the Worker.

     Include this component inside the page's @push('scripts') block. --}}

<script>
(function () {
    'use strict';
    var forms = document.querySelectorAll('form[data-r2-upload]');
    if (!forms.length) return;

    forms.forEach(function (form) {
        form.addEventListener('submit', async function (e) {
            var fileInput = form.querySelector('input[type="file"]');
            if (!fileInput || !fileInput.files.length) return;

            var file = fileInput.files[0];
            var sizeLimit = 90 * 1024 * 1024;
            if (file.size <= sizeLimit) return; // small file — normal POST

            e.preventDefault();

            var path = '{{ $r2Path ?? '' }}';
            if (!path) {
                path = 'uploads/generic/' + Date.now() + '/' + file.name;
            }

            var btn = form.querySelector('button[type="submit"]');
            var origText = btn ? btn.textContent : '';
            if (btn) { btn.disabled = true; btn.textContent = 'Uploading to R2…'; }

            try {
                var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

                // 1. Presigned URL
                var pr = await fetch('/api/r2/presigned-upload', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ path: path, mime: file.type || 'video/mp4' })
                });
                var pu = await pr.json();
                if (!pr.ok) throw new Error('Presigned URL failed');

                // 2. Upload directly to R2
                var ur = await fetch(pu.url, {
                    method: 'PUT',
                    headers: { 'Content-Type': file.type || 'video/mp4' },
                    body: file
                });
                if (!ur.ok) throw new Error('R2 upload status ' + ur.status);

                // 3. Confirm with the app
                var cr = await fetch('/api/r2/confirm-upload', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({
                        path: pu.path,
                        owner_type: '{{ $ownerType ?? '' }}',
                        owner_id: {{ $ownerId ?? 0 }},
                        slot: fileInput.name || 'upload',
                        original_name: file.name,
                        mime: file.type || 'video/mp4',
                        size_bytes: file.size
                    })
                });

                if (!cr.ok) throw new Error('Asset registration failed');
                window.location.reload();

            } catch (err) {
                alert('Upload failed: ' + err.message);
            } finally {
                if (btn) { btn.disabled = false; btn.textContent = origText; }
            }
        });
    });
})();
</script>