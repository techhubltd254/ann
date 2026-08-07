"""All 47 Kenyan counties with metadata for the 3D interactive map.

Each county has:
- name, id, region (former province)
- approximate map position (x, y, z for 3D layout)
- capital, area_km2, population_estimate
- dominant sectors, scene_type for color-coding
- color palette derived from region
"""

COUNTIES = [
    {
        "id": "baringo", "name": "Baringo", "capital": "Kabarnet", "region": "Rift Valley",
        "x": -0.5, "z": 0.5, "area_km2": 11075, "population": 666763,
        "sectors": ["Tourism", "Agriculture", "Fishing"],
        "scene_type": "rural", "has_water": True, "color": "#4a7c3f",
    },
    {
        "id": "bomet", "name": "Bomet", "capital": "Bomet", "region": "Rift Valley",
        "x": -0.8, "z": 1.5, "area_km2": 1998, "population": 875689,
        "sectors": ["Agriculture", "Tea", "Education"],
        "scene_type": "rural", "has_water": False, "color": "#5a8f4a",
    },
    {
        "id": "bungoma", "name": "Bungoma", "capital": "Bungoma", "region": "Western",
        "x": -1.5, "z": -0.3, "area_km2": 3032, "population": 1670570,
        "sectors": ["Agriculture", "Sugar", "Education"],
        "scene_type": "rural", "has_water": False, "color": "#3a7a3a",
    },
    {
        "id": "busia", "name": "Busia", "capital": "Busia", "region": "Western",
        "x": -1.7, "z": 0.5, "area_km2": 1629, "population": 893681,
        "sectors": ["Trade", "Fishing", "Agriculture"],
        "scene_type": "rural", "has_water": True, "color": "#4a8a4a",
    },
    {
        "id": "elgeyo-marakwet", "name": "Elgeyo-Marakwet", "capital": "Iten", "region": "Rift Valley",
        "x": -0.3, "z": -0.2, "area_km2": 3029, "population": 463584,
        "sectors": ["Athletics", "Tourism", "Agriculture"],
        "scene_type": "mountain", "has_water": False, "color": "#6a8f3a",
    },
    {
        "id": "embu", "name": "Embu", "capital": "Embu", "region": "Eastern",
        "x": 0.8, "z": 1.3, "area_km2": 2818, "population": 608599,
        "sectors": ["Agriculture", "Coffee", "Education"],
        "scene_type": "rural", "has_water": False, "color": "#7a9f3a",
    },
    {
        "id": "garissa", "name": "Garissa", "capital": "Garissa", "region": "North Eastern",
        "x": 2.5, "z": 0.0, "area_km2": 45720, "population": 798506,
        "sectors": ["Livestock", "Trade", "Education"],
        "scene_type": "arid", "has_water": False, "color": "#c4a35a",
    },
    {
        "id": "homa-bay", "name": "Homa Bay", "capital": "Homa Bay", "region": "Nyanza",
        "x": -1.2, "z": 1.8, "area_km2": 3183, "population": 1137359,
        "sectors": ["Fishing", "Tourism", "Agriculture"],
        "scene_type": "rural", "has_water": True, "color": "#3a8ac4",
    },
    {
        "id": "isiolo", "name": "Isiolo", "capital": "Isiolo", "region": "Eastern",
        "x": 1.2, "z": -0.5, "area_km2": 25336, "population": 268002,
        "sectors": ["Livestock", "Tourism", "Trade"],
        "scene_type": "arid", "has_water": False, "color": "#b8944a",
    },
    {
        "id": "kajiado", "name": "Kajiado", "capital": "Kajiado", "region": "Rift Valley",
        "x": 0.2, "z": 3.0, "area_km2": 21101, "population": 1178661,
        "sectors": ["Tourism", "Livestock", "Real Estate"],
        "scene_type": "arid", "has_water": False, "color": "#a4884a",
    },
    {
        "id": "kakamega", "name": "Kakamega", "capital": "Kakamega", "region": "Western",
        "x": -1.3, "z": -0.8, "area_km2": 3034, "population": 1867579,
        "sectors": ["Agriculture", "Sugar", "Education"],
        "scene_type": "rural", "has_water": False, "color": "#3a7a3a",
    },
    {
        "id": "kericho", "name": "Kericho", "capital": "Kericho", "region": "Rift Valley",
        "x": -0.8, "z": 1.0, "area_km2": 2454, "population": 901777,
        "sectors": ["Tea", "Agriculture", "Education"],
        "scene_type": "rural", "has_water": False, "color": "#4a8f3a",
    },
    {
        "id": "kiambu", "name": "Kiambu", "capital": "Kiambu", "region": "Central",
        "x": 0.5, "z": 2.0, "area_km2": 2543, "population": 2417735,
        "sectors": ["Technology", "Real Estate", "Agriculture"],
        "scene_type": "urban", "has_water": False, "color": "#8a6a4a",
    },
    {
        "id": "kilifi", "name": "Kilifi", "capital": "Kilifi", "region": "Coast",
        "x": 2.3, "z": 2.3, "area_km2": 12246, "population": 1453787,
        "sectors": ["Tourism", "Agriculture", "Marine"],
        "scene_type": "coastal", "has_water": True, "color": "#3a9ac4",
    },
    {
        "id": "kirinyaga", "name": "Kirinyaga", "capital": "Kerugoya/Kutus", "region": "Central",
        "x": 0.9, "z": 1.5, "area_km2": 1479, "population": 610411,
        "sectors": ["Agriculture", "Coffee", "Rice"],
        "scene_type": "rural", "has_water": False, "color": "#6a9f3a",
    },
    {
        "id": "kisii", "name": "Kisii", "capital": "Kisii", "region": "Nyanza",
        "x": -1.0, "z": 1.8, "area_km2": 1318, "population": 1269867,
        "sectors": ["Agriculture", "Education", "Trade"],
        "scene_type": "rural", "has_water": False, "color": "#4a8a4a",
    },
    {
        "id": "kisumu", "name": "Kisumu", "capital": "Kisumu", "region": "Nyanza",
        "x": -1.0, "z": 1.2, "area_km2": 2086, "population": 1155574,
        "sectors": ["Fishing", "Technology", "Tourism"],
        "scene_type": "urban", "has_water": True, "color": "#3a8ac4",
    },
    {
        "id": "kitui", "name": "Kitui", "capital": "Kitui", "region": "Eastern",
        "x": 1.3, "z": 2.0, "area_km2": 30496, "population": 1136557,
        "sectors": ["Agriculture", "Livestock", "Trade"],
        "scene_type": "arid", "has_water": False, "color": "#a4944a",
    },
    {
        "id": "kwale", "name": "Kwale", "capital": "Kwale", "region": "Coast",
        "x": 2.0, "z": 3.0, "area_km2": 8270, "population": 866820,
        "sectors": ["Tourism", "Mining", "Agriculture"],
        "scene_type": "coastal", "has_water": True, "color": "#3aa0c4",
    },
    {
        "id": "laikipia", "name": "Laikipia", "capital": "Rumuruti", "region": "Rift Valley",
        "x": 0.5, "z": 0.2, "area_km2": 8696, "population": 518560,
        "sectors": ["Wildlife", "Tourism", "Agriculture"],
        "scene_type": "rural", "has_water": False, "color": "#6a8f3a",
    },
    {
        "id": "lamu", "name": "Lamu", "capital": "Lamu", "region": "Coast",
        "x": 3.0, "z": 1.5, "area_km2": 6497, "population": 143920,
        "sectors": ["Tourism", "Fishing", "Culture"],
        "scene_type": "coastal", "has_water": True, "color": "#3aa8c4",
    },
    {
        "id": "machakos", "name": "Machakos", "capital": "Machakos", "region": "Eastern",
        "x": 0.9, "z": 2.3, "area_km2": 6208, "population": 1421932,
        "sectors": ["Agriculture", "Real Estate", "Education"],
        "scene_type": "urban", "has_water": False, "color": "#8a7a4a",
    },
    {
        "id": "makueni", "name": "Makueni", "capital": "Wote", "region": "Eastern",
        "x": 1.2, "z": 2.5, "area_km2": 8009, "population": 987653,
        "sectors": ["Agriculture", "Livestock", "Trade"],
        "scene_type": "arid", "has_water": False, "color": "#a4904a",
    },
    {
        "id": "mandera", "name": "Mandera", "capital": "Mandera", "region": "North Eastern",
        "x": 3.5, "z": -1.5, "area_km2": 25897, "population": 459495,
        "sectors": ["Livestock", "Trade"],
        "scene_type": "arid", "has_water": False, "color": "#c4b05a",
    },
    {
        "id": "marsabit", "name": "Marsabit", "capital": "Marsabit", "region": "Eastern",
        "x": 2.0, "z": -2.0, "area_km2": 70944, "population": 459785,
        "sectors": ["Livestock", "Tourism"],
        "scene_type": "arid", "has_water": False, "color": "#b8a04a",
    },
    {
        "id": "meru", "name": "Meru", "capital": "Meru", "region": "Eastern",
        "x": 1.0, "z": 0.5, "area_km2": 6930, "population": 1545714,
        "sectors": ["Agriculture", "Coffee", "Tourism"],
        "scene_type": "rural", "has_water": False, "color": "#6a9f4a",
    },
    {
        "id": "migori", "name": "Migori", "capital": "Migori", "region": "Nyanza",
        "x": -1.3, "z": 2.3, "area_km2": 2586, "population": 1116436,
        "sectors": ["Agriculture", "Fishing", "Mining"],
        "scene_type": "rural", "has_water": True, "color": "#4a8ac4",
    },
    {
        "id": "mombasa", "name": "Mombasa", "capital": "Mombasa City", "region": "Coast",
        "x": 2.3, "z": 3.0, "area_km2": 219, "population": 1208333,
        "sectors": ["Tourism", "Port", "Technology", "Trade"],
        "scene_type": "urban", "has_water": True, "color": "#3a8ac4",
    },
    {
        "id": "muranga", "name": "Murang'a", "capital": "Murang'a", "region": "Central",
        "x": 0.7, "z": 1.7, "area_km2": 2559, "population": 1156275,
        "sectors": ["Agriculture", "Coffee", "Tea"],
        "scene_type": "rural", "has_water": False, "color": "#5a8f4a",
    },
    {
        "id": "nairobi", "name": "Nairobi City", "capital": "Nairobi", "region": "Nairobi",
        "x": 0.5, "z": 2.5, "area_km2": 696, "population": 4397087,
        "sectors": ["Technology", "Finance", "Trade", "Tourism", "Education"],
        "scene_type": "urban", "has_water": False, "color": "#8a6a4a",
    },
    {
        "id": "nakuru", "name": "Nakuru", "capital": "Nakuru", "region": "Rift Valley",
        "x": 0.0, "z": 1.3, "area_km2": 7509, "population": 2166993,
        "sectors": ["Tourism", "Agriculture", "Technology"],
        "scene_type": "urban", "has_water": True, "color": "#5a8a6a",
    },
    {
        "id": "nandi", "name": "Nandi", "capital": "Kapsabet", "region": "Rift Valley",
        "x": -0.5, "z": 0.8, "area_km2": 2884, "population": 885358,
        "sectors": ["Tea", "Agriculture", "Education"],
        "scene_type": "rural", "has_water": False, "color": "#4a8f3a",
    },
    {
        "id": "narok", "name": "Narok", "capital": "Narok", "region": "Rift Valley",
        "x": -0.3, "z": 2.3, "area_km2": 17933, "population": 1156274,
        "sectors": ["Tourism", "Wildlife", "Agriculture"],
        "scene_type": "rural", "has_water": False, "color": "#6a8f4a",
    },
    {
        "id": "nyamira", "name": "Nyamira", "capital": "Nyamira", "region": "Nyanza",
        "x": -0.9, "z": 1.5, "area_km2": 913, "population": 668616,
        "sectors": ["Agriculture", "Tea", "Education"],
        "scene_type": "rural", "has_water": False, "color": "#4a8a4a",
    },
    {
        "id": "nyandarua", "name": "Nyandarua", "capital": "Ol Kalou", "region": "Central",
        "x": 0.4, "z": 1.0, "area_km2": 3245, "population": 638289,
        "sectors": ["Agriculture", "Potatoes", "Education"],
        "scene_type": "rural", "has_water": False, "color": "#5a8f4a",
    },
    {
        "id": "nyeri", "name": "Nyeri", "capital": "Nyeri", "region": "Central",
        "x": 0.8, "z": 1.0, "area_km2": 3361, "population": 781237,
        "sectors": ["Agriculture", "Coffee", "Tourism"],
        "scene_type": "rural", "has_water": False, "color": "#6a9f3a",
    },
    {
        "id": "samburu", "name": "Samburu", "capital": "Maralal", "region": "Rift Valley",
        "x": 0.8, "z": -0.8, "area_km2": 20821, "population": 310327,
        "sectors": ["Tourism", "Livestock", "Wildlife"],
        "scene_type": "arid", "has_water": False, "color": "#b8904a",
    },
    {
        "id": "siaya", "name": "Siaya", "capital": "Siaya", "region": "Nyanza",
        "x": -1.3, "z": 1.0, "area_km2": 2529, "population": 993183,
        "sectors": ["Fishing", "Agriculture", "Education"],
        "scene_type": "rural", "has_water": True, "color": "#3a8ac4",
    },
    {
        "id": "taita-taveta", "name": "Taita-Taveta", "capital": "Voi", "region": "Coast",
        "x": 1.8, "z": 2.5, "area_km2": 17083, "population": 340671,
        "sectors": ["Tourism", "Mining", "Agriculture"],
        "scene_type": "rural", "has_water": False, "color": "#7a9f4a",
    },
    {
        "id": "tana-river", "name": "Tana River", "capital": "Hola", "region": "Coast",
        "x": 2.5, "z": 1.0, "area_km2": 35375, "population": 315943,
        "sectors": ["Livestock", "Agriculture", "Fishing"],
        "scene_type": "arid", "has_water": True, "color": "#b49c4a",
    },
    {
        "id": "tharaka-nithi", "name": "Tharaka-Nithi", "capital": "Chuka", "region": "Eastern",
        "x": 1.0, "z": 1.0, "area_km2": 2562, "population": 393177,
        "sectors": ["Agriculture", "Coffee", "Education"],
        "scene_type": "rural", "has_water": False, "color": "#6a9f3a",
    },
    {
        "id": "trans-nzoia", "name": "Trans Nzoia", "capital": "Kitale", "region": "Rift Valley",
        "x": -0.5, "z": -0.5, "area_km2": 2496, "population": 990341,
        "sectors": ["Agriculture", "Maize", "Education"],
        "scene_type": "rural", "has_water": False, "color": "#4a8f4a",
    },
    {
        "id": "turkana", "name": "Turkana", "capital": "Lodwar", "region": "Rift Valley",
        "x": 0.5, "z": -2.5, "area_km2": 71598, "population": 926976,
        "sectors": ["Energy", "Fishing", "Livestock"],
        "scene_type": "arid", "has_water": True, "color": "#c4a04a",
    },
    {
        "id": "uasin-gishu", "name": "Uasin Gishu", "capital": "Eldoret", "region": "Rift Valley",
        "x": -0.3, "z": -0.7, "area_km2": 3345, "population": 1162487,
        "sectors": ["Agriculture", "Education", "Athletics"],
        "scene_type": "urban", "has_water": False, "color": "#5a8f4a",
    },
    {
        "id": "vihiga", "name": "Vihiga", "capital": "Vihiga", "region": "Western",
        "x": -1.2, "z": 0.2, "area_km2": 531, "population": 590013,
        "sectors": ["Agriculture", "Education", "Trade"],
        "scene_type": "rural", "has_water": False, "color": "#4a8a4a",
    },
    {
        "id": "wajir", "name": "Wajir", "capital": "Wajir", "region": "North Eastern",
        "x": 3.0, "z": -0.5, "area_km2": 56685, "population": 781263,
        "sectors": ["Livestock", "Trade"],
        "scene_type": "arid", "has_water": False, "color": "#c4ac5a",
    },
    {
        "id": "west-pokot", "name": "West Pokot", "capital": "Kapenguria", "region": "Rift Valley",
        "x": 0.0, "z": -1.0, "area_km2": 9123, "population": 621241,
        "sectors": ["Livestock", "Agriculture", "Tourism"],
        "scene_type": "arid", "has_water": False, "color": "#a4884a",
    },
]

