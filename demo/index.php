<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="../docs/css/bootstrap.min.css">

<!-- Optional theme -->
<link rel="stylesheet" href="../docs/css/bootstrap-theme.min.css">

<!-- Latest compiled and minified JavaScript -->
<script src="../docs/js/jquery.min.js"></script>
<script src="../docs/js/bootstrap.min.js"></script>
<script src="../docs/js/main.js"></script>

<?php

/** @var \Doctrine\ORM\EntityManager $entityManager */
$entityManager = require_once __DIR__ . '/bootstrap.php';

require_once '../tests/Entity/User.php';
require_once '../tests/Entity/Groups.php';

\Tracy\Debugger::enable(\Tracy\Debugger::DEVELOPMENT, __DIR__ . '/log');

?>

<hr>

<div class="container">
    <h2>Basic functionality</h2>

    <hr>

    <?php
/*
    $connection = new \Nette\Database\Connection(
        'mysql:host=127.0.0.1;dbname=voip', 'root', 'root'
    );
    $cacheMemoryStorage = new \Nette\Caching\Storages\MemoryStorage;
    $structure = new \Nette\Database\Structure($connection, $cacheMemoryStorage);
    $conventions = new \Nette\Database\Conventions\DiscoveredConventions($structure);
    $context = new \Nette\Database\Context($connection, $structure, $conventions, $cacheMemoryStorage);

    $selection = $context->table('missed_calls')
            ->select('missed_calls.*')
            //->select('call_category.identifier')
            ->where('saved IS NOT NULL');

    $source = new \Mesour\Sources\NetteDbSource($selection, $context);

    $source->setRelated('call_categories', 'call_category', 'identifier');

    dump($source->related('call_categories')->fetchAll());
    die;
*/
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

    $source = $table->getSource();

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