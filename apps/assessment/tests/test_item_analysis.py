from django.test import SimpleTestCase

from analysis.item_analysis import analyze
from analysis.mastery import mastery_report

KEYS = {"Q1": "A", "Q2": "B", "Q3": "C"}


def sheet(rows: dict[str, str]) -> dict[str, dict[str, str]]:
    return {student: dict(zip(KEYS, answers)) for student, answers in rows.items()}


class ItemAnalysisTests(SimpleTestCase):
    def setUp(self):
        # Q1: semua benar (terlalu mudah). Q2: hanya siswa pintar benar (pembeda baik).
        # Q3: hanya siswa lemah benar (pembeda negatif → kunci patut dicurigai).
        self.result = analyze(sheet({
            "s1": "ABD", "s2": "ABD", "s3": "ABD", "s4": "ABD",
            "s5": "ADD", "s6": "ADC", "s7": "ACC", "s8": "ACC",
        }), KEYS)
        self.items = {r.item_id: r for r in self.result.items}

    def test_difficulty(self):
        self.assertEqual(self.items["Q1"].difficulty, 1.0)
        self.assertEqual(self.items["Q1"].difficulty_label, "mudah")
        self.assertEqual(self.items["Q2"].difficulty, 0.5)

    def test_discrimination_and_decision(self):
        self.assertEqual(self.items["Q2"].discrimination, 1.0)
        self.assertEqual(self.items["Q2"].discrimination_label, "sangat baik")
        self.assertLess(self.items["Q3"].discrimination, 0)
        self.assertEqual(self.items["Q3"].decision, "buang")
        self.assertTrue(any("kunci" in rec.lower() for rec in self.items["Q3"].recommendations))

    def test_nonfunctional_distractor_detected(self):
        # Pada Q2, opsi D dipilih siswa lemah (berfungsi), opsi A tidak dipilih siapa pun → tidak muncul.
        self.assertNotIn("D", self.items["Q2"].nonfunctional_distractors)

    def test_test_level_statistics(self):
        self.assertEqual(self.result.participants, 8)
        self.assertIsNotNone(self.result.kr20)

    def test_mastery_report(self):
        report = mastery_report({"s1": 90, "s2": 70, "s3": 50, "s4": 30}, threshold=66)
        self.assertEqual(report["remedial"], ["s4", "s3"])
        self.assertEqual(report["enrichment"], ["s1"])
        self.assertEqual(report["distribution"]["Mulai berkembang"], 1)
