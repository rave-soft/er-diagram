<?php

declare(strict_types=1);

namespace RaveSoft\ErDiagram\Graph;

/**
 * @skiptests
 */
class Graph
{
    /**
     * @var array<string, Node>
     */
    private array $nodes = [];

    /**
     * @var array<string, Edge>
     */
    private array $edges = [];

    public function addNode(string $id): Node
    {
        return $this->nodes[$id] = new Node($id, $this);
    }

    public function getNode(string $id): Node
    {
        if (!isset($this->nodes[$id])) {
            throw new \InvalidArgumentException(sprintf('Node %s is not found', $id));
        }

        return $this->nodes[$id];
    }

    /**
     * @return array<string, Node>
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    public function getNodesCount(): int
    {
        return \count($this->nodes);
    }

    public function addEdge(string $source, string $target): Edge
    {
        $edge = new Edge($source, $target, $this);
        $this->edges[$edge->id] = $edge;

        return $edge;
    }

    /**
     * @return array<string, Edge>
     */
    public function getEdges(): array
    {
        return $this->edges;
    }

    /**
     * @return array<string, Edge>
     */
    public function getIncomingEdges(Node $node): array
    {
        return array_filter($this->edges, static fn (Edge $edge) => $edge->target === $node->id);
    }

    /**
     * @return array<string, Edge>
     */
    public function getOutgoingEdges(Node $node): array
    {
        return array_filter($this->edges, static fn (Edge $edge) => $edge->source === $node->id);
    }

    public function connected(Node $source, Node $target): bool
    {
        return \count(array_filter(
            $this->edges,
            static fn (Edge $edge) => $edge->source === $source->id && $edge->target === $target->id
        )) > 0;
    }
}
