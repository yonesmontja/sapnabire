"""Analisis Butir Soal klasik (Classical Test Theory) — murni Python, tanpa I/O.

Kriteria yang dipakai (umum dipakai di sekolah Indonesia):
- Tingkat kesukaran p: < 0.30 sukar, 0.30–0.70 sedang, > 0.70 mudah.
- Daya pembeda D (kelompok atas/bawah 27%): ≥ 0.40 sangat baik, 0.30–0.39 baik,
  0.20–0.29 cukup (perlu revisi), < 0.20 jelek (buang/ganti).
- Pengecoh berfungsi bila dipilih ≥ 5% peserta dan lebih banyak dipilih kelompok bawah.
- Reliabilitas KR-20 (skor dikotomus).
"""

from dataclasses import asdict, dataclass, field
from math import sqrt
from statistics import mean, pstdev

GROUP_FRACTION = 0.27
DISTRACTOR_MIN_SHARE = 0.05


@dataclass
class ItemResult:
    item_id: str
    difficulty: float
    difficulty_label: str
    discrimination: float
    discrimination_label: str
    point_biserial: float | None
    option_shares: dict[str, float] = field(default_factory=dict)
    nonfunctional_distractors: list[str] = field(default_factory=list)
    decision: str = ""  # terima / revisi / buang
    recommendations: list[str] = field(default_factory=list)


@dataclass
class TestResult:
    participants: int
    items: list[ItemResult]
    mean_score: float
    std_dev: float
    kr20: float | None
    sem: float | None

    def to_dict(self) -> dict:
        return asdict(self)


def difficulty_label(p: float) -> str:
    return "sukar" if p < 0.30 else "mudah" if p > 0.70 else "sedang"


def discrimination_label(d: float) -> str:
    if d >= 0.40:
        return "sangat baik"
    if d >= 0.30:
        return "baik"
    if d >= 0.20:
        return "cukup"
    return "jelek"


def _point_biserial(item: list[int], rest_totals: list[float]) -> float | None:
    """Korelasi butir dengan skor total TANPA butir itu sendiri (corrected item-total)."""
    sd = pstdev(rest_totals)
    p = mean(item)
    if sd == 0 or p in (0, 1):
        return None
    mean_correct = mean(t for s, t in zip(item, rest_totals) if s)
    mean_all = mean(rest_totals)
    return round((mean_correct - mean_all) / sd * sqrt(p / (1 - p)), 3)


def analyze(
    answers: dict[str, dict[str, str]],
    keys: dict[str, str],
) -> TestResult:
    """`answers`: {student_id: {item_id: opsi_dipilih}}; `keys`: {item_id: kunci}.

    Jawaban kosong/tidak ada dihitung salah dan dicatat sebagai opsi "-".
    """
    students = list(answers)
    item_ids = list(keys)
    if len(students) < 2 or not item_ids:
        raise ValueError("Butuh minimal 2 peserta dan 1 butir soal.")

    chosen = {s: {i: (answers[s].get(i) or "-").strip().upper() for i in item_ids} for s in students}
    scored = {s: {i: int(chosen[s][i] == keys[i].strip().upper()) for i in item_ids} for s in students}
    totals = {s: sum(scored[s].values()) for s in students}

    ranked = sorted(students, key=lambda s: totals[s], reverse=True)
    group_size = max(1, round(len(students) * GROUP_FRACTION))
    upper, lower = ranked[:group_size], ranked[-group_size:]

    results: list[ItemResult] = []
    for item in item_ids:
        column = [scored[s][item] for s in students]
        p = round(mean(column), 3)
        d = round(mean(scored[s][item] for s in upper) - mean(scored[s][item] for s in lower), 3)
        rpbis = _point_biserial(column, [totals[s] - scored[s][item] for s in students])

        options = sorted({chosen[s][item] for s in students} | {keys[item].strip().upper()})
        shares = {o: round(sum(chosen[s][item] == o for s in students) / len(students), 3) for o in options}
        nonfunctional = [
            o for o in options
            if o not in {keys[item].strip().upper(), "-"}
            and (
                shares[o] < DISTRACTOR_MIN_SHARE
                or sum(chosen[s][item] == o for s in upper) > sum(chosen[s][item] == o for s in lower)
            )
        ]
        results.append(_judge(ItemResult(
            item_id=item,
            difficulty=p,
            difficulty_label=difficulty_label(p),
            discrimination=d,
            discrimination_label=discrimination_label(d),
            point_biserial=rpbis,
            option_shares=shares,
            nonfunctional_distractors=nonfunctional,
        ), keys[item].strip().upper()))

    total_values = list(totals.values())
    variance = pstdev(total_values) ** 2
    k = len(item_ids)
    kr20 = None
    if k > 1 and variance > 0:
        pq = sum(r.difficulty * (1 - r.difficulty) for r in results)
        kr20 = round(k / (k - 1) * (1 - pq / variance), 3)
    sd = round(pstdev(total_values), 3)

    return TestResult(
        participants=len(students),
        items=results,
        mean_score=round(mean(total_values), 3),
        std_dev=sd,
        kr20=kr20,
        sem=round(sd * sqrt(1 - kr20), 3) if kr20 is not None and kr20 <= 1 else None,
    )


def _judge(r: ItemResult, key: str) -> ItemResult:
    recs: list[str] = []
    if r.discrimination < 0:
        recs.append("Daya pembeda negatif: siswa pintar justru salah. Periksa kunci jawaban dan ambiguitas pokok soal.")
    elif r.discrimination < 0.20:
        recs.append("Daya pembeda jelek: soal tidak membedakan siswa yang paham dan belum paham.")
    elif r.discrimination < 0.30:
        recs.append("Daya pembeda cukup: perjelas pokok soal atau perbaiki pengecoh.")

    if r.difficulty_label == "sukar":
        recs.append("Soal terlalu sukar: cek kesesuaian dengan TP, kejelasan bahasa, atau materi belum tuntas diajarkan.")
    elif r.difficulty_label == "mudah" and r.difficulty > 0.90:
        recs.append("Soal terlalu mudah: kunci mungkin terlihat jelas; perkuat pengecoh atau naikkan level kognitif.")

    for option in r.nonfunctional_distractors:
        recs.append(f"Pengecoh {option} tidak berfungsi: ganti dengan miskonsepsi yang umum terjadi.")
    if r.discrimination < 0 and r.option_shares.get(key, 0) < max(r.option_shares.values()):
        recs.append(f"Opsi lain lebih banyak dipilih daripada kunci ({key}); kemungkinan kunci salah.")

    if r.discrimination < 0.20:
        r.decision = "buang"
    elif r.discrimination < 0.30 or r.nonfunctional_distractors or r.difficulty < 0.10 or r.difficulty > 0.95:
        r.decision = "revisi"
    else:
        r.decision = "terima"
    r.recommendations = recs
    return r
