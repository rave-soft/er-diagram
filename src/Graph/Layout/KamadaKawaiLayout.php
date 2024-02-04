<?php

declare(strict_types=1);

namespace RaveSoft\ErDiagram\Graph\Layout;

use ArrayObject;
use RaveSoft\ErDiagram\Graph\Graph;
use RaveSoft\ErDiagram\Graph\Node;
use RaveSoft\ErDiagram\Graph\Size;

/**
 * @skiptests
 */
class KamadaKawaiLayout implements GraphLayoutInterface
{
    private float $epsilon = 0.2;
    private int $maxIterations = 500; // 2000;

    private float $idealLength; // the ideal length of an edge
    private int $arbitraryConstNumber = 1; // arbitrary const number

    private bool $adjustForGravity = true;
    private bool $exchangeVertices = true;

    /**
     * The diameter of the visible graph. In other words, the maximum over all pairs
     * of vertices of the length of the shortest path between a and bf the visible graph.
     */
    private float $diameter = 5.0; // 5.0;

    /**
     * A multiplicative factor which partly specifies the "preferred" length of an edge (L).
     */
    private float $lengthFactor = 1.2;

    /**
     * A multiplicative factor which specifies the fraction of the graph's diameter to be
     * used as the inter-vertex distance between disconnected vertices.
     */
    private float $disconnectedMultiplier = 0.7; // 0.5;
    private float $disconnectedDistance;

    public function __construct()
    {
        $this->disconnectedDistance = $this->diameter * $this->disconnectedMultiplier;
    }

    public function doLayout(Graph $graph): Size
    {
        $square = (int) (sqrt($graph->getNodesCount()) * 900);
        $size = new Size($square, $square);
        $this->initCoordinates($graph, $size);
        $distanceMatrix = $this->initializeDistanceMatrix($graph, $size);
        $currentIteration = 0;
        while ($currentIteration < $this->maxIterations) {
            $this->step($graph, $size, $distanceMatrix);
            ++$currentIteration;
        }

        return new Size($size->width + 900, $size->height + 900);
    }

    private function initializeDistanceMatrix(Graph $graph, Size $size): ArrayObject
    {
        $result = new ArrayObject();
        $l0 = min($size->height, $size->width);
        $this->idealLength = ($l0 / $this->diameter) * $this->lengthFactor;
        // L = 0.75 * sqrt(height * width / n);
        $nodes = array_values($graph->getNodes());
        $countNodes = \count($nodes);
        for ($i = 0; $i < $countNodes - 1; ++$i) {
            for ($j = $i + 1; $j < $countNodes; ++$j) {
                $nodeFrom = $nodes[$i];
                $nodeTo = $nodes[$j];
                $distance1 = $this->getDistance($nodeFrom, $nodeTo, $graph);
                $distance2 = $this->getDistance($nodeTo, $nodeFrom, $graph);
                $result[$nodeFrom->id][$nodeTo->id] = $result[$nodeTo->id][$nodeFrom->id] = min($distance1, $distance2);
            }
        }

        return $result;
    }

    /**
     * init node's xydate.
     */
    private function initCoordinates(Graph $graph, Size $size): void
    {
        foreach ($graph->getNodes() as $node) {
            $node->x = random_int(10, $size->width - 1);
            $node->y = random_int(10, $size->height - 1);
        }
    }

    private function getDistance(Node $from, Node $to, Graph $graph): float
    {
        return $graph->connected($from, $to) ? 1 : $this->disconnectedDistance;
    }

    private function step(Graph $graph, Size $size, ArrayObject $distanceMatrix): void
    {
        $this->calcEnergy($graph, $distanceMatrix);

        $maxDeltaM = 0;
        $pm = null;            // the node having max deltaM
        foreach ($graph->getNodes() as $node) {
            $deltam = $this->calcDeltaM($node, $distanceMatrix);

            if ($maxDeltaM < $deltam) {
                $maxDeltaM = $deltam;
                $pm = $node;
            }
        }
        if (null === $pm) {
            return;
        }

        for ($i = 0; $i < 100; ++$i) {
            $dxy = $this->calcDeltaXY($pm, $distanceMatrix);
            $pm->x += (int) $dxy[0];
            $pm->y += (int) $dxy[1];

            $deltam = $this->calcDeltaM($pm, $distanceMatrix);
            if ($deltam < $this->epsilon) {
                break;
            }
        }

        if ($this->adjustForGravity) {
            $this->adjustForGravity($graph, $size);
        }

        if ($this->exchangeVertices && $maxDeltaM < $this->epsilon) {
            $energy = $this->calcEnergy($graph, $distanceMatrix);
            $nodes = array_values($graph->getNodes());
            $nodesCount = $graph->getNodesCount();
            for ($i = 0; $i < $nodesCount - 1; ++$i) {
                for ($j = $i + 1; $j < $nodesCount; ++$j) {
                    $iNode = $nodes[$i];
                    $jNode = $nodes[$j];
                    $xenergy = $this->calcEnergyIfExchanged($iNode, $jNode, $distanceMatrix);
                    if ($energy > $xenergy) {
                        $sx = $iNode->x;
                        $sy = $iNode->y;
                        $iNode->x = $jNode->x;
                        $iNode->y = $jNode->y;
                        $jNode->x = $sx;
                        $jNode->y = $sy;

                        return;
                    }
                }
            }
        }
    }

