<?php
// Clear cookies
setcookie('access', '', time() - 3600, '/');
setcookie('refresh', '', time() - 3600, '/');

// Destroy session
session_destroy();

redirect('/');
