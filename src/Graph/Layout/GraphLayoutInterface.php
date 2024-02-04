<?php

declare(strict_types=1);

namespace RaveSoft\ErDiagram\Graph\Layout;

use RaveSoft\ErDiagram\Graph\Graph;
use RaveSoft\ErDiagram\Graph\Size;

interface GraphLayoutInterface
{
    public function doLayout(Graph $graph): Size;
}