    /**
     * Shift all vertices so that the center of gravity is located at
     * the center of the screen.
     */
    private function adjustForGravity(Graph $graph, Size $size): void
    {
        $height = $size->height;
        $width = $size->width;
        $gx = 0;
        $gy = 0;
        $cnt = $graph->getNodesCount();
        foreach ($graph->getNodes() as $node) {
            $gx += $node->x;
            $gy += $node->y;
        }
        $gx /= $cnt;
        $gy /= $cnt;
        $diffx = $width / 2 - $gx;
        $diffy = $height / 2 - $gy;
        foreach ($graph->getNodes() as $node) {
            $node->x += (int) $diffx;
            $node->y += (int) $diffy;
        }
    }

    /**
     * Determines a step to new position of the vertex m.
     * @return array<int, float>
     */
    private function calcDeltaXY(Node $node, ArrayObject $distanceMatrix): array
    {
        $dEDxm = 0;
        $dEDym = 0;
        $d2ED2xm = 0;
        $d2EDxmdym = 0;
        $d2ED2ym = 0;

        foreach ($node->graph->getNodes() as $itemNode) {
            if ($itemNode !== $node) {
                $dist = $distanceMatrix[$node->id][$itemNode->id];
                $lMi = $this->idealLength * $dist;
                $kMi = $this->arbitraryConstNumber / ($dist * $dist);
                $dx = $node->x - $itemNode->x;
                $dy = $node->y - $itemNode->y;
                $d = sqrt($dx * $dx + $dy * $dy);
                $ddd = $d * $d * $d;

                $dEDxm += $kMi * (1 - $lMi / $d) * $dx;
                $dEDym += $kMi * (1 - $lMi / $d) * $dy;
                $d2ED2xm += $kMi * (1 - $lMi * $dy * $dy / $ddd);
                $d2EDxmdym += $kMi * $lMi * $dx * $dy / $ddd;
                $d2ED2ym += $kMi * (1 - $lMi * $dx * $dx / $ddd);
            }
        }

        $denomi = $d2ED2xm * $d2ED2ym - $d2EDxmdym * $d2EDxmdym;
        $deltaX = ($d2EDxmdym * $dEDym - $d2ED2ym * $dEDxm) / $denomi;
        $deltaY = ($d2EDxmdym * $dEDxm - $d2ED2xm * $dEDym) / $denomi;

        return [$deltaX, $deltaY];
    }

    /**
     * Calculates the gradient of energy function at the vertex m.
     */
    private function calcDeltaM(Node $node, ArrayObject $distanceMatrix): float
    {
        $dEdxm = 0;
        $dEdym = 0;
        foreach ($node->graph->getNodes() as $itemNode) {
            if ($itemNode !== $node) {
                $dist = $distanceMatrix[$node->id][$itemNode->id];
                $lMi = $this->idealLength * $dist;
                $kMi = $this->arbitraryConstNumber / ($dist * $dist);

                $dx = $node->x - $itemNode->x;
                $dy = $node->y - $itemNode->y;
                $d = sqrt($dx * $dx + $dy * $dy);

                $common = $kMi * (1 - $lMi / $d);
                $dEdxm += $common * $dx;
                $dEdym += $common * $dy;
            }
        }

        return sqrt($dEdxm * $dEdxm + $dEdym * $dEdym);
    }

    /**
     * Calculates the energy function E.
     */
    private function calcEnergy(Graph $graph, ArrayObject $distanceMatrix): float
    {
        $energy = 0;
        $nodes = array_values($graph->getNodes());
        $countNodes = \count($nodes);
        for ($i = 0; $i < $countNodes - 1; ++$i) {
            for ($j = $i + 1; $j < $countNodes; ++$j) {
                $nodeFrom = $nodes[$i];
                $nodeTo = $nodes[$j];
                $dist = $distanceMatrix[$nodeFrom->id][$nodeTo->id];
                $lIj = $this->idealLength * $dist;
                $kIj = $this->arbitraryConstNumber / ($dist * $dist);
                $dx = $nodeFrom->x - $nodeTo->x;
                $dy = $nodeFrom->y - $nodeTo->y;
                $d = sqrt($dx * $dx + $dy * $dy);

                $energy += $kIj / 2 * ($dx * $dx + $dy * $dy + $lIj * $lIj - 2 * $lIj * $d);
            }
        }

        return $energy;
    }

    /**
     * Calculates the energy function E as if positions of the
     * specified vertices are exchanged.
     */
    private function calcEnergyIfExchanged(Node $p, Node $q, ArrayObject $distanceMatrix): float
    {
        $nodes = array_values($p->graph->getNodes());
        $countNodes = \count($nodes);
        $energy = 0;
        for ($i = 0; $i < $countNodes - 1; ++$i) {
            for ($j = $i + 1; $j < $countNodes; ++$j) {
                $ii = $i;
                $jj = $j;
                $iNode = $nodes[$i];
                $jNode = $nodes[$j];
                $iiNode = $nodes[$ii];
                $jjNode = $nodes[$jj];
                if ($iNode->id === $p->id) {
                    $iiNode = $q;
                }
                if ($jNode->id === $q->id) {
                    $jjNode = $p;
                }
                $dist = $distanceMatrix[$iNode->id][$jNode->id];
                $lIj = $this->idealLength * $dist;
                $kIj = $this->arbitraryConstNumber / ($dist * $dist);
                $dx = $iiNode->x - $jjNode->x;
                $dy = $iiNode->y - $jjNode->y;
                $d = sqrt($dx * $dx + $dy * $dy);

                $energy += $kIj / 2 * ($dx * $dx + $dy * $dy + $lIj * $lIj - 2 * $lIj * $d);
            }
        }

        return $energy;
    }
}
