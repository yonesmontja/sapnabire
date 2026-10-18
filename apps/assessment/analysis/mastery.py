"""Ketuntasan berbasis KKTP (Kriteria Ketercapaian Tujuan Pembelajaran) dengan interval 4 tingkat."""

from dataclasses import dataclass

# Interval default per TP; sekolah bisa override dari Setup Sekolah (settings.kktp_intervals).
DEFAULT_INTERVALS = (
    (0, 40, "Belum berkembang"),
    (41, 65, "Mulai berkembang"),
    (66, 85, "Berkembang sesuai harapan"),
    (86, 100, "Sangat berkembang"),
)


@dataclass(frozen=True)
class MasteryRow:
    student_id: str
    score: float
    level: str
    passed: bool


def classify(score: float, intervals=DEFAULT_INTERVALS) -> str:
    rounded = round(score)
    for low, high, label in intervals:
        if low <= rounded <= high:
            return label
    raise ValueError(f"Nilai {score} di luar interval KKTP.")


def mastery_report(scores: dict[str, float], threshold: float, intervals=DEFAULT_INTERVALS) -> dict:
    rows = [MasteryRow(s, v, classify(v, intervals), v >= threshold) for s, v in scores.items()]
    distribution = {label: sum(r.level == label for r in rows) for _, _, label in intervals}
    passed = sum(r.passed for r in rows)
    return {
        "threshold": threshold,
        "participants": len(rows),
        "passed": passed,
        "mastery_rate": round(passed / len(rows), 3) if rows else 0,
        "distribution": distribution,
        "remedial": [r.student_id for r in sorted(rows, key=lambda r: r.score) if not r.passed],
        "enrichment": [r.student_id for r in rows if r.level == intervals[-1][2]],
    }
