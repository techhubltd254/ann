"""In-process async event bus - the wiring that makes all 20 algorithms one system.

escrow.released -> commission accrual -> pool contribution flows through here
with zero coupling between modules. In production, bridge to SQS/Kafka by
publishing the same topics; handlers stay identical.
"""
import asyncio


class EventBus:
    def __init__(self):
        self._subs: dict[str, list] = {}

    def subscribe(self, topic: str, handler):
        self._subs.setdefault(topic, []).append(handler)

    async def publish(self, topic: str, payload):
        for handler in self._subs.get(topic, []):
            result = handler(payload)
            if asyncio.iscoroutine(result):
                await result
