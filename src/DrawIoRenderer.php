<?php

declare(strict_types=1);

namespace RaveSoft\ErDiagram;

use RaveSoft\ErDiagram\Graph\Graph;
use RaveSoft\ErDiagram\Graph\Node;
use RaveSoft\ErDiagram\Graph\Size;
use RaveSoft\ErDiagram\Schema\Entity;
use RaveSoft\ErDiagram\Schema\FilterLevelEnum;

class DrawIoRenderer
{
    public function __construct(
        private readonly int $rowWidth = 300,
        private readonly int $rowHeight = 30,
        private readonly int $rowBoxWidth = 30,
    ) {
    }

    /**
     * @param Entity[] $entities
     */
    public function renderMxFile(array $entities, Graph $graph, Size $size): string
    {
        $date = (new \DateTime())->format(DATE_RFC3339_EXTENDED);
        $etag = base_convert(random_bytes(10), 16, 32);
        $diagramId = base_convert(random_bytes(10), 16, 32);

        $content = '';
        foreach ($entities as $entity) {
            $content .= $this->renderEntity($entity, $graph->getNode($entity->uniqId));
        }
        $content .= $this->renderEdges($entities);

        $dx = (int) ($size->width / 2);
        $dy = (int) ($size->height / 2);

        return <<<XML
            <mxfile host="app.diagrams.net" modified="$date" agent="Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.0.0 Safari/537.36" etag="$etag" version="22.0.8" type="google">
              <diagram id="$diagramId" name="Page-1">
                <mxGraphModel  dx="$dx" dy="$dy" grid="1" page="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" pageScale="1" pageWidth="$size->width" pageHeight="$size->height" math="0" shadow="0" extFonts="Permanent Marker^https://fonts.googleapis.com/css?family=Permanent+Marker">
                  <root>
                    <mxCell id="0" />
                    <mxCell id="1" parent="0" />
                    $content
                  </root>
                </mxGraphModel>
              </diagram>
            </mxfile>
            XML;
    }

    private function renderEntity(Entity $entity, Node $node): string
    {
        $entityHeight = $this->rowHeight * (\count($entity->primaryKeys) + \count($entity->relations) + 1);
        // phpcs:ignore Generic.Files.LineLength.TooLong
        $style = match ($entity->filterLevel) {
            FilterLevelEnum::FirstLevel => "shape=table;startSize=$this->rowHeight;container=1;collapsible=1;childLayout=tableLayout;fixedRows=1;rowLines=0;fontStyle=1;align=center;resizeLast=1;fillColor=#dae8fc;strokeColor=#6c8ebf;",
            FilterLevelEnum::SecondLevel => "shape=table;startSize=$this->rowHeight;container=1;collapsible=1;childLayout=tableLayout;fixedRows=1;rowLines=0;fontStyle=1;align=center;resizeLast=1;fillColor=#fff2cc;strokeColor=#d6b656;",
            FilterLevelEnum::Regular => "shape=table;startSize=$this->rowHeight;container=1;collapsible=1;childLayout=tableLayout;fixedRows=1;rowLines=0;fontStyle=1;align=center;resizeLast=1;",
        };
        return <<<MX
            <mxCell id="$entity->uniqId" value="$entity->shortName" style="$style" vertex="1" parent="1">
              <mxGeometry x="$node->x" y="$node->y" width="$this->rowWidth" height="$entityHeight" as="geometry" />
            </mxCell>
            {$this->renderPrimaryKeys($entity)}
            {$this->renderAssociations($entity)}
            MX;
    }

    private function renderPrimaryKeys(Entity $entity): string
    {
        $result = '';
        $y = 0;
        $width = $this->rowWidth - $this->rowBoxWidth;
        foreach ($entity->primaryKeys as $primaryKey) {
            $pkCellId = uniqid('C-', false);
            $nameCellId = uniqid('C-', false);
            $y += $this->rowHeight;
            // phpcs:ignore Generic.Files.LineLength.TooLong
            $result .= <<<MX
                <mxCell id="$primaryKey->uniqId" value="" style="shape=partialRectangle;collapsible=0;dropTarget=0;pointerEvents=0;fillColor=none;points=[[0,0.5],[1,0.5]];portConstraint=eastwest;top=0;left=0;right=0;bottom=1;" vertex="1" parent="$entity->uniqId">
                  <mxGeometry y="$y" width="$this->rowWidth" height="$this->rowHeight" as="geometry" />
                </mxCell>
                <mxCell id="$pkCellId" value="PK" style="shape=partialRectangle;overflow=hidden;connectable=0;fillColor=none;top=0;left=0;bottom=0;right=0;fontStyle=1;" vertex="1" parent="$primaryKey->uniqId">
                  <mxGeometry width="$this->rowBoxWidth" height="$this->rowHeight" as="geometry" />
                </mxCell>
                <mxCell id="$nameCellId" value="$primaryKey->name" style="shape=partialRectangle;overflow=hidden;connectable=0;fillColor=none;top=0;left=0;bottom=0;right=0;align=left;spacingLeft=6;fontStyle=5;" vertex="1" parent="$primaryKey->uniqId">
                  <mxGeometry x="$this->rowBoxWidth" width="$width" height="$this->rowHeight" as="geometry" />
                </mxCell>
                MX;
        }

        return $result;
    }

