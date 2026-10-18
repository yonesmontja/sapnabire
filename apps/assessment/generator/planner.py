"""Memecah permintaan "N soal dengan komposisi X%" menjadi potongan kerja per level kognitif.

Contoh: 100 soal, {C1: 20, C2: 20, C3: 30, C4: 20, C5: 10} → jumlah per level persis 100,
lalu tiap level dipecah per `chunk_size` agar bisa dikerjakan paralel oleh Celery worker.
"""

from dataclasses import dataclass
from math import floor

from .taxonomy import TIER_LEVELS, CognitiveLevel, Tier


@dataclass(frozen=True)
class Chunk:
    level: CognitiveLevel
    count: int
    index: int  # urutan potongan dalam level yang sama


def allocate(total: int, weights: dict[str, float]) -> dict[str, int]:
    """Metode sisa terbesar (Hamilton): jumlah hasil selalu tepat `total`, tidak ada pembulatan liar."""
    if total < 1:
        raise ValueError("Jumlah soal minimal 1.")
    positive = {key: float(w) for key, w in weights.items() if float(w) > 0}
    weight_sum = sum(positive.values())
    if not positive or weight_sum <= 0:
        raise ValueError("Komposisi kesulitan tidak boleh kosong.")

    quotas = {key: total * w / weight_sum for key, w in positive.items()}
    result = {key: floor(q) for key, q in quotas.items()}
    remaining = total - sum(result.values())
    for key in sorted(quotas, key=lambda k: (quotas[k] - result[k], positive[k]), reverse=True)[:remaining]:
        result[key] += 1
    return {key: n for key, n in result.items() if n > 0}


def expand_tiers(tier_weights: dict[str, float]) -> dict[str, float]:
    """LOTS/MOTS/HOTS → bobot per level C, dibagi rata di dalam tier."""
    levels: dict[str, float] = {}
    for tier, weight in tier_weights.items():
        members = TIER_LEVELS[Tier(tier)]
        for level in members:
            levels[level.value] = levels.get(level.value, 0) + float(weight) / len(members)
    return levels


def plan_chunks(total: int, level_weights: dict[str, float], chunk_size: int) -> list[Chunk]:
    chunks: list[Chunk] = []
    for level, count in allocate(total, level_weights).items():
        full, rest = divmod(count, chunk_size)
        sizes = [chunk_size] * full + ([rest] if rest else [])
        chunks.extend(Chunk(CognitiveLevel(level), size, i) for i, size in enumerate(sizes))
    return chunks
