<?php 

require_once 'lib/init.php';
require_once 'libmaster/init_master.php';

$model = Model::factory('dataset');

$model->set('title', 'foof');
$model->set('description', 'ff0ooooof');
$model->set('annotation_file', 'gencode 3b');
$model->set('variant_type', 'svMapper');
$model->set('raw_filename', 'foof.vcf');
$model->set('status', 0);

$model->save();

$id = $model->get('id');

echo 'id: ' . $id . "\n";

unset($model);

$model = Model::factory('dataset', $id);

echo "<pre>\n";
var_dump($model);
echo "</pre>\n";


?>