<?php

namespace App\Services\Ai;

use App\Models\Menu;
use App\Models\MenuAlias;
use Illuminate\Database\Eloquent\Collection;

class MenuMatcher
{
    protected ?Collection $menusCache = null;

    public function setMenus(Collection $menus): void
    {
        $this->menusCache = $menus;
    }

    public function clearCache(): void
    {
        $this->menusCache = null;
    }

    protected function getActiveMenus(): Collection
    {
        if ($this->menusCache === null) {
            $this->menusCache = Menu::with('aliases')->where('is_active', true)->get();
        }

        return $this->menusCache;
    }

    /**
     * Match a raw item name against all registered menus and aliases.
     *
     * @return array{menu: ?Menu, score: float, is_matched: bool, matched_name: ?string}
     */
    public function match(string $rawName): array
    {
        $rawClean = trim($rawName);
        $normalizedRaw = MenuAlias::normalize($rawClean);

        if (empty($normalizedRaw)) {
            return [
                'menu' => null,
                'score' => 0.0,
                'is_matched' => false,
                'matched_name' => null,
            ];
        }

        $menus = $this->getActiveMenus();

        $bestScore = 0.0;
        $bestMenu = null;
        $bestMatchedName = null;

        foreach ($menus as $menu) {
            // 1. Direct name check
            $menuNormalized = MenuAlias::normalize($menu->name);
            if ($menuNormalized === $normalizedRaw) {
                return [
                    'menu' => $menu,
                    'score' => 1.0,
                    'is_matched' => true,
                    'matched_name' => $menu->name,
                ];
            }

            // Check similarity with menu name
            $sim = $this->calculateSimilarity($normalizedRaw, $menuNormalized);
            if ($sim > $bestScore) {
                $bestScore = $sim;
                $bestMenu = $menu;
                $bestMatchedName = $menu->name;
            }

            // 2. Check aliases
            foreach ($menu->aliases as $alias) {
                if ($alias->normalized_alias === $normalizedRaw) {
                    return [
                        'menu' => $menu,
                        'score' => 0.98,
                        'is_matched' => true,
                        'matched_name' => $alias->alias,
                    ];
                }

                $aliasSim = $this->calculateSimilarity($normalizedRaw, $alias->normalized_alias);
                if ($aliasSim > $bestScore) {
                    $bestScore = $aliasSim;
                    $bestMenu = $menu;
                    $bestMatchedName = $alias->alias;
                }
            }
        }

        $isMatched = $bestScore >= 0.85;

        return [
            'menu' => $bestMenu,
            'score' => round($bestScore, 2),
            'is_matched' => $isMatched,
            'matched_name' => $bestMatchedName,
        ];
    }

    protected function calculateSimilarity(string $str1, string $str2): float
    {
        if ($str1 === $str2) {
            return 1.0;
        }

        similar_text($str1, $str2, $percent);
        $simText = $percent / 100.0;

        $lev = levenshtein($str1, $str2);
        $maxLen = max(strlen($str1), strlen($str2));
        $levScore = $maxLen > 0 ? (1.0 - ($lev / $maxLen)) : 0.0;

        return max($simText, $levScore);
    }
}
