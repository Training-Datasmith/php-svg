<?php

declare(strict_types=1);

namespace SVG\Nodes\Presentation;

use SVG\Nodes\SVGNodeContainer;
use SVG\Rasterization\SVGRasterizer;

/**
 * Represents the SVG tag 'radialGradient'.
 */
class SVGRadialGradient extends SVGNodeContainer
{
    public const TAG_NAME = 'radialGradient';

    /**
     * @inheritdoc
     */
    public function rasterize(SVGRasterizer $rasterizer): void
    {
        // Nothing to rasterize.
    }
}
