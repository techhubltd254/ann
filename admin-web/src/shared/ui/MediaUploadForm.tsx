import { useMemo, useRef, useState } from "react";
import { cn } from "./utils";

/**
 * MediaUploadForm — admin upload for county/sector media (4K/8K masters).
 *
 * Spec compliance:
 *  - Native <form> + <button type="submit">; Enter submits; no div handlers.
 *  - Explicit labels (htmlFor), correct input types, name attributes,
 *    autoComplete where relevant, required constraint validation, visible focus.
 *  - Derived state only: validation errors + size warnings are computed during
 *    render / useMemo from raw inputs — never mirrored into state (Section 2).
 *  - Radios for 2-3 choices, searchable combobox for the 47 counties,
 *    top-aligned labels over boxed fields, inline error recovery, auto-save note.
 *  - Size guardrails surfaced BEFORE upload (the "can't upload blindly" rule):
 *    >2 GB blocked client-side; >500 MB shows compression advisory.
 */

const MAX_BYTES = 2 * 1024 ** 3; // 2 GB hard cap (engine enforces same)
const ADVISORY_BYTES = 500 * 1024 ** 2;

interface County {
  slug: string;
  name: string;
}

interface MediaUploadFormProps {
  counties: County[];
  onSubmit: (data: FormData) => Promise<void>;
}

export function MediaUploadForm({ counties, onSubmit }: MediaUploadFormProps) {
  const [file, setFile] = useState<File | null>(null);
  const [query, setQuery] = useState("");
  const [county, setCounty] = useState("");
  const [slot, setSlot] = useState("hero");
  const [submitting, setSubmitting] = useState(false);
  const [serverError, setServerError] = useState<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  // ---- Derived state (computed, never duplicated) ----
  const filteredCounties = useMemo(
    () =>
      query.trim()
        ? counties.filter((c) => c.name.toLowerCase().includes(query.trim().toLowerCase()))
        : counties,
    [counties, query],
  );

  const fileError = useMemo(() => {
    if (!file) return null;
    if (file.size > MAX_BYTES) return "File exceeds the 2 GB master cap. Split or pre-compress it.";
    if (!/^(video|image)\//.test(file.type)) return "Only video or image masters are accepted here.";
    return null;
  }, [file]);

  const showSizeAdvisory = file !== null && !fileError && file.size > ADVISORY_BYTES;

  async function handleSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    if (!file || fileError || !county) return;
    setSubmitting(true);
    setServerError(null);
    try {
      const data = new FormData(e.currentTarget); // native FormData parsing (name attrs required)
      await onSubmit(data);
      e.currentTarget.reset();
      setFile(null);
      setCounty("");
      setSlot("hero");
    } catch (err) {
      setServerError(err instanceof Error ? err.message : "Upload failed. Your progress is saved — retry when ready.");
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <form
      onSubmit={handleSubmit}
      aria-busy={submitting}
      className="mx-auto w-full max-w-lg space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-[#0A1024]"
    >
      <header className="space-y-1">
        <h2 className="text-lg font-semibold text-slate-900 dark:text-white">Publish county media</h2>
        <p className="text-sm text-slate-500 dark:text-slate-400">
          Masters up to 8K are fine — the pipeline builds the web versions. Nothing heavy ever reaches the site directly.
        </p>
      </header>

      {/* County — searchable combobox (47 options) */}
      <div className="space-y-2">
        <label htmlFor="county-search" className="block text-sm font-medium text-slate-700 dark:text-slate-200">
          County
        </label>
        <input
          id="county-search"
          type="search"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder="Search 47 counties…"
          autoComplete="off"
          className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-[#901C1E] focus:outline-2 focus:outline-[#901C1E]/40 dark:border-white/15 dark:bg-white/5 dark:text-white"
        />
        <select
          id="county"
          name="county"
          required
          value={county}
          onChange={(e) => setCounty(e.target.value)}
          aria-label="Choose county"
          className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-[#901C1E] focus:outline-2 focus:outline-[#901C1E]/40 dark:border-white/15 dark:bg-white/5 dark:text-white"
        >
          <option value="" disabled>
            Select county…
          </option>
          {filteredCounties.map((c) => (
            <option key={c.slug} value={c.slug}>
              {c.name}
            </option>
          ))}
        </select>
      </div>

      {/* Slot — radios (3 choices) */}
      <fieldset className="space-y-2">
        <legend className="text-sm font-medium text-slate-700 dark:text-slate-200">Placement</legend>
        <div className="flex gap-4">
          {[
            { value: "hero", label: "Hero video" },
            { value: "gallery", label: "Gallery" },
            { value: "model3d", label: "3D model" },
          ].map((opt) => (
            <label
              key={opt.value}
              className="flex min-h-11 cursor-pointer items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm has-checked:border-[#901C1E] has-checked:bg-[#901C1E]/5 dark:border-white/15 dark:has-checked:border-[#FFCD05] dark:has-checked:bg-[#FFCD05]/10"
            >
              <input
                type="radio"
                name="slot"
                value={opt.value}
                checked={slot === opt.value}
                onChange={() => setSlot(opt.value)}
                className="size-4 accent-[#901C1E] dark:accent-[#FFCD05]"
              />
              {opt.label}
            </label>
          ))}
        </div>
      </fieldset>

      {/* File */}
      <div className="space-y-2">
        <label htmlFor="master-file" className="block text-sm font-medium text-slate-700 dark:text-slate-200">
          Master file
        </label>
        <input
          ref={fileInputRef}
          id="master-file"
          name="master"
          type="file"
          required
          accept="video/*,image/*"
          onChange={(e) => setFile(e.currentTarget.files?.[0] ?? null)}
          aria-invalid={!!fileError}
          aria-describedby={fileError ? "file-error" : showSizeAdvisory ? "file-advisory" : undefined}
          className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm focus:border-[#901C1E] focus:outline-2 focus:outline-[#901C1E]/40 dark:border-white/15 dark:bg-white/5 dark:text-white"
        />
        {fileError && (
          <p id="file-error" role="alert" className="text-sm text-red-600 dark:text-red-400">
            {fileError}
          </p>
        )}
        {showSizeAdvisory && (
          <p id="file-advisory" className="text-sm text-amber-600 dark:text-amber-400">
            {(file!.size / 1024 ** 3).toFixed(1)} GB master — transcoding will take a while. You can keep working; progress shows in the media library.
          </p>
        )}
      </div>

      {serverError && (
        <p role="alert" className="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-950/40 dark:text-red-300">
          {serverError}
        </p>
      )}

      <div className="flex items-center justify-between gap-3">
        <p className="text-xs text-slate-400 dark:text-slate-500">Draft auto-saves on file select.</p>
        <button
          type="submit"
          disabled={submitting || !file || !!fileError || !county}
          className={cn(
            "min-h-11 rounded-full bg-[#901C1E] px-6 py-2.5 text-sm font-semibold text-white shadow",
            "transition-all hover:bg-[#ac2323] active:scale-[0.98]",
            "focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#901C1E]",
            "disabled:cursor-not-allowed disabled:opacity-50",
            "dark:bg-[#FFCD05] dark:text-[#0A1024] dark:hover:bg-[#ffd92e]",
          )}
        >
          {submitting ? "Uploading…" : "Looks Good — Publish"}
        </button>
      </div>
    </form>
  );
}
