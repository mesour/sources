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

    $helperSet = new \Symfony\Component\Console\Helper\HelperSet(array(
        'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($entityManager->getConnection()),
        'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($entityManager)
    ));

    /*$product = new \User();
    $product->setName('test');

    $entityManager->persist($product);
    $entityManager->flush();*/

    $qb = $entityManager->createQueryBuilder();
    $qb
        ->select('u')
        ->from('Mesour\Sources\Tests\Entity\User', 'u')
        ->where('u.name= :name')
        ->setParameter('name', 'john')
        ;

    $source = new \Mesour\Sources\DoctrineSource($qb, [
        'userId' => 'u.userId',
        'groupName' => 'gr.name',
    ]);

    $source->setPrimaryKey('userId');

    /*
    dump($source->setRelated('groups', 'groupId', 'name', 'groupName', 'id'));
    dump($source->where('u.email= :email', ['email' => 'john.doe@test.xx']));
    dump($source->fetch());
    dump($source->fetchPairs('userId', 'groupName'));
    dump($source->fetchAll());
    dump($source->fetchLastRawRows());
    dump($source->count());
    dump($source->getTotalCount());
    $groupsSource = $source->related('groups');
    dump($groupsSource->fetchAll());
    */


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