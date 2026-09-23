"""Algorithm 18: Itinerary cost composition with live-quote ports.

Composition logic is pure and testable; live flight/transfer/hotel quotes
come from injected async ports (FlightPort, HotelPort) so plugging Amadeus/
Aviasales/Amadeus weather is an adapter, not a rewrite. Total cost =
transport + stay + attractions + curated package markup.
"""
from __future__ import annotations
from decimal import Decimal, ROUND_HALF_UP


class ItineraryComposer:
    CURATED_MARKUP = Decimal("0.10")  # the priced "we built this for you" fee

    def __init__(self, flights, hotels, attractions, weather):
        self.flights, self.hotels = flights, hotels
        self.attractions, self.weather = attractions, weather

    async def compose(self, *, origin: str, county: str, days: int,
                      travelers: int = 1, season_tag: str | None = None) -> dict:
        flight = Decimal(str(await self.flights.cheapest(origin, county)))
        hotel_rate = Decimal(str(await self.hotels.nightly(county, season_tag)))
        stay = hotel_rate * max(1, days - 1) * travelers
        weather = await self.weather.forecast(county, days)
        acts = await self.attractions.top(county, days)
        attractions_cost = sum((Decimal(str(a["entry_fee"])) for a in acts), Decimal(0)) * travelers
        subtotal = flight + stay + attractions_cost
        markup = (subtotal * self.CURATED_MARKUP).quantize(Decimal("0.01"), ROUND_HALF_UP)
        return {
            "origin": origin, "county": county, "days": days, "travelers": travelers,
            "flight": flight, "stay": stay, "attractions": attractions_cost,
            "weather": weather, "attractions_selected": [a["name"] for a in acts],
            "subtotal": subtotal, "curated_markup": markup,
            "total": subtotal + markup, "currency": "KES",
        }
