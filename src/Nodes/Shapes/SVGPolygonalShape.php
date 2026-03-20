<?php

declare (strict_types=1);
namespace SVG\Nodes\Shapes;

use SVG\Nodes\Svg_Node_Container;
use SVG\Shims\Str;
/**
 * This is the base class for polygons and polylines.
 * Offers methods for manipulating the list of points.
 */
abstract class Svg_Polygonal_Shape extends Svg_Node_Container
{
    /**
     * @param array[]|null $points Array of points (float 2-tuples).
     */
    public function __construct(?array $points = null)
    {
        parent::__construct();
        if ($points !== null) {
            $this->set_attribute('points', self::join_points($points));
        }
    }
    /**
     * Appends a new point to the end of this shape. The point can be given
     * either as a 2-tuple (1 param) or as separate x and y (2 params).
     *
     * @param float|float[] $a The point as an array, or its x coordinate.
     * @param float|null    $b The point's y coordinate, if not given as array.
     *
     * @return $this This node instance, for call chaining.
     */
    public function add_point($a, $b = null): Svg_Polygonal_Shape
    {
        if (is_array($a)) {
            [$a, $b] = $a;
        }
        $points_attribute = $this->get_attribute('points') ?: '';
        $this->set_attribute('points', Str::trim($points_attribute . ' ' . $a . ',' . $b));
        return $this;
    }
    /**
     * Removes the point at the given index from this shape.
     *
     * @param int $index The index of the point to remove.
     *
     * @return $this This node instance, for call chaining.
     */
    public function remove_point(int $index): Svg_Polygonal_Shape
    {
        $coords = self::split_coordinates($this->get_attribute('points') ?: '');
        array_splice($coords, $index * 2, 2);
        $this->set_attribute('points', self::join_coordinates($coords));
        return $this;
    }
    /**
     * @return int The number of points in this shape.
     */
    public function count_points(): int
    {
        $points_attribute = $this->get_attribute('points');
        if (isset($points_attribute)) {
            $coords = self::split_coordinates($points_attribute);
            return (int) (count($coords) / 2);
        }
        return 0;
    }
    /**
     * @return array[] All points in this shape (array of float 2-tuples).
     */
    public function get_points(): array
    {
        $points_attribute = $this->get_attribute('points');
        if (isset($points_attribute)) {
            return self::split_points($points_attribute);
        }
        return [];
    }
    /**
     * @param int $index The index of the point to get.
     *
     * @return float[] The point at the given index (0 => x, 1 => y).
     */
    public function get_point(int $index): array
    {
        $coords = self::split_coordinates($this->get_attribute('points') ?: '');
        return [(float) $coords[$index * 2], (float) $coords[$index * 2 + 1]];
    }
    /**
     * Replaces the point at the given index with a different one.
     *
     * @param int     $index The index of the point to set.
     * @param float[] $point The new point.
     *
     * @return $this This node instance, for call chaining.
     */
    public function set_point(int $index, array $point): Svg_Polygonal_Shape
    {
        $coords = self::split_coordinates($this->get_attribute('points') ?: '');
        $coords[$index * 2] = $point[0];
        $coords[$index * 2 + 1] = $point[1];
        $this->set_attribute('points', self::join_coordinates($coords));
        return $this;
    }
    private static function split_coordinates(?string $points_string): array
    {
        return preg_split('/[\s,]+/', Str::trim($points_string));
    }
    private static function join_coordinates(array $coordinates_array): string
    {
        $points_string = '';
        for ($i = 0, $n = count($coordinates_array); $i < $n; ++$i) {
            if ($i > 0) {
                // join coordinates with ',' and points (2 coordinates) with ' '
                $points_string .= $i % 2 === 1 ? ',' : ' ';
            }
            $points_string .= $coordinates_array[$i];
        }
        return $points_string;
    }
    private static function split_points(?string $points_string): array
    {
        $points_array = [];
        $coords = self::split_coordinates($points_string);
        for ($i = 0, $n = count($coords); $i + 1 < $n; $i += 2) {
            $points_array[] = [(float) $coords[$i], (float) $coords[$i + 1]];
        }
        return $points_array;
    }
    private static function join_points(array $points_array): string
    {
        $points_string = '';
        foreach ($points_array as $point) {
            if (count($point) < 2) {
                break;
            }
            if ($points_string !== '') {
                $points_string .= ' ';
            }
            $points_string .= $point[0] . ',' . $point[1];
        }
        return $points_string;
    }
}