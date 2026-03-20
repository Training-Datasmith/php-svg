<?php

declare (strict_types=1);
namespace SVG\Fonts;

class Font_Registry
{
    private array $font_files = [];
    public function add_font(string $file_path): void
    {
        $ttf_file = True_Type_Font_File::read($file_path);
        if ($ttf_file === null) {
            throw new \RuntimeException('Font file "' . $file_path . '" is not a valid TrueType font.');
        }
        $this->font_files[] = $ttf_file;
    }
    public function find_matching_font(?string $family, ?string $style, float $weight): ?Font_File
    {
        if (empty($this->font_files)) {
            return null;
        }
        // TODO implement generic families ('serif', 'sans-serif', 'monospace', etc.)
        // Check whether the requested font family is available, or whether we don't have to bother checking the family
        // in the following loops.
        $any_font_family = true;
        foreach ($this->font_files as $font) {
            if ($family === $font->get_family()) {
                $any_font_family = false;
            }
        }
        // Attempt to find the closest-weight match with correct family and cursiveness.
        $match = $this->closest_match_based_on_weight(function (Font_File $font) use ($family, $any_font_family, $style): bool {
            $result = $any_font_family || $font->get_family() === $family;
            $is_italic = $font->is_italic();
            $is_oblique = $font->is_oblique();
            switch ($style) {
                case 'italic':
                    return $result && ($is_italic || $is_oblique);
                case 'oblique':
                    return $result && ($is_oblique || $is_italic);
                default:
                    return $result && !($is_italic || $is_oblique);
            }
        }, $weight);
        // Attempt to match just based on the font family.
        $match ??= $this->closest_match_based_on_weight(fn(Font_File $font) => $any_font_family || $font->get_family() === $family, $weight);
        // Return any font at all, if possible.
        return $match ?? $this->font_files[0];
    }
    private function closest_match_based_on_weight(callable $filter, float $target_weight): ?Font_File
    {
        $best_match = null;
        foreach ($this->font_files as $font) {
            if (!$filter($font)) {
                continue;
            }
            if ($best_match === null) {
                $best_match = $font;
                continue;
            }
            if (abs($target_weight - $font->get_weight()) < abs($target_weight - $best_match->get_weight())) {
                $best_match = $font;
            }
        }
        return $best_match;
    }
}