<?php

if (!defined('ABSPATH')) { exit; }

// need to disable comment flood control so we can test more consistently
add_filter('comment_flood_filter', 'bwp_comment_flood_filter', 11);
function bwp_comment_flood_filter()
{
    return false;
}
