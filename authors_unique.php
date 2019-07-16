<?php

require_once 'litres_config.php';

header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

set_time_limit ( 75000 );

function output($str) {
    echo $str;
    ob_end_flush();
    ob_flush();
    flush();
    ob_start();
}

try {
    $dbh = new PDO('mysql:host='. $hostname .';dbname='. $database, $username, $password);
} catch(PDOException $e) {
    echo '<h1>An error has ocurred.</h1><pre>', $e->getMessage() ,'</pre>';
    die();
}

$sth = $dbh->query('SELECT author FROM authors_unique');
$sth->setFetchMode(PDO::FETCH_ASSOC);
$result = $sth->fetchAll();

foreach ($result as $i => $row)
{
    $sth2 = $dbh->query('SELECT COUNT(*) as books_counter FROM books_public_domain WHERE author = "' . $row['author'] . '"');
    $sth2->setFetchMode(PDO::FETCH_ASSOC);

    $result2 = $sth2->fetchAll();
    echo '<h1>';
    output("(" . $i . ") " . $row['author'] . " : " . $result2[0]['books_counter']);
    echo '</h1>';

    $sql_update = "UPDATE authors_unique SET books_counter = " . $result2[0]['books_counter'] . " WHERE author = '" . $row['author'] . "'";
    var_dump($sql_update);

    $sth = $dbh->prepare($sql_update);
    try {
        $sth->execute();
    } catch(Exception $e) {
        echo '<h1>An error has ocurred.</h1><pre>', $e->getMessage() ,'</pre>';
    }
}

?>