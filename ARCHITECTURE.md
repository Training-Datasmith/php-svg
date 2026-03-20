# Architecture: php-svg

## Purpose

A PHP library for reading, creating, and rasterizing SVG (Scalable Vector Graphics) images to GD images. Enables server-side SVG rendering without external dependencies like Inkscape or ImageMagick.

## Directory Structure

```
src/
  SVG.php                        — Entry point: load from string/file, get document, rasterize
  Nodes/
    SVG_Node.php                 — Base class for all SVG DOM nodes
    SVG_Node_Container.php       — Base for nodes that contain children (groups, defs, etc.)
    SVG_Generic_Node_Type.php    — Fallback for unknown SVG elements
    Shapes/                      — SVG shape nodes: Circle, Ellipse, Line, Path, Polygon, Rect, Polyline
    Texts/                       — SVG text nodes: Text, TSpan, TextPath, Desc, Title
    Structures/                  — SVG structural nodes: Group, Defs, ClipPath, Mask, Symbol, Use, Pattern, etc.
    Embedded/                    — Image and ForeignObject nodes
    Filters/                     — SVG filter primitives (blur, blend, color matrix, etc.)
    Presentation/                — Gradient, animation, and view nodes
    C_Data_Container.php         — Nodes containing CDATA (scripts, styles)
  Reading/
    SVG_Reader.php               — SAX-style parser: converts SVG XML to the node object tree
    Node_Registry.php            — Maps SVG element names to PHP node classes
    Attribute_Registry.php       — Maps attribute names to converter functions
    Attribute_Converter.php      — Converts SVG attribute strings to typed PHP values
    Length_Attribute_Converter.php — Converts CSS length strings (px, em, %, etc.) to pixels
  Rasterization/
    SVG_Rasterizer.php           — Orchestrates rasterization of the node tree to a GD image
    Renderers/                   — One renderer per shape/node type
    Path/
      Path_Parser.php            — Parses SVG path `d` attribute commands (M, L, C, A, Z, etc.)
      Path_Approximator.php      — Converts path commands to line segment approximations
      Arc_Approximator.php       — Approximates arc segments with Bezier curves
      Bezier_Approximator.php    — Approximates Bezier curves with polylines
      Polygon_Builder.php        — Builds a polygon from approximated path segments
    Transform/
      Transform.php              — Applies SVG transform matrix operations
      Transform_Parser.php       — Parses SVG transform attribute strings
  Utilities/
    Colors/
      Color.php                  — Parses and converts SVG color values (hex, rgb, hsl, named)
      Color_Lookup.php           — SVG named color lookup table
    SVG_Style_Parser.php         — Parses inline `style` attribute CSS
  Fonts/
    Font_Registry.php            — Manages font file associations for text rendering
    True_Type_Font_File.php      — Reads TrueType font metrics via GD
  Shims/Str.php                  — PHP version compatibility helpers
```

## Key Design Decisions

- **DOM tree model**: SVG XML is parsed into a tree of `SVGNode` objects; this tree can be traversed and modified before rasterization
- **Registry-driven parsing**: `NodeRegistry` and `AttributeRegistry` map SVG element/attribute names to PHP classes/converters, making it easy to add support for new SVG features
- **Path approximation pipeline**: Vector paths are converted to polygons via a multi-stage approximation (Bezier → line segments → polygon); GD then fills/strokes the polygon
- **GD-only rasterization**: Output is a PHP GD resource/image; no external binaries are required

## Extension Points

- Register custom node classes in `Node_Registry` to handle non-standard SVG elements
- Register custom attribute converters in `Attribute_Registry`
- Extend `Renderer` to add or override shape rendering

## Dependency Flow

```
SVG::fromFile($path) or SVG::fromString($svg)
  → SVGReader (SAX parse → node tree)
  → SVG_Rasterizer::render($width, $height)
      → per-node Renderer (Circle, Path, Text, etc.)
      → GD imagecreatetruecolor, imagepolygon, etc.
  → GD resource (save as PNG/JPEG via imagepng/imagejpeg)
```
