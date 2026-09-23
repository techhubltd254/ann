"""Repository interface + in-memory adapter.

The whole algorithm layer programs against these five async methods, so the
production adapter is a thin SQLAlchemy/asyncpg class over the migrations in
/migrations - same method signatures, pooled connections, batch writes.
"""
import itertools
from collections import defaultdict


class InMemoryRepository:
    def __init__(self):
        self.tables: dict[str, list[dict]] = defaultdict(list)
        self._ids = itertools.count(1)

    async def insert(self, table: str, row: dict) -> dict:
        row = dict(row)
        row["id"] = next(self._ids)
        self.tables[table].append(row)
        return row

    async def all(self, table: str) -> list[dict]:
        return list(self.tables[table])

    async def count(self, table: str, **filters) -> int:
        return sum(1 for r in self.tables[table]
                   if all(r.get(k) == v for k, v in filters.items()))

    async def find(self, table: str, **filters) -> list[dict]:
        return [r for r in self.tables[table]
                if all(r.get(k) == v for k, v in filters.items())]

    async def upsert(self, table: str, key_fields: list[str], row: dict) -> dict:
        for existing in self.tables[table]:
            if all(existing.get(k) == row.get(k) for k in key_fields):
                existing.update(row)
                return existing
        return await self.insert(table, row)
