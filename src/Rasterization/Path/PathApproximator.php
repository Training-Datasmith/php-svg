<?php

declare (strict_types=1);
namespace SVG\Rasterization\Path;

use SVG\Rasterization\Transform\Transform;
/**
 * This class can trace a path by converting its commands into a series of points. Curves are approximated and output
 * as polyline segments.
 *
 * The transform to use for mapping coordinates into the final image must be supplied during this step, so that
 * the approximation accuracy for each segment can be properly chosen according to the output resolution instead of
 * the input coordinates (which may have a completely arbitrary scale).
 *
 * There should be one instance created per path, as this class must keep some state during processing.
 */
class Path_Approximator
{
    /**
     * @var string[] $commands A map of command ids to approximation functions.
     */
    private static array $commands = ['M' => 'moveTo', 'm' => 'moveTo', 'L' => 'lineTo', 'l' => 'lineTo', 'H' => 'lineToHorizontal', 'h' => 'lineToHorizontal', 'V' => 'lineToVertical', 'v' => 'lineToVertical', 'C' => 'curveToCubic', 'c' => 'curveToCubic', 'S' => 'curveToCubicSmooth', 's' => 'curveToCubicSmooth', 'Q' => 'curveToQuadratic', 'q' => 'curveToQuadratic', 'T' => 'curveToQuadraticSmooth', 't' => 'curveToQuadraticSmooth', 'A' => 'arcTo', 'a' => 'arcTo', 'Z' => 'closePath', 'z' => 'closePath'];
    /**
     * @var BezierApproximator $bezier The singleton bezier approximator.
     */
    private static Bezier_Approximator $bezier;
    /**
     * @var ArcApproximator $arc The singleton arc approximator.
     */
    private static Arc_Approximator $arc;
    /**
     * @var Transform $transform The transform to use.
     */
    private Transform $transform;
    /**
     * @var float[][][] $subpaths The approximation result up until now.
     */
    private array $subpaths = [];
    /**
     * @var PolygonBuilder|null $builder The current subpath builder.
     */
    private ?Polygon_Builder $builder = null;
    // the start of the current subpath, in path coordinates
    private $first_x;
    private $first_y;
    // the most recently added point of the current subpath, in path coordinates
    private $pos_x;
    private $pos_y;
    /**
     * @var string $previousCommand The id of the last computed command.
     */
    private $previous_command;
    /**
     * @var float[] $cubicOld Second control point of last C or S command.
     */
    private ?array $cubic_old = null;
    /**
     * @var float[] $quadraticOld Control point of last Q or T command.
     */
    private ?array $quadratic_old = null;
    /**
     * Construct a new, empty approximator.
     *
     * @param Transform $transform The transform from path coordinates into image coordinates.
     */
    public function __construct(Transform $transform)
    {
        $this->transform = $transform;
        if (isset(self::$bezier)) {
            return;
        }
        self::$bezier = new Bezier_Approximator();
        self::$arc = new Arc_Approximator();
    }
    /**
     * Traces/approximates the path described by the given array of commands. According to the SVG spec, the first
     * command must be a "moveto" command (either relative or absolute). Approximation will stop when unknown or
     * invalid commands are encountered (that is, no more points will be generated in that case).
     *
     * After this function has completed, the resulting subpaths can be obtained via <code>getSubpaths()</code>.
     *
     * Example input:
     * ```php
     * [
     *     ['id' => 'M', 'args' => [10, 20]],
     *     ['id' => 'l', 'args' => [40, 20]],
     *     ['id' => 'Z', 'args' => []],
     * ]
     * ```
     *
     * The behavior when this is called multiple times is unspecified.
     *
     * @param array[] $commands The commands (assoc. arrays; see above).
     */
    public function approximate(array $commands): void
    {
        // https://www.w3.org/TR/SVG/paths.html#PathDataMovetoCommands
        // "A path data segment (if there is one) must begin with a "moveto" command."
        if (empty($commands) || $commands[0]['id'] !== 'M' && $commands[0]['id'] !== 'm') {
            return;
        }
        // These variables are used to track the current position in *path coordinate space*.
        // We cannot simply use the PolygonBuilder's last position for this, because that has already been transformed.
        $this->first_x = $this->pos_x = 0;
        $this->first_y = $this->pos_y = 0;
        foreach ($commands as $cmd) {
            $id = $cmd['id'];
            if (!isset(self::$commands[$id])) {
                // https://svgwg.org/svg2-draft/paths.html#PathDataErrorHandling
                // "The general rule for error handling in path data is that the SVG user agent shall render a 'path'
                // element up to (but not including) the path command containing the first error in the path data
                // specification."
                break;
            }
            $func_name = self::$commands[$id];
            $this->{$func_name}($id, $cmd['args']);
            $this->previous_command = $id;
        }
        // The path might end with an unclosed segment. In that case, we want to append it now.
        $this->append_subpath();
    }
    /**
     * Obtain the resulting subpath array after approximation.
     *
     * This array contains an entry for each subpath. Such an entry is itself an array of points.
     * Each point is an array of two floats (the x and y coordinates).
     *
     * @return float[][][] The approximated subpaths.
     */
    public function get_subpaths(): array
    {
        return $this->subpaths;
    }
    /**
     * Complete the current subpath by appending the builder's points as a new subpath array to the array of all
     * subpaths. Subpaths containing no points or only a single point (for example, because their only command was
     * a "moveto") will not be appended.
     */
    private function append_subpath(): void
    {
        if (isset($this->builder)) {
            $points = $this->builder->build();
            if (count($points) > 1) {
                $this->subpaths[] = $points;
            }
        }
    }
    /**
     * Append the current subpath, then start a new one at the current position.
     * The builder will also have the current position added to it as a point.
     */
    private function new_subpath(): void
    {
        $this->append_subpath();
        $builder_x = $this->pos_x;
        $builder_y = $this->pos_y;
        $this->transform->map($builder_x, $builder_y);
        $this->builder = new Polygon_Builder($builder_x, $builder_y);
        $this->builder->add_point($builder_x, $builder_y);
    }
    /**
     * Calculates the reflection of $p relative to $r. Returns a point.
     *
     * @param float[] $p The point to be reflected (x, y).
     * @param float[] $r The point that $p is reflected relative to (x, y).
     *
     * @return float[] The reflected point (x, y).
     */
    private static function reflect_point(array $p, array $r): array
    {
        return [2 * $r[0] - $p[0], 2 * $r[1] - $p[1]];
    }
    /**
     * Approximation function for MoveTo (M and m).
     *
     * @param string  $id   The actual id used (for abs. vs. rel.).
     * @param float[] $args The arguments provided to the command.
     *
     *
     * @SuppressWarnings("unused")
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private function move_to(string $id, array $args): void
    {
        [$x, $y] = $args;
        if ($id === 'm') {
            $x += $this->pos_x;
            $y += $this->pos_y;
        }
        $this->first_x = $this->pos_x = $x;
        $this->first_y = $this->pos_y = $y;
        $this->new_subpath();
    }
    /**
     * Approximation function for LineTo (L and l).
     *
     * @param string  $id   The actual id used (for abs. vs. rel.).
     * @param float[] $args The arguments provided to the command.
     *
     *
     * @SuppressWarnings("unused")
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private function line_to(string $id, array $args): void
    {
        [$x, $y] = $args;
        if ($id === 'l') {
            $x += $this->pos_x;
            $y += $this->pos_y;
        }
        $this->pos_x = $x;
        $this->pos_y = $y;
        $this->transform->map($x, $y);
        $this->builder->add_point($x, $y);
    }
    /**
     * Approximation function for LineToHorizontal (H and h).
     *
     * @param string  $id   The actual id used (for abs. vs. rel.).
     * @param float[] $args The arguments provided to the command.
     *
     *
     * @SuppressWarnings("unused")
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private function line_to_horizontal(string $id, array $args): void
    {
        $x = $args[0];
        $y = $this->pos_y;
        if ($id === 'h') {
            $x += $this->pos_x;
        }
        $this->pos_x = $x;
        $this->transform->map($x, $y);
        $this->builder->add_point($x, $y);
    }
    /**
     * Approximation function for LineToVertical (V and v).
     *
     * @param string  $id   The actual id used (for abs. vs. rel.).
     * @param float[] $args The arguments provided to the command.
     *
     *
     * @SuppressWarnings("unused")
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private function line_to_vertical(string $id, array $args): void
    {
        $x = $this->pos_x;
        $y = $args[0];
        if ($id === 'v') {
            $y += $this->pos_y;
        }
        $this->pos_y = $y;
        $this->transform->map($x, $y);
        $this->builder->add_point($x, $y);
    }
    /**
     * Approximation function for CurveToCubic (C and c).
     *
     * @param string  $id   The actual id used (for abs. vs. rel.).
     * @param float[] $args The arguments provided to the command.
     *
     *
     * @SuppressWarnings("unused")
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private function curve_to_cubic(string $id, array $args): void
    {
        // NOTE: Bézier curves are invariant under affine transforms.
        //       This means transforming the control points vs. transforming the final approximated pixels does not
        //       affect the nature of the curve. This is great! By transforming first, we can choose the approximation
        //       accuracy properly for the output image size.
        // the transformed $p0 is simply $builder->getPosition()
        $p1 = [$args[0], $args[1]];
        $p2 = [$args[2], $args[3]];
        $p3 = [$args[4], $args[5]];
        if ($id === 'c') {
            $p1[0] += $this->pos_x;
            $p1[1] += $this->pos_y;
            $p2[0] += $this->pos_x;
            $p2[1] += $this->pos_y;
            $p3[0] += $this->pos_x;
            $p3[1] += $this->pos_y;
        }
        $this->cubic_old = $p2;
        [$this->pos_x, $this->pos_y] = $p3;
        $this->transform->map($p1[0], $p1[1]);
        $this->transform->map($p2[0], $p2[1]);
        $this->transform->map($p3[0], $p3[1]);
        $approx = self::$bezier->cubic($this->builder->get_position(), $p1, $p2, $p3);
        $this->builder->add_points($approx);
    }
    /**
     * Approximation function for CurveToCubicSmooth (S and s).
     *
     * @param string  $id   The actual id used (for abs. vs. rel.).
     * @param float[] $args The arguments provided to the command.
     *
     *
     * @SuppressWarnings("unused")
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private function curve_to_cubic_smooth(string $id, array $args): void
    {
        $p1 = [$this->pos_x, $this->pos_y];
        // first control point defaults to current point
        $p2 = [$args[0], $args[1]];
        $p3 = [$args[2], $args[3]];
        if ($id === 's') {
            $p2[0] += $this->pos_x;
            $p2[1] += $this->pos_y;
            $p3[0] += $this->pos_x;
            $p3[1] += $this->pos_y;
        }
        // calculate first control point
        $prev = strtolower($this->previous_command);
        if ($prev === 'c' || $prev === 's') {
            $p1 = self::reflect_point($this->cubic_old, $p1);
        }
        $this->cubic_old = $p2;
        [$this->pos_x, $this->pos_y] = $p3;
        $this->transform->map($p1[0], $p1[1]);
        $this->transform->map($p2[0], $p2[1]);
        $this->transform->map($p3[0], $p3[1]);
        $approx = self::$bezier->cubic($this->builder->get_position(), $p1, $p2, $p3);
        $this->builder->add_points($approx);
    }
    /**
     * Approximation function for CurveToQuadratic (Q and q).
     *
     * @param string  $id   The actual id used (for abs. vs. rel.).
     * @param float[] $args The arguments provided to the command.
     *
     *
     * @SuppressWarnings("unused")
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private function curve_to_quadratic(string $id, array $args): void
    {
        $p1 = [$args[0], $args[1]];
        $p2 = [$args[2], $args[3]];
        if ($id === 'q') {
            $p1[0] += $this->pos_x;
            $p1[1] += $this->pos_y;
            $p2[0] += $this->pos_x;
            $p2[1] += $this->pos_y;
        }
        $this->quadratic_old = $p1;
        [$this->pos_x, $this->pos_y] = $p2;
        $this->transform->map($p1[0], $p1[1]);
        $this->transform->map($p2[0], $p2[1]);
        $approx = self::$bezier->quadratic($this->builder->get_position(), $p1, $p2);
        $this->builder->add_points($approx);
    }
    /**
     * Approximation function for CurveToQuadraticSmooth (T and t).
     *
     * @param string  $id   The actual id used (for abs. vs. rel.).
     * @param float[] $args The arguments provided to the command.
     *
     *
     * @SuppressWarnings("unused")
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private function curve_to_quadratic_smooth(string $id, array $args): void
    {
        $p1 = [$this->pos_x, $this->pos_y];
        // control point defaults to current point
        $p2 = [$args[0], $args[1]];
        if ($id === 't') {
            $p2[0] += $this->pos_x;
            $p2[1] += $this->pos_y;
        }
        // calculate control point
        $prev = strtolower($this->previous_command);
        if ($prev === 'q' || $prev === 't') {
            $p1 = self::reflect_point($this->quadratic_old, $p1);
        }
        $this->quadratic_old = $p1;
        [$this->pos_x, $this->pos_y] = $p2;
        $this->transform->map($p1[0], $p1[1]);
        $this->transform->map($p2[0], $p2[1]);
        $approx = self::$bezier->quadratic($this->builder->get_position(), $p1, $p2);
        $this->builder->add_points($approx);
    }
    /**
     * Approximation function for ArcTo (A and a).
     *
     * @param string  $id   The actual id used (for abs. vs. rel.).
     * @param float[] $args The arguments provided to the command.
     *
     *
     * @SuppressWarnings("unused")
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private function arc_to(string $id, array $args): void
    {
        // NOTE: Unfortunately, it seems that arc segments are not invariant under affine transforms, as opposed to
        //       Bézier curves. Currently, our best strategy is to approximate the curve with path coordinates and
        //       transform the resulting points. This is somewhat improved by guessing a "scale factor" to increase or
        //       decrease the number of approximated points.
        // start point, end point
        $p0 = [$this->pos_x, $this->pos_y];
        $p1 = [$args[5], $args[6]];
        // radiuses, rotation
        $rx = $args[0];
        $ry = $args[1];
        $xa = deg2rad($args[2]);
        // large arc flag, sweep flag
        $fa = (bool) $args[3];
        $fs = (bool) $args[4];
        if ($id === 'a') {
            $p1[0] += $this->pos_x;
            $p1[1] += $this->pos_y;
        }
        [$this->pos_x, $this->pos_y] = $p1;
        // guess a scale factor
        $scaled_rx = $rx;
        $scaled_ry = $ry;
        $this->transform->resize($scaled_rx, $scaled_ry);
        $scale = $rx == 0 || $ry == 0 ? 1.0 : hypot($scaled_rx / $rx, $scaled_ry / $ry);
        $approx = self::$arc->approximate($p0, $p1, $fa, $fs, $rx, $ry, $xa, $scale);
        foreach ($approx as &$point) {
            $this->transform->map($point[0], $point[1]);
        }
        $this->builder->add_points($approx);
    }
    /**
     * Approximation function for ClosePath (Z and z).
     *
     *
     *
     * @SuppressWarnings("unused")
     * @noinspection PhpUnusedPrivateMethodInspection
     */
    private function close_path(): void
    {
        $first = $this->builder->get_first_point();
        $this->builder->add_point($first[0], $first[1]);
        $this->pos_x = $this->first_x;
        $this->pos_y = $this->first_y;
        // The subpath is now complete and any following command should start a new one.
        // Also, since ClosePath can be immediately followed by a command such as LineTo,
        // we append ClosePath's position as a point to the new subpath.
        // If the following command is, in fact, a MoveTo, this will simply be overridden.
        $this->new_subpath();
    }
}