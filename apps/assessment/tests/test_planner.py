from django.test import SimpleTestCase

from generator.planner import allocate, expand_tiers, plan_chunks


class PlannerTests(SimpleTestCase):
    def test_allocation_always_sums_to_total(self):
        for total in (1, 7, 33, 100, 499):
            result = allocate(total, {"C1": 15, "C2": 15, "C3": 30, "C4": 20, "C5": 15, "C6": 5})
            self.assertEqual(sum(result.values()), total)

    def test_largest_remainder(self):
        self.assertEqual(allocate(10, {"C1": 1, "C2": 1, "C3": 1}), {"C1": 4, "C2": 3, "C3": 3})

    def test_tier_expansion(self):
        weights = expand_tiers({"LOTS": 30, "MOTS": 40, "HOTS": 30})
        self.assertEqual(allocate(100, weights), {"C1": 15, "C2": 15, "C3": 40, "C4": 10, "C5": 10, "C6": 10})

    def test_chunks_respect_size(self):
        chunks = plan_chunks(25, {"C3": 1}, chunk_size=10)
        self.assertEqual([c.count for c in chunks], [10, 10, 5])

    def test_rejects_empty_distribution(self):
        with self.assertRaises(ValueError):
            allocate(10, {"C1": 0})
