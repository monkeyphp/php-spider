<?php
//
$server = $_SERVER;
$uri = $server['REQUEST_URI'];

$links = [];

// home
if ($uri === '/') {
    $links = [
        '/about',
        '/contact',
    ];
}

// about
if ($uri === '/about') {
    $links = [
        '/',
        '/contact',
    ];
}

// contact
if ($uri === '/contact') {
    $links = [
        '/',
        '/about',
    ];
}
?>
<html>
    <head>
        <title>Test</title>
    </head>
    <body>
        <ul>
        <?php foreach ($links as $link): ?>
            <li>
                <a href="<?php echo $link; ?>"><?php echo $link; ?></a>
            </li>
        <?php endforeach; ?>
        </ul>
    </body>
</html>
