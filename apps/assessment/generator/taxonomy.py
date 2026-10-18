"""Taksonomi Bloom revisi (Anderson & Krathwohl) dan pemetaannya ke LOTS/MOTS/HOTS."""

from enum import StrEnum


class CognitiveLevel(StrEnum):
    C1 = "C1"  # Mengingat
    C2 = "C2"  # Memahami
    C3 = "C3"  # Mengaplikasikan
    C4 = "C4"  # Menganalisis
    C5 = "C5"  # Mengevaluasi
    C6 = "C6"  # Mencipta


class Tier(StrEnum):
    LOTS = "LOTS"
    MOTS = "MOTS"
    HOTS = "HOTS"


LEVEL_LABELS = {
    CognitiveLevel.C1: "Mengingat",
    CognitiveLevel.C2: "Memahami",
    CognitiveLevel.C3: "Mengaplikasikan",
    CognitiveLevel.C4: "Menganalisis",
    CognitiveLevel.C5: "Mengevaluasi",
    CognitiveLevel.C6: "Mencipta",
}

TIER_LEVELS = {
    Tier.LOTS: [CognitiveLevel.C1, CognitiveLevel.C2],
    Tier.MOTS: [CognitiveLevel.C3],
    Tier.HOTS: [CognitiveLevel.C4, CognitiveLevel.C5, CognitiveLevel.C6],
}


def tier_of(level: CognitiveLevel) -> Tier:
    return next(tier for tier, levels in TIER_LEVELS.items() if level in levels)
