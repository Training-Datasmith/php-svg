<?php

declare (strict_types=1);
namespace SVG\Rasterization\Path;

/**
 * This class can approximate elliptical arc segments by calculating a series of
 * points on them (converting them to polylines).
 */
class Arc_Approximator
{
    private static float $EPSILON = 1.0E-7;
    /**
     * Approximates an elliptical arc segment given the start point, the end
     * point, the section to use (large or small), the sweep direction,
     * the ellipse's radii, and its rotation.
     *
     * All of the points (input and output) are represented as float arrays
     * where [0 => x coordinate, 1 => y coordinate].
     *
     * The image scale can be given for somewhat better approximation. For example, when the image coordinate space
     * is 4 times larger than path coordinate space, the scale should be 4. The result in that case will be that
     * 4 times as many points are generated.
     *
     * @param float[] $start    The start point (x0, y0).
     * @param float[] $end      The end point (x1, y1).
     * @param bool    $large    The large arc flag.
     * @param bool    $sweep    The sweep direction flag.
     * @param float   $radiusX  The x radius.
     * @param float   $radiusY  The y radius.
     * @param float   $rotation The x-axis angle / the ellipse's rotation (radians).
     * @param float   $scale    The scale factor to go from path coordinate space to image space.
     *
     * @return array[] An approximation for the curve, as an array of points.
     */
    public function approximate(array $start, array $end, bool $large, bool $sweep, float $radius_x, float $radius_y, float $rotation, float $scale = 1.0): array
    {
        // out-of-range parameter handling according to W3; see
        // https://www.w3.org/TR/SVG11/implnote.html#ArcImplementationNotes
        if (self::points_close($start, $end)) {
            // arc with equal points is treated as nonexistent
            return [];
        }
        $radius_x = abs($radius_x);
        $radius_y = abs($radius_y);
        if ($radius_x < self::$EPSILON || $radius_y < self::$EPSILON) {
            // arc with no radius is treated as straight line
            return [$start, $end];
        }
        $cosr = cos($rotation);
        $sinr = sin($rotation);
        [$center, $radius_x, $radius_y, $angle_start, $angle_delta] = self::endpoint_to_center($start, $end, $large, $sweep, $radius_x, $radius_y, $cosr, $sinr);
        $dist = abs($end[0] - $start[0]) + abs($end[1] - $start[1]);
        $num_steps = max(2, ceil(abs($angle_delta * $dist * $scale)));
        $step_size = $angle_delta / $num_steps;
        $points = [];
        for ($i = 0; $i <= $num_steps; ++$i) {
            $angle = $angle_start + $step_size * $i;
            $first = $radius_x * cos($angle);
            $second = $radius_y * sin($angle);
            $points[] = [$cosr * $first - $sinr * $second + $center[0], $sinr * $first + $cosr * $second + $center[1]];
        }
        return $points;
    }
    /**
     * Converts an ellipse in endpoint parameterization (standard for SVG paths)
     * to the corresponding center parameterization (easier to work with).
     *
     * In other words, takes two points, sweep flags, and size/orientation
     * values and computes from them the ellipse's optimal center point and the
     * angles the segment covers. For this, the start angle and the angle delta
     * are returned.
     *
     * If the radii are too small, they are scaled. The new radii are returned.
     *
     * The formulas can be found in W3's SVG spec.
     *
     * @see https://www.w3.org/TR/SVG11/implnote.html#ArcImplementationNotes
     *
     * @param float[] $start   The start point (x0, y0).
     * @param float[] $end     The end point (x1, y1).
     * @param bool    $large   The large arc flag.
     * @param bool    $sweep   The sweep direction flag.
     * @param float   $radiusX The x radius.
     * @param float   $radiusY The y radius.
     * @param float   $cosr    Cosine of the ellipse's rotation.
     * @param float   $sinr    Sine of the ellipse's rotation.
     *
     * @return float[] A tuple with (center(cx,cy), radiusX, radiusY, angleStart, angleDelta).
     */
    private static function endpoint_to_center(array $start, array $end, bool $large, bool $sweep, float $radius_x, float $radius_y, float $cosr, float $sinr): array
    {
        // Step 1: Compute (x1', y1') [F.6.5.1]
        $xsubhalf = ($start[0] - $end[0]) / 2;
        $ysubhalf = ($start[1] - $end[1]) / 2;
        $x1prime = $cosr * $xsubhalf + $sinr * $ysubhalf;
        $y1prime = -$sinr * $xsubhalf + $cosr * $ysubhalf;
        // squares that occur multiple times
        $rx2 = $radius_x * $radius_x;
        $ry2 = $radius_y * $radius_y;
        $x1prime2 = $x1prime * $x1prime;
        $y1prime2 = $y1prime * $y1prime;
        // Ensure radiuses are large enough [F.6.6.2]
        $lambda_sqrt = sqrt($x1prime2 / $rx2 + $y1prime2 / $ry2);
        if ($lambda_sqrt > 1) {
            $radius_x *= $lambda_sqrt;
            $radius_y *= $lambda_sqrt;
            $rx2 = $radius_x * $radius_x;
            $ry2 = $radius_y * $radius_y;
        }
        // Step 2: Compute (cx', cy') [F.6.5.2]
        $cxfactor = ($large != $sweep ? 1 : -1) * sqrt(abs(($rx2 * $ry2 - $rx2 * $y1prime2 - $ry2 * $x1prime2) / ($rx2 * $y1prime2 + $ry2 * $x1prime2)));
        $cxprime = $cxfactor * $radius_x * $y1prime / $radius_y;
        $cyprime = $cxfactor * -$radius_y * $x1prime / $radius_x;
        // Step 3: Compute (cx, cy) from (cx', cy') [F.6.5.3]
        $center_x = $cosr * $cxprime - $sinr * $cyprime + ($start[0] + $end[0]) / 2;
        $center_y = $sinr * $cxprime + $cosr * $cyprime + ($start[1] + $end[1]) / 2;
        // Step 4: Compute the angles [F.6.5.5, F.6.5.6]
        $angle_start = self::vector_angle(($x1prime - $cxprime) / $radius_x, ($y1prime - $cyprime) / $radius_y);
        $angle_delta = self::vector_angle2(($x1prime - $cxprime) / $radius_x, ($y1prime - $cyprime) / $radius_y, (-$x1prime - $cxprime) / $radius_x, (-$y1prime - $cyprime) / $radius_y);
        // Adapt angles to sweep flags
        if (!$sweep && $angle_delta > 0) {
            $angle_delta -= M_PI * 2;
        } elseif ($sweep && $angle_delta < 0) {
            $angle_delta += M_PI * 2;
        }
        return [[$center_x, $center_y], $radius_x, $radius_y, $angle_start, $angle_delta];
    }
    /**
     * Computes the angle between a vector and the positive x axis.
     * This is a simplified version of vectorAngle2, where the first vector is
     * fixed as [1, 0].
     *
     * @param float $vecx The vector's x coordinate.
     * @param float $vecy The vector's y coordinate.
     *
     * @return float The angle, in radians.
     */
    private static function vector_angle(float $vecx, float $vecy): float
    {
        $norm = hypot($vecx, $vecy);
        return ($vecy >= 0 ? 1 : -1) * acos($vecx / $norm);
    }
    /**
     * Computes the angle between two given vectors.
     *
     * @param float $vec1x First vector's x coordinate.
     * @param float $vec1y First vector's y coordinate.
     * @param float $vec2x Second vector's x coordinate.
     * @param float $vec2y Second vector's y coordinate.
     *
     * @return float The angle, in radians.
     */
    private static function vector_angle2(float $vec1x, float $vec1y, float $vec2x, float $vec2y): float
    {
        // see W3C [F.6.5.4]
        $dotprod = $vec1x * $vec2x + $vec1y * $vec2y;
        $norm = hypot($vec1x, $vec1y) * hypot($vec2x, $vec2y);
        $sign = $vec1x * $vec2y - $vec1y * $vec2x >= 0 ? 1 : -1;
        return $sign * acos($dotprod / $norm);
    }
    /**
     * Determine whether two points are basically the same, except for minuscule
     * differences.
     *
     * @param float[] $vec1 The start point (x0, y0).
     * @param float[] $vec2 The end point (x1, y1).
     * @return bool Whether the points are close.
     */
    private static function points_close(array $vec1, array $vec2): bool
    {
        $distance_x = abs($vec1[0] - $vec2[0]);
        $distance_y = abs($vec1[1] - $vec2[1]);
        return $distance_x < self::$EPSILON && $distance_y < self::$EPSILON;
    }
}