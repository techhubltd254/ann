<?php

namespace App\Services\Onboarding;

/**
 * Sanctions/PEP screening — Algorithm 17 from kicc-algorithms.
 * Jaro-Winkler fuzzy match against a watchlist.
 */
class ScreeningService
{
    private float $threshold;
    private array $watchlist;

    public function __construct(?array $watchlist = null)
    {
        $this->threshold = config('kicc.screening.threshold', 0.85);
        $this->watchlist = $watchlist ?? [
            'John Appropriator', 'Global Terror Fund', 'Example Sanctioned Entity',
        ];
        $this->watchlist = array_map([$this, 'normalize'], $this->watchlist);
    }

    public function screen(string $name): array
    {
        $norm = $this->normalize($name);
        $best = null;
        $bestScore = 0.0;

        foreach ($this->watchlist as $entry) {
            $score = $this->jaroWinkler($norm, $entry);
            if ($score > $bestScore) {
                $best = $entry;
                $bestScore = $score;
            }
        }

        $hit = $bestScore >= $this->threshold;
        return [
            'name'       => $name,
            'normalized' => $norm,
            'match'      => $best,
            'score'      => round($bestScore, 4),
            'hit'        => $hit,
            'action'     => $hit ? 'freeze_pending_review' : 'clear',
        ];
    }

    public function normalize(string $name): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        $text = preg_replace('/[^a-zA-Z0-9 ]/', '', strtolower($text ?? $name));
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    public function jaroWinkler(string $s1, string $s2): float
    {
        if ($s1 === $s2) return 1.0;
        $len1 = strlen($s1);
        $len2 = strlen($s2);
        if ($len1 === 0 || $len2 === 0) return 0.0;

        $matchDist = (int)(max($len1, $len2) / 2 - 1);
        if ($matchDist < 0) $matchDist = 0;
        $m1 = array_fill(0, $len1, false);
        $m2 = array_fill(0, $len2, false);
        $matches = 0;

        for ($i = 0; $i < $len1; $i++) {
            $lo = max(0, $i - $matchDist);
            $hi = min($len2, $i + $matchDist + 1);
            for ($j = $lo; $j < $hi; $j++) {
                if ($j < $len2 && !$m2[$j] && $s1[$i] === $s2[$j]) {
                    $m1[$i] = $m2[$j] = true;
                    $matches++;
                    break;
                }
            }
        }

        if ($matches === 0) return 0.0;

        $transpositions = 0;
        $k = 0;
        for ($i = 0; $i < $len1; $i++) {
            if ($m1[$i]) {
                while ($k < $len2 && !$m2[$k]) $k++;
                if ($k < $len2 && $s1[$i] !== $s2[$k]) $transpositions++;
                $k++;
            }
        }
        $transpositions /= 2;

        $jaro = (($matches / $len1) + ($matches / $len2) + (($matches - $transpositions) / $matches)) / 3;

        $prefix = 0;
        for ($i = 0; $i < min(4, min($len1, $len2)); $i++) {
            if ($s1[$i] === $s2[$i]) $prefix++;
            else break;
        }

        return $jaro + $prefix * 0.1 * (1 - $jaro);
    }
}