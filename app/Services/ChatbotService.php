<?php

namespace App\Services;

use App\Models\Faq;

class ChatbotService
{
    /**
     * Process a user message and return a bot response using FAQ matching only.
     */
    public function respond(string $message, array $history = [], ?int $userId = null): array
    {
        $topFaqs = $this->findTopFaqs($message, 1);

        if (!empty($topFaqs)) {
            return [
                'message'        => $topFaqs[0]['answer'],
                'suggest_ticket' => false,
            ];
        }

        return [
            'message'        => '<p>Paumanhin, hindi ko mahanap ang sagot sa iyong tanong. Maaari kang mag-create ng support ticket para matulungan ka ng aming team.</p>',
            'suggest_ticket' => true,
        ];
    }

    /**
     * Find the top N most relevant FAQs using multi-signal scoring.
     * Handles both English and Filipino queries.
     *
     * @return array<int, array{question: string, answer: string, score: int}>
     */
    private function findTopFaqs(string $query, int $limit = 3): array
    {
        $faqs = Faq::published()->with('category')->get();

        if ($faqs->isEmpty()) {
            return [];
        }

        $queryNorm  = strtolower(preg_replace('/[^\p{L}0-9 ]/u', ' ', $query));
        $queryWords = array_filter(array_unique(explode(' ', $queryNorm)), fn($w) => strlen($w) >= 2);

        if (empty($queryWords)) {
            return [];
        }

        $scored = [];

        foreach ($faqs as $faq) {
            $plainAnswer  = strtolower(strip_tags($faq->answer));
            $questionNorm = strtolower(preg_replace('/[^\p{L}0-9 ]/u', ' ', $faq->question));

            $score = 0;

            foreach ($queryWords as $word) {
                if (str_contains($questionNorm, $word)) {
                    $score += 4;
                }
                if (str_contains($plainAnswer, $word)) {
                    $score += 2;
                }
            }

            // Bonus: exact phrase match
            if (str_contains($questionNorm, $queryNorm)) {
                $score += 8;
            }
            if (str_contains($plainAnswer, $queryNorm)) {
                $score += 4;
            }

            if ($score > 0) {
                $scored[] = [
                    'question' => $faq->question,
                    'answer'   => $faq->answer,
                    'category' => $faq->category?->name ?? '',
                    'score'    => $score,
                ];
            }
        }

        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $limit);
    }
}