    private function renderAssociations(Entity $entity): string
    {
        $result = '';
        $y = $this->rowHeight * \count($entity->primaryKeys);
        $width = $this->rowWidth - $this->rowBoxWidth;
        foreach ($entity->relations as $relation) {
            $fkCellId = uniqid('C-', false);
            $nameCellId = uniqid('C-', false);
            $y += $this->rowHeight;
            $result .= <<<MX
                <mxCell id="$relation->uniqId" value="" style="shape=partialRectangle;collapsible=0;dropTarget=0;pointerEvents=0;fillColor=none;points=[[0,0.5],[1,0.5]];portConstraint=eastwest;top=0;left=0;right=0;bottom=1;" vertex="1" parent="$entity->uniqId">
                  <mxGeometry y="$y" width="$this->rowWidth" height="$this->rowHeight" as="geometry" />
                </mxCell>
                <mxCell id="$fkCellId" value="FK" style="shape=partialRectangle;overflow=hidden;connectable=0;fillColor=none;top=0;left=0;bottom=0;right=0;" vertex="1" parent="$relation->uniqId">
                  <mxGeometry width="$this->rowBoxWidth" height="$this->rowHeight" as="geometry" />
                </mxCell>
                <mxCell id="$nameCellId" value="{$relation->target?->shortName}" style="shape=partialRectangle;overflow=hidden;connectable=0;fillColor=none;top=0;left=0;bottom=0;right=0;align=left;spacingLeft=6;" vertex="1" parent="$relation->uniqId">
                  <mxGeometry x="$this->rowBoxWidth" width="$width" height="$this->rowHeight" as="geometry" />
                </mxCell>
                MX;
        }

        return $result;
    }

    /**
     * @param Entity[] $entities
     */
    private function renderEdges(array $entities): string
    {
        $result = '';
        foreach ($entities as $entity) {
            foreach ($entity->relations as $relation) {
                if ($relation->target === null) {
                    continue;
                }
                $relId = uniqid('REL-', false);
                $boldLevels = [FilterLevelEnum::FirstLevel, FilterLevelEnum::SecondLevel];
                if ($entity->filterLevel === FilterLevelEnum::FirstLevel && $relation->target->filterLevel === FilterLevelEnum::FirstLevel) {
                    $style = "edgeStyle=orthogonalEdgeStyle;orthogonalLoop=1;jettySize=auto;html=1;exitX=1;exitY=0.5;exitDx=0;exitDy=0;entryX=0;entryY=0.5;entryDx=0;entryDy=0;curved=1;dashed=1;";
                } elseif (\in_array($entity->filterLevel, $boldLevels, true) && \in_array($relation->target->filterLevel, $boldLevels)) {
                    $style = 'edgeStyle=orthogonalEdgeStyle;orthogonalLoop=1;jettySize=auto;html=1;exitX=0;exitY=0.5;exitDx=0;exitDy=0;entryX=0;entryY=0.5;entryDx=0;entryDy=0;curved=1;strokeWidth=2;';
                } else {
                    $style = "edgeStyle=orthogonalEdgeStyle;orthogonalLoop=1;jettySize=auto;html=1;exitX=1;exitY=0.5;exitDx=0;exitDy=0;curved=1;";
                }
                $result .= <<<MX
                    <mxCell id="$relId" style="$style" edge="1" parent="1" source="$relation->uniqId" target="{$relation->target->primaryKeys[0]?->uniqId}">
                      <mxGeometry relative="1" as="geometry" />
                    </mxCell>
                MX;
            }
        }

        return $result;
    }
}
