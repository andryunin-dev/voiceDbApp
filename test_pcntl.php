<?php
    $pid = pcntl_fork();

    switch($pid) {
        case -1:
            print "Could not fork!\n";
            exit;
        case 0:
            print "In child!\n";
            break;
        default:
            print "In parent!\n";
    }
?>