<?php

declare (strict_types=1);
namespace SVG\Reading;

use SVG\Nodes\Embedded\Svg_Foreign_Object;
use SVG\Nodes\Embedded\Svg_Image;
use SVG\Nodes\Filters\Svgfe_Blend;
use SVG\Nodes\Filters\Svgfe_Color_Matrix;
use SVG\Nodes\Filters\Svgfe_Component_Transfer;
use SVG\Nodes\Filters\Svgfe_Composite;
use SVG\Nodes\Filters\Svgfe_Convolve_Matrix;
use SVG\Nodes\Filters\Svgfe_Diffuse_Lighting;
use SVG\Nodes\Filters\Svgfe_Displacement_Map;
use SVG\Nodes\Filters\Svgfe_Distant_Light;
use SVG\Nodes\Filters\Svgfe_Drop_Shadow;
use SVG\Nodes\Filters\Svgfe_Flood;
use SVG\Nodes\Filters\Svgfe_Func_A;
use SVG\Nodes\Filters\Svgfe_Func_B;
use SVG\Nodes\Filters\Svgfe_Func_G;
use SVG\Nodes\Filters\Svgfe_Func_R;
use SVG\Nodes\Filters\Svgfe_Gaussian_Blur;
use SVG\Nodes\Filters\Svgfe_Image;
use SVG\Nodes\Filters\Svgfe_Merge;
use SVG\Nodes\Filters\Svgfe_Merge_Node;
use SVG\Nodes\Filters\Svgfe_Morphology;
use SVG\Nodes\Filters\Svgfe_Offset;
use SVG\Nodes\Filters\Svgfe_Point_Light;
use SVG\Nodes\Filters\Svgfe_Specular_Lighting;
use SVG\Nodes\Filters\Svgfe_Spot_Light;
use SVG\Nodes\Filters\Svgfe_Tile;
use SVG\Nodes\Filters\Svgfe_Turbulence;
use SVG\Nodes\Filters\Svg_Filter;
use SVG\Nodes\Presentation\Svg_Animate;
use SVG\Nodes\Presentation\Svg_Animate_Motion;
use SVG\Nodes\Presentation\Svg_Animate_Transform;
use SVG\Nodes\Presentation\Svg_Linear_Gradient;
use SVG\Nodes\Presentation\Svgm_Path;
use SVG\Nodes\Presentation\Svg_Radial_Gradient;
use SVG\Nodes\Presentation\Svg_Set;
use SVG\Nodes\Presentation\Svg_Stop;
use SVG\Nodes\Presentation\Svg_View;
use SVG\Nodes\Shapes\Svg_Circle;
use SVG\Nodes\Shapes\Svg_Ellipse;
use SVG\Nodes\Shapes\Svg_Line;
use SVG\Nodes\Shapes\Svg_Path;
use SVG\Nodes\Shapes\Svg_Polygon;
use SVG\Nodes\Shapes\Svg_Polyline;
use SVG\Nodes\Shapes\Svg_Rect;
use SVG\Nodes\Structures\Svg_Clip_Path;
use SVG\Nodes\Structures\Svg_Defs;
use SVG\Nodes\Structures\Svg_Document_Fragment;
use SVG\Nodes\Structures\Svg_Group;
use SVG\Nodes\Structures\Svg_Link_Group;
use SVG\Nodes\Structures\Svg_Marker;
use SVG\Nodes\Structures\Svg_Mask;
use SVG\Nodes\Structures\Svg_Metadata;
use SVG\Nodes\Structures\Svg_Pattern;
use SVG\Nodes\Structures\Svg_Script;
use SVG\Nodes\Structures\Svg_Style;
use SVG\Nodes\Structures\Svg_Switch;
use SVG\Nodes\Structures\Svg_Symbol;
use SVG\Nodes\Structures\Svg_Use;
use SVG\Nodes\Svg_Generic_Node_Type;
use SVG\Nodes\Svg_Node;
use SVG\Nodes\Texts\Svg_Desc;
use SVG\Nodes\Texts\Svg_Text;
use SVG\Nodes\Texts\Svg_Text_Path;
use SVG\Nodes\Texts\Svg_Title;
use SVG\Nodes\Texts\Svgt_Span;
/**
 * This class contains a list of all known SVG node types, and enables dynamic
 * instantiation of the respective class.
 */
