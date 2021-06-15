<?php

// Hide user deprecated errors
define('DEFAULT_ERROR_REPORTING', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED & ~E_USER_DEPRECATED);

require 'concrete/dispatcher.php';