REGION_COLORS = {
    "Nairobi": "#8a6a4a",
    "Central": "#6a9f3a",
    "Coast": "#3a9ac4",
    "Eastern": "#7a9f3a",
    "North Eastern": "#c4a35a",
    "Nyanza": "#3a8ac4",
    "Rift Valley": "#5a8f4a",
    "Western": "#3a7a3a",
}

SCENE_TYPE_COLORS = {
    "urban": "#8a6a4a",
    "rural": "#5a8f4a",
    "coastal": "#3a9ac4",
    "mountain": "#6a8f3a",
    "arid": "#b8904a",
}


def get_county(county_id: str) -> dict | None:
    for c in COUNTIES:
        if c["id"] == county_id.lower().replace(" ", "-"):
            return c
    return None


def get_county_by_name(name: str) -> dict | None:
    for c in COUNTIES:
        if c["name"].lower() == name.lower():
            return c
    return None


def get_region_counties(region: str) -> list[dict]:
    return [c for c in COUNTIES if c["region"] == region]


def get_scene_type_counties(scene_type: str) -> list[dict]:
    return [c for c in COUNTIES if c["scene_type"] == scene_type]


def get_sector_counties(sector: str) -> list[dict]:
    return [c for c in COUNTIES if sector in c["sectors"]]


def hex_to_rgb(hex_color: str) -> tuple:
    h = hex_color.lstrip("#")
    return tuple(int(h[i:i+2], 16) for i in (0, 2, 4))
