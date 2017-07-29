<?php
require_once dirname(__FILE__) . '/../videos/configuration.php';
require_once '../objects/Encoder.php';
header('Content-Type: application/json');
$rows = Encoder::getAll();
foreach ($rows as $key=>$value) {
    $f = new Format($rows[$key]['formats_id']);
    $rows[$key]['format']= $f->getName();
}
$total = Encoder::getTotal();

echo '{  "current": '.$_POST['current'].',"rowCount": '.$_POST['rowCount'].', "total": '.$total.', "rows":'. json_encode($rows).'}';