<?php

namespace App\Services;

use App\Models\ConsentForm;
use Illuminate\Support\Str;

/**
 * BilingualFormRenderer — renders consent/waiver forms in English and Swahili.
 * Supports digital signature collection and bilingual content management.
 */
class BilingualFormRenderer
{
    public function forForm(ConsentForm $form): array
    {
        return [
            'id' => $form->id,
            'title' => $form->title,
            'language' => $form->language,
            'content' => [
                'en' => $form->content_en,
                'sw' => $form->content_sw,
            ],
            'signed_count' => $form->signed_count,
            'is_active' => $form->is_active,
            'entity' => [
                'type' => $form->entity_type,
                'id' => $form->entity_id,
            ],
        ];
    }

    /**
     * Render an HTML consent form with bilingual content.
     */
    public function renderHtml(ConsentForm $form, string $lang = 'en'): string
    {
        $content = $lang === 'sw' ? ($form->content_sw ?? $form->content_en) : $form->content_en;
        $title = $form->title;

        return <<<HTML
<div class="consent-form" style="max-width:700px;margin:2rem auto;padding:2rem;border:1px solid #ddd;border-radius:8px">
    <h2 style="font-size:1.25rem;font-weight:700;margin-bottom:1rem">{$title}</h2>
    <div class="consent-body" style="line-height:1.6;margin-bottom:2rem">{$content}</div>
    <form method="POST" action="/consent/sign/{$form->id}">
        <input type="hidden" name="form_id" value="{$form->id}">
        <div style="margin-bottom:1rem">
            <label style="display:block;font-weight:600;margin-bottom:.25rem">Full Name</label>
            <input name="signer_name" required style="width:100%;padding:.5rem;border:1px solid #ccc;border-radius:4px">
        </div>
        <div style="margin-bottom:1rem">
            <label style="display:block;font-weight:600;margin-bottom:.25rem">ID Number</label>
            <input name="signer_id_number" style="width:100%;padding:.5rem;border:1px solid #ccc;border-radius:4px">
        </div>
        <div style="margin-bottom:1rem">
            <label style="display:block;font-weight:600;margin-bottom:.25rem">Phone</label>
            <input name="signer_phone" style="width:100%;padding:.5rem;border:1px solid #ccc;border-radius:4px">
        </div>
        <div style="margin-bottom:1.5rem">
            <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
                <input type="checkbox" name="agreements[]" value="media_usage" required>
                <span>I consent to the use of my voice and/or image for KICC exhibition purposes</span>
            </label>
            <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;margin-top:.5rem">
                <input type="checkbox" name="agreements[]" value="broadcast">
                <span>I consent to broadcast across KICC screens and partner platforms</span>
            </label>
        </div>
        <button type="submit" style="background:#0B1E57;color:white;border:none;padding:.75rem 2rem;border-radius:6px;font-weight:600;cursor:pointer">Sign Consent</button>
    </form>
</div>
HTML;
    }
}