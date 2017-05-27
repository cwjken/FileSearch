<?php
require('./library/FileSearch.php');
$m = new FileSearch("test.log", 200);
echo $m->search(19);
