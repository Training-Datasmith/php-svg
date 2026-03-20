<?php

declare (strict_types=1);
namespace SVG\Rasterization\Renderers;

use SVG\Fonts\Font_Registry;
use SVG\Rasterization\Transform\Transform;
/**
 * This renderer can draw basic text.
 *
 * Options:
 * - float x: the x coordinate of the text
 * - float y: the y coordinate of the baseline
 * - string anchor: the anchor point (start|middle|end) for x coordinate
 * - float fontSize: the font size
 * - string fontFamily: the font family
 * - string fontStyle: the font style (normal|italic|oblique)
 * - string fontWeight: the font weight (normal|bold|bolder|lighter|(number))
 * - string text: the text to draw
 */
class Text_Renderer extends Multi_Pass_Renderer
{
    /**
     * @inheritdoc
     */
    protected function prepare_render_params(array $options, Transform $transform, ?Font_Registry $font_registry): ?array
    {
        // this assumes there is no rotation or skew, but that's fine, we can't deal with that anyway
        $size1 = $options['fontSize'];
        $size2 = $size1;
        $transform->resize($size1, $size2);
        $size = min($size1, $size2);
        $font_path = null;
        if (isset($font_registry)) {
            $weight = self::resolve_font_weight($options['fontWeight']);
            $matching_font = $font_registry->find_matching_font($options['fontFamily'], $options['fontStyle'], $weight);
            if ($matching_font !== null) {
                $font_path = $matching_font->get_path();
            }
        }
        if (!isset($font_path)) {
            return null;
        }
        // text-anchor
        $anchor_offset = 0;
        if ($options['anchor'] === 'middle' || $options['anchor'] === 'end') {
            $width = self::calculate_text_width($options['text'], $font_path, $size);
            $anchor_offset = $options['anchor'] === 'middle' ? $width / 2 : $width;
        }
        $x = $options['x'];
        $y = $options['y'];
        $transform->map($x, $y);
        return ['x' => $x - $anchor_offset, 'y' => $y, 'size' => $size, 'fontPath' => $font_path, 'text' => $options['text']];
    }
    /**
     * @inheritdoc
     */
    protected function render_fill($image, $params, int $color): void
    {
        imagettftext($image, $params['size'], 0, $params['x'], $params['y'], $color, $params['fontPath'], $params['text']);
    }
    /**
     * @inheritdoc
     */
    protected function render_stroke($image, $params, int $color, float $stroke_width): void
    {
        $x = $params['x'];
        $y = $params['y'];
        $px = $stroke_width;
        for ($c1 = $x - abs($px); $c1 <= $x + abs($px); $c1++) {
            for ($c2 = $y - abs($px); $c2 <= $y + abs($px); $c2++) {
                imagettftext($image, $params['size'], 0, $c1, $c2, $color, $params['fontPath'], $params['text']);
            }
        }
    }
    /**
     * Compute the width, in pixels, of the given text.
     *
     * @param string $text The text to measure.
     * @param string $fontFile The font file path.
     * @param float $size The font size in pixels.
     *
     * @return float The width in pixels.
     */
    private static function calculate_text_width(string $text, string $font_file, float $size): float
    {
        // note for future: imagettfbbox is unable to calculate height properly.
        // width should be fine though.
        $box = imagettfbbox($size, 0, $font_file, $text);
        $min_x = min($box[0], $box[2], $box[4], $box[6]);
        $max_x = max($box[0], $box[2], $box[4], $box[6]);
        return abs($max_x - $min_x);
    }
    private static function resolve_font_weight($weight): int
    {
        // TODO implement "bolder" and "lighter"
        if (is_numeric($weight)) {
            return (int) $weight;
        }
        // TODO implement "bolder" and "lighter"
        if ($weight === 'bold') {
            return 700;
        }
        return 400;
    }
}