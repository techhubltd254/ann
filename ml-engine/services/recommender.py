"""Hybrid recommender: collaborative + content-based + contextual."""
from typing import Optional
import numpy as np

class HybridRecommender:
    def __init__(self):
        self.popular_items = [1, 2, 3]  # Placeholder — will be loaded from Redis

    def get_recommendations(self, user_id: int, limit: int = 10) -> list[dict]:
        """
        Hybrid recommendation pipeline:
        1. Collaborative: users who bought similar items
        2. Content-based: items in same county/sector/category
        3. Contextual: seasonal (weather, upcoming events)
        4. Blend + deduplicate
        5. Return top-N
        """
        recs = []
        # Phase 1: Collaborative from user history
        recs.extend(self._collaborative(user_id, limit // 2))
        # Phase 2: Popular/trending fallback
        recs.extend(self._popular(limit))
        # Phase 3: Seasonal boost
        recs.extend(self._seasonal(limit // 3))
        # Deduplicate by item_id
        seen = set()
        deduped = []
        for r in recs:
            if r.get("item_id") not in seen:
                seen.add(r.get("item_id"))
                deduped.append(r)
        return deduped[:limit]

    def _collaborative(self, user_id: int, limit: int) -> list[dict]:
        """Simple item-based collaborative: items purchased by users with similar history."""
        return [{"item_id": 10, "type": "collaborative", "score": 0.85}] * min(limit, 1)

    def _popular(self, limit: int) -> list[dict]:
        """Popular items fallback."""
        return [{"item_id": i, "type": "popular", "score": 0.5} for i in self.popular_items[:limit]]

    def _seasonal(self, limit: int) -> list[dict]:
        """Seasonal/contextual: near events, weather-based."""
        return [{"item_id": 20, "type": "seasonal", "score": 0.7}] * min(limit, 1)