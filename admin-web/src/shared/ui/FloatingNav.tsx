import { useCallback, useId, useMemo, useRef, useState } from "react";
import { cn } from "./utils";

/**
 * FloatingNav — floating segmented pill navbar (per UX spec).
 *
 * - Glassmorphic pill, centered near viewport bottom (thumb reach — Fitts's Law).
 * - Icon+label tabs left, high-contrast CTA pill right.
 * - Sliding highlight indicator animates between active tabs.
 * - Accessibility: native <button> tabs in a <nav> with aria-label, roving
 *   tabindex with Arrow/Home/End keys, aria-current on the active tab,
 *   visible focus rings, 44x44px minimum targets.
 */

export interface NavTab {
  id: string;
  label: string;
  icon: React.ReactNode;
  href?: string;
}

interface FloatingNavProps {
  tabs: NavTab[];
  activeTab: string;
  onTabChange: (id: string) => void;
  cta: { label: string; onClick: () => void; icon?: React.ReactNode };
  className?: string;
}

export function FloatingNav({ tabs, activeTab, onTabChange, cta, className }: FloatingNavProps) {
  const navRef = useRef<HTMLElement>(null);
  const [focusIdx, setFocusIdx] = useState(() => Math.max(0, tabs.findIndex((t) => t.id === activeTab)));
  const labelId = useId();

  // Derived state — computed during render, never stored (Section 2 spec).
  const activeIdx = useMemo(
    () => Math.max(0, tabs.findIndex((t) => t.id === activeTab)),
    [tabs, activeTab],
  );

  const onKeyDown = useCallback(
    (e: React.KeyboardEvent) => {
      const count = tabs.length;
      let next: number | null = null;
      if (e.key === "ArrowRight") next = (focusIdx + 1) % count;
      else if (e.key === "ArrowLeft") next = (focusIdx - 1 + count) % count;
      else if (e.key === "Home") next = 0;
      else if (e.key === "End") next = count - 1;
      if (next !== null) {
        e.preventDefault();
        setFocusIdx(next);
        navRef.current
          ?.querySelectorAll<HTMLButtonElement>('[role="tab"]')
          [next]?.focus();
      }
    },
    [focusIdx, tabs.length],
  );

  return (
    <nav
      ref={navRef}
      aria-labelledby={labelId}
      className={cn(
        "fixed bottom-6 left-1/2 z-50 -translate-x-1/2",
        className,
      )}
    >
      <span id={labelId} className="sr-only">
        Primary navigation
      </span>

      <div
        role="tablist"
        aria-orientation="horizontal"
        onKeyDown={onKeyDown}
        className={cn(
          "relative flex items-center gap-1 rounded-full p-1.5",
          // Glassmorphism: blur + translucent fill + hairline border + soft shadow.
          "border border-white/20 bg-white/70 shadow-lg shadow-black/10",
          "backdrop-blur-xl backdrop-saturate-150",
          "dark:border-white/10 dark:bg-[#0A1024]/80 dark:shadow-black/40",
        )}
      >
        {/* Sliding active-pill indicator */}
        <span
          aria-hidden
          className={cn(
            "absolute top-1.5 bottom-1.5 rounded-full bg-[#901C1E]/10 ring-1 ring-[#901C1E]/25",
            "transition-all duration-300 ease-[cubic-bezier(0.22,1,0.36,1)]",
            "dark:bg-[#FFCD05]/15 dark:ring-[#FFCD05]/30",
          )}
          style={{
            left: `calc(${activeIdx} * (var(--tab-w, 0px)) + 6px)`,
            width: "var(--tab-w, 0px)",
          }}
        />

        {tabs.map((tab, i) => {
          const active = tab.id === activeTab;
          return (
            <button
              key={tab.id}
              type="button"
              role="tab"
              aria-selected={active}
              aria-current={active ? "page" : undefined}
              tabIndex={i === focusIdx ? 0 : -1}
              onClick={() => onTabChange(tab.id)}
              onFocus={() => setFocusIdx(i)}
              ref={(el) => {
                if (el) el.style.setProperty("--tab-w", `${el.offsetWidth}px`);
              }}
              className={cn(
                "relative z-10 flex min-h-11 min-w-11 items-center gap-2 rounded-full px-4 py-2.5",
                "text-sm font-medium transition-colors duration-200",
                "focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#901C1E] dark:focus-visible:outline-[#FFCD05]",
                active
                  ? "text-[#901C1E] dark:text-[#FFCD05]"
                  : "text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white",
              )}
            >
              <span aria-hidden className="size-5 shrink-0">
                {tab.icon}
              </span>
              <span className="hidden sm:inline">{tab.label}</span>
            </button>
          );
        })}

        {/* Integrated CTA — right end of the pill (Fitts: short throw from tabs) */}
        <button
          type="button"
          onClick={cta.onClick}
          className={cn(
            "relative z-10 ml-1 flex min-h-11 items-center gap-2 rounded-full px-5 py-2.5",
            "bg-[#901C1E] text-sm font-semibold text-white shadow-md",
            "transition-all duration-200 hover:bg-[#ac2323] hover:shadow-lg active:scale-[0.98]",
            "focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#901C1E]",
            "dark:bg-[#FFCD05] dark:text-[#0A1024] dark:hover:bg-[#ffd92e] dark:focus-visible:outline-[#FFCD05]",
          )}
        >
          {cta.icon && (
            <span aria-hidden className="size-5 shrink-0">
              {cta.icon}
            </span>
          )}
          {cta.label}
        </button>
      </div>
    </nav>
  );
}
