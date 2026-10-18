"""Kontrak output model (structured outputs). Pydantic → JSON Schema dikirim ke Claude
dan respons divalidasi ulang di sisi kita, sehingga soal yang tersimpan selalu berbentuk benar."""

from typing import Literal

from pydantic import BaseModel, ConfigDict, Field


class Option(BaseModel):
    model_config = ConfigDict(extra="forbid")

    label: Literal["A", "B", "C", "D", "E"]
    text: str
    is_correct: bool
    rationale: str = Field(description="Alasan opsi ini benar, atau miskonsepsi yang diwakili pengecoh.")


class GeneratedItem(BaseModel):
    model_config = ConfigDict(extra="forbid")

    question_type: Literal["multiple_choice", "complex_multiple_choice", "true_false", "short_answer", "essay"]
    cognitive_level: Literal["C1", "C2", "C3", "C4", "C5", "C6"]
    indicator: str = Field(description="Indikator soal untuk kisi-kisi, diawali kata kerja operasional.")
    stimulus: str = Field(description="Teks/konteks stimulus. String kosong jika tidak ada.")
    stem: str = Field(description="Pokok soal. Rumus ditulis dalam LaTeX di antara $...$.")
    options: list[Option] = Field(description="Kosong untuk short_answer dan essay.")
    answer_key: str
    explanation: str = Field(description="Pembahasan langkah demi langkah.")
    scoring_rubric: str = Field(description="Rubrik penskoran untuk essay/short_answer; string kosong untuk pilihan ganda.")
    image_prompt: str = Field(description="Prompt gambar ilustrasi bila soal butuh gambar (sains), else string kosong.")


class GeneratedBatch(BaseModel):
    model_config = ConfigDict(extra="forbid")

    items: list[GeneratedItem]