class Node_Registry
{
    /**
     * @var string[] $nodeTypes Map of tag names to fully-qualified class names.
     */
    private static array $node_types = ['foreignObject' => Svg_Foreign_Object::class, 'image' => Svg_Image::class, 'feBlend' => Svgfe_Blend::class, 'feColorMatrix' => Svgfe_Color_Matrix::class, 'feComponentTransfer' => Svgfe_Component_Transfer::class, 'feComposite' => Svgfe_Composite::class, 'feConvolveMatrix' => Svgfe_Convolve_Matrix::class, 'feDiffuseLighting' => Svgfe_Diffuse_Lighting::class, 'feDisplacementMap' => Svgfe_Displacement_Map::class, 'feDistantLight' => Svgfe_Distant_Light::class, 'feDropShadow' => Svgfe_Drop_Shadow::class, 'feFlood' => Svgfe_Flood::class, 'feFuncA' => Svgfe_Func_A::class, 'feFuncB' => Svgfe_Func_B::class, 'feFuncG' => Svgfe_Func_G::class, 'feFuncR' => Svgfe_Func_R::class, 'feGaussianBlur' => Svgfe_Gaussian_Blur::class, 'feImage' => Svgfe_Image::class, 'feMerge' => Svgfe_Merge::class, 'feMergeNode' => Svgfe_Merge_Node::class, 'feMorphology' => Svgfe_Morphology::class, 'feOffset' => Svgfe_Offset::class, 'fePointLight' => Svgfe_Point_Light::class, 'feSpecularLighting' => Svgfe_Specular_Lighting::class, 'feSpotLight' => Svgfe_Spot_Light::class, 'feTile' => Svgfe_Tile::class, 'feTurbulence' => Svgfe_Turbulence::class, 'filter' => Svg_Filter::class, 'animate' => Svg_Animate::class, 'animateMotion' => Svg_Animate_Motion::class, 'animateTransform' => Svg_Animate_Transform::class, 'linearGradient' => Svg_Linear_Gradient::class, 'mpath' => Svgm_Path::class, 'radialGradient' => Svg_Radial_Gradient::class, 'set' => Svg_Set::class, 'stop' => Svg_Stop::class, 'view' => Svg_View::class, 'circle' => Svg_Circle::class, 'ellipse' => Svg_Ellipse::class, 'line' => Svg_Line::class, 'path' => Svg_Path::class, 'polygon' => Svg_Polygon::class, 'polyline' => Svg_Polyline::class, 'rect' => Svg_Rect::class, 'clipPath' => Svg_Clip_Path::class, 'defs' => Svg_Defs::class, 'svg' => Svg_Document_Fragment::class, 'g' => Svg_Group::class, 'a' => Svg_Link_Group::class, 'marker' => Svg_Marker::class, 'mask' => Svg_Mask::class, 'metadata' => Svg_Metadata::class, 'pattern' => Svg_Pattern::class, 'script' => Svg_Script::class, 'style' => Svg_Style::class, 'switch' => Svg_Switch::class, 'symbol' => Svg_Symbol::class, 'use' => Svg_Use::class, 'desc' => Svg_Desc::class, 'text' => Svg_Text::class, 'textPath' => Svg_Text_Path::class, 'title' => Svg_Title::class, 'tspan' => Svgt_Span::class];
    /**
     * Instantiate a node class matching the given type.
     * If no such class exists, a generic one will be used.
     *
     * @param string $type The node tag name ('svg', 'rect', 'title', etc.).
     *
     * @return SVGNode The node that was created.
     */
    public static function create(string $type): Svg_Node
    {
        if (isset(self::$node_types[$type])) {
            $node_class = self::$node_types[$type];
            return new $node_class();
        }
        return new Svg_Generic_Node_Type($type);
    }
}