"""Algorithm 17: Sanctions/PEP screening - name normalization + Jaro-Winkler.

Cross-pipeline gate: every Tier-2 participant is screened before any money
moves. Fuzzy match (Jaro-Winkler >= threshold on normalized names) against
watchlist entries; hit -> pipeline object frozen pending compliance review.
Deterministic, no network needed - watchlist is injectable.
"""
from __future__ import annotations
import unicodedata


def _normalize(name: str) -> str:
    text = unicodedata.normalize("NFKD", name).encode("ascii", "ignore").decode()
    return " ".join("".join(ch for ch in text.lower() if ch.isalnum() or ch == " ").split())


def _jaro_winkler(s1: str, s2: str) -> float:
    if s1 == s2:
        return 1.0
    if not s1 or not s2:
        return 0.0
    jaro_w = 0.1
    match_dist = max(len(s1), len(s2)) // 2 - 1
    m1, m2, matches = [False] * len(s1), [False] * len(s2), 0
    for i, ch in enumerate(s1):
        lo, hi = max(0, i - match_dist), min(len(s2), i + match_dist + 1)
        for j in range(lo, hi):
            if not m2[j] and ch == s2[j]:
                m1[i] = m2[j] = True
                matches += 1
                break
    if not matches:
        return 0.0
    t = 0
    k = 0
    for i, hit in enumerate(m1):
        if hit:
            while not m2[k]:
                k += 1
            if s1[i] != s2[k]:
                t += 1
            k += 1
    t //= 2
    jaro = (matches / len(s1) + matches / len(s2) + (matches - t) / matches) / 3
    prefix = 0
    for a, b in zip(s1, s2):
        if a != b or prefix == 4:
            break
        prefix += 1
    return jaro + prefix * jaro_w * (1 - jaro)


class Screener:
    def __init__(self, config, watchlist: list[str] | None = None):
        self.threshold = config.get("screening.threshold")
        self.watchlist = [_normalize(w) for w in (watchlist or [
            "John Appropriator", "Global Terror Fund", "Example Sanctioned Entity",
        ])]

    def screen(self, name: str) -> dict:
        norm = _normalize(name)
        best, best_score = None, 0.0
        for entry in self.watchlist:
            score = _jaro_winkler(norm, entry)
            if score > best_score:
                best, best_score = entry, score
        hit = best_score >= self.threshold
        return {"name": name, "normalized": norm, "match": best,
                "score": round(best_score, 4), "hit": bool(hit),
                "action": "freeze_pending_review" if hit else "clear"}
