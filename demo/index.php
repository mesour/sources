<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="../docs/css/bootstrap.min.css">

<!-- Optional theme -->
<link rel="stylesheet" href="../docs/css/bootstrap-theme.min.css">

<!-- Latest compiled and minified JavaScript -->
<script src="../docs/js/jquery.min.js"></script>
<script src="../docs/js/bootstrap.min.js"></script>
<script src="../docs/js/main.js"></script>

<?php

define('SRC_DIR', __DIR__ . '/../src/');

require_once __DIR__ . '/../vendor/autoload.php';

\Tracy\Debugger::enable(\Tracy\Debugger::DEVELOPMENT, __DIR__ . '/log');

require_once SRC_DIR . 'Table/BaseTable.php';
require_once SRC_DIR . 'Table.php';

require_once SRC_DIR . 'Table/ITable.php';
require_once SRC_DIR . 'Table/Source/ISource.php';
require_once SRC_DIR . 'Table/Source/ArraySource.php';
require_once SRC_DIR . 'Table/Render/Attributes.php';
require_once SRC_DIR . 'Table/Render/Body.php';
require_once SRC_DIR . 'Table/Render/Cell.php';
require_once SRC_DIR . 'Table/Render/Header.php';
require_once SRC_DIR . 'Table/Render/HeaderCell.php';
require_once SRC_DIR . 'Table/Render/IColumn.php';
require_once SRC_DIR . 'Table/BaseColumn.php';
require_once SRC_DIR . 'Table/Column.php';
require_once SRC_DIR . 'Table/Render/IRendererFactory.php';
require_once SRC_DIR . 'Table/Render/Renderer.php';
require_once SRC_DIR . 'Table/Render/Row.php';
require_once SRC_DIR . 'Table/Render/Table/Row.php';
require_once SRC_DIR . 'Table/Render/Table/Renderer.php';
require_once SRC_DIR . 'Table/Render/Table/Body.php';
require_once SRC_DIR . 'Table/Render/Table/Cell.php';
require_once SRC_DIR . 'Table/Render/Table/Header.php';
require_once SRC_DIR . 'Table/Render/Table/HeaderCell.php';
require_once SRC_DIR . 'Table/Render/Table/RendererFactory.php';

\Mesour\UI\Control::$default_link = new \Mesour\Components\Link\Link();

?>

<hr>

<div class="container">
    <h2>Basic functionality</h2>

    <hr>

    <?php

    $table = new \Mesour\UI\Table();

    $data = array(
        array(
            'method' => 'setName',
            'params' => '$name',
            'returns' => 'Mesour\Table\Column',
            'description' => 'Set column name.',
        ),
        array(
            'method' => 'setHeader',
            'params' => '$header',
            'returns' => 'Mesour\Table\Column',
            'description' => 'Set header text.',
        ),
        array(
            'method' => 'setCallback',
            'params' => '$callback',
            'returns' => 'Mesour\Table\Column',
            'description' => 'Set render callback.',
        )
    );

    $table->setSource($data);

    $table->setAttribute('class', 'table table-striped table-hover');

    $table->addColumn('method', 'Method')
        ->setCallback(function($data) {
            return \Mesour\Components\Html::el('b')->setText($data['method']);
        });

    $table->addColumn('params', 'Parameters');

    $table->addColumn('returns', 'Returns');

    $table->addColumn('description', 'Description');

    $table->render();

    ?>
</div>

<hr>

<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>