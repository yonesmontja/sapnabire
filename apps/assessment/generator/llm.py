"""Klien Claude untuk generator soal.

- Structured outputs (Pydantic) → respons pasti sesuai `GeneratedBatch`.
- Streaming + get_final_message() → aman untuk output panjang (banyak soal sekaligus).
- Fallback server-side ("default") → jika model menolak, API mencoba model pengganti.
"""

import logging
from dataclasses import dataclass

import anthropic
from django.conf import settings

from .schemas import GeneratedBatch
from .taxonomy import LEVEL_LABELS, CognitiveLevel

logger = logging.getLogger(__name__)

SYSTEM_PROMPT = """Anda adalah penulis soal asesmen senior untuk sekolah di Indonesia (Kurikulum Merdeka).
Tulis soal dalam Bahasa Indonesia baku yang sesuai usia dan fase peserta didik.

Prinsip kualitas:
- Setiap soal mengukur tepat satu indikator dan sesuai level kognitif yang diminta. Soal HOTS (C4–C6)
  harus memakai stimulus kontekstual (kasus, data, grafik yang dideskripsikan) — bukan sekadar hafalan berlabel sulit.
- Pilihan ganda: tepat satu kunci; pengecoh homogen, masuk akal, dan masing-masing mewakili miskonsepsi nyata.
  Hindari "semua benar"/"semua salah", petunjuk gramatikal, dan opsi yang jauh lebih panjang dari yang lain.
- Pilihan ganda kompleks: dua atau lebih opsi benar, sebutkan semuanya di answer_key.
- Rumus matematika/sains ditulis LaTeX di antara $...$ agar bisa dirender KaTeX dan diekspor ke Word.
- Jika soal butuh gambar, tulis deskripsi gambar yang presisi di image_prompt; soal tetap harus bisa dipahami dari teks.
- Konteks lokal (Papua dan Indonesia) dipakai bila relevan, tanpa stereotip.
- Jangan mengulang soal yang sudah ada di daftar "hindari"."""


class GenerationRefused(Exception):
    pass


@dataclass(frozen=True)
class GenerationRequest:
    subject: str
    grade: str
    material: str
    learning_objective: str
    level: CognitiveLevel
    count: int
    question_type: str
    options_count: int
    avoid_stems: tuple[str, ...] = ()


def build_user_prompt(req: GenerationRequest) -> str:
    avoid = "\n".join(f"- {stem}" for stem in req.avoid_stems[-30:]) or "- (belum ada)"
    option_rule = (
        f"Gunakan {req.options_count} opsi (label mulai dari A)."
        if req.question_type in {"multiple_choice", "complex_multiple_choice"}
        else "Biarkan options berupa list kosong."
    )
    return f"""<konteks>
Mata pelajaran: {req.subject}
Kelas/fase: {req.grade}
Tujuan pembelajaran (TP): {req.learning_objective}
</konteks>

<materi>
{req.material}
</materi>

<tugas>
Buat tepat {req.count} soal bertipe {req.question_type} pada level {req.level.value} ({LEVEL_LABELS[req.level]}).
{option_rule}
Isi cognitive_level setiap soal dengan "{req.level.value}".
</tugas>

<hindari>
{avoid}
</hindari>"""


def generate_items(req: GenerationRequest, client: anthropic.Anthropic | None = None) -> GeneratedBatch:
    client = client or anthropic.Anthropic()
    with client.beta.messages.stream(
        model=settings.AI["MODEL"],
        max_tokens=64000,
        system=SYSTEM_PROMPT,
        messages=[{"role": "user", "content": build_user_prompt(req)}],
        output_format=GeneratedBatch,
        output_config={"effort": "high"},
        betas=["server-side-fallback-2026-07-01"],
        fallbacks="default",
    ) as stream:
        message = stream.get_final_message()

    if message.stop_reason == "refusal":
        category = message.stop_details.category if message.stop_details else None
        raise GenerationRefused(f"Model menolak membuat soal (kategori: {category}).")
    if message.stop_reason == "max_tokens" or message.parsed_output is None:
        raise ValueError(f"Output model tidak lengkap (stop_reason={message.stop_reason}).")

    logger.info(
        "Generated %s items level=%s model=%s in=%s out=%s",
        len(message.parsed_output.items), req.level, message.model,
        message.usage.input_tokens, message.usage.output_tokens,
    )
    return message.parsed_output
