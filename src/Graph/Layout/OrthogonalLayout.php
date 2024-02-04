<?php

declare(strict_types=1);

namespace RaveSoft\ErDiagram\Graph\Layout;

use RaveSoft\ErDiagram\Graph\Graph;
use RaveSoft\ErDiagram\Graph\Node;
use RaveSoft\ErDiagram\Graph\Size;

/**
 * Graph Compact Orthogonal Graph Layout.
 */
class OrthogonalLayout implements GraphLayoutInterface
{
    public function doLayout(Graph $graph): Size
    {
        $nodeSpacing = 400;
        $levelSpacing = 500;
        $nodes = $graph->getNodes();
        $nodesIndexed = [];
        foreach ($nodes as $node) {
            $nodesIndexed[$node->id] = $node;
        }
        // Sort nodes by level
        usort($nodes, static fn ($a, $b) => $a->y - $b->y);

        // Assign initial x-coordinates to nodes
        foreach ($nodes as $i => $node) {
            $node->x = $i * $nodeSpacing;
        }

        // Compact nodes horizontally within each level
        foreach (array_unique(array_map(fn (Node $node) => $node->y, $nodes)) as $level) {
            $levelNodes = array_filter($nodes, static fn (Node $node) => $node->y === $level);

            $maxX = max(array_map(fn (Node $node) => $node->x, $levelNodes));
            $minX = min(array_map(fn (Node $node) => $node->x, $levelNodes));
            $levelWidth = $maxX - $minX;
            $extraSpace = max(0, $levelWidth - (\count($levelNodes) - 1) * $nodeSpacing) / (\count($levelNodes) - 1);

            foreach ($levelNodes as $i => $node) {
                $min = min(array_map(fn (Node $node) => $node->x, $levelNodes));
                $node->x = (int) ($min + $i * ($nodeSpacing + $extraSpace));
            }
        }

        // Adjust y-coordinates based on incoming and outgoing edges
        foreach ($nodes as $node) {
            $incomingEdges = $graph->getIncomingEdges($node);
            $outgoingEdges = $graph->getOutgoingEdges($node);

            if (\count($incomingEdges) > 0) {
                $maxY = max(array_map(
                    static fn ($edge) => $nodesIndexed[$edge->source]->y,
                    $incomingEdges
                )) + $levelSpacing;

                $node->y = max($node->y, $maxY);
            }

            if (\count($outgoingEdges) > 0) {
                $minY = min(array_map(
                    static fn ($edge) => $nodesIndexed[$edge->target]->y,
                    $outgoingEdges
                )) - $levelSpacing;

                $node->y = min($node->y, $minY);
            }
        }

        return new Size(1000, 1000);
    }
}
