#!/usr/local/bin/php -q
<?php
passthru(dirname(__FILE__) . '/indexing.php');
passthru(dirname(__FILE__) . '/ranking.php');
passthru(dirname(__FILE__) . '/suggestion-cache.php');
passthru(dirname(__FILE__) . '/bestsellers.php');
