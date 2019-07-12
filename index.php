<?php

// url => https://www.litres.ru/darya-doncova/gaduka-v-sirope/
// price => 109.00
// currencyId => RUR
// categoryId => 38300
// picture => https://cv6.litres.ru/pub/c/cover/118363.jpg
// author => Дарья Донцова
// name => Гадюка в сиропе
// publisher => Эксмо
// series => Евлампия Романова. Следствие ведет дилетант
// year => 2001
// ISBN => 
// description => Везет же мне на приключения! Я – Евлампия Романова, неудавшаяся арфистка, осталась на целый год одна. Все мои близкие уехали на год в США. Чтобы не сойти с ума от безделья, я нанялась экономкой в семью маститого писателя детективных романов Кондрата Разумова. Буквально через неделю его застрелил собственный сынишка, играя с папой в войну. А вскоре арестовали жену Кондрата Лену по подозрению в организации убийства. В вину Лены я не верила. И моя жизнь снова превратилась в самый настоящий детектив… 
// downloadable => true
// age => 0
// param => 
// litres_isbn => 978-5-425-00002-6
// genres_list => 5262,5262
// epub => 
// counter_downloads => 
// language => 

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

$sth = $dbh->query('SELECT * FROM books_not_epub WHERE epub = ""');
$sth->setFetchMode(PDO::FETCH_ASSOC);

$result = $sth->fetchAll();
// echo '<h1>';
// var_dump($result);
// echo '</h1>';

foreach ($result as $i_book => $book)
{
    sleep(5);

    output("<h1>" . $i_book . " : " . $book['id'] . " [" . $book['url'] . "]<br></h1>");

    $handle = curl_init();
    curl_setopt($handle, CURLOPT_URL, $book['url']);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true); // Set the result output to be a string.
    $output = curl_exec($handle);
    curl_close($handle);

    // var_dump( $output );

    // echo "<br><br><br><h1>ENTRIES [$i]</h1><br><br><br><br>";
    preg_match_all('/href="([^"]+)" data-format="epub"/siU', $output, $entry_matches); // entries
    // print_r($entry_matches);

    $sql_update = "UPDATE books_not_epub SET epub = '" . $entry_matches[1][0] . "' WHERE id = " . $book['id'];
    // var_dump($sql_update);

    $sth = $dbh->prepare($sql_update);
    try {
        $sth->execute();
    } catch(Exception $e) {
        echo '<h1>An error has ocurred.</h1><pre>', $e->getMessage() ,'</pre>';
    }
}

exit();

$url = "http://partnersdnld.litres.ru/genres_list_2/";

$handle = curl_init();
curl_setopt($handle, CURLOPT_URL, $url);
curl_setopt($handle, CURLOPT_RETURNTRANSFER, true); // Set the result output to be a string.
$output = curl_exec($handle);
curl_close($handle);

// var_dump( $output );

// echo "<br><br><br><h1>ENTRIES [$i]</h1><br><br><br><br>";
preg_match_all('/id="(\d+)" title="(.+)"/siU', $output, $entry_matches); // entries
print_r($entry_matches);

for ($i = 0; $i < count($entry_matches[0]); $i++)
{
    $sql = "INSERT INTO `genres`(`id`, `genre_id`, `genre_name`) VALUES (NULL," . $entry_matches[1][$i] . ",'" . $entry_matches[2][$i] . "')";
    // var_dump($sql_update);

    $sth = $dbh->prepare($sql);
    try {
        $sth->execute();
    } catch(Exception $e) {
        echo '<h1>An error has ocurred.</h1><pre>', $e->getMessage() ,'</pre>';
    }
}

exit();

$sth = $dbh->query('SELECT id, author, name FROM books_public_domain');
$sth->setFetchMode(PDO::FETCH_ASSOC);

$result = $sth->fetchAll();
// echo '<h1>';
// var_dump($result);
// echo '</h1>';

foreach ($result as $i => $book)
{
    output( "<h1>$i - " . $book['name'] . "</h1>" );

    // var_dump($book['name']);

    // $url = "http://flibusta.is/opds/search?searchType=books&searchTerm=" . urlencode('Учитель фехтования');
    $url = "http://flibusta.is/opds/search?searchType=books&searchTerm=" . urlencode($book['name']);

    $handle = curl_init();
    curl_setopt($handle, CURLOPT_URL, $url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true); // Set the result output to be a string.
    $output = curl_exec($handle);
    curl_close($handle);

    // var_dump( $output );

    // echo "<br><br><br><h1>ENTRIES [$i]</h1><br><br><br><br>";
    preg_match_all('/<entry>(.*)<\/entry>/siU', $output, $entry_matches); // entries
    // print_r($entry_matches);

    foreach ($entry_matches[1] as $entry)
    {
        // echo "<br><br><br><h1>AUTHORS [$i]</h1><br><br><br><br>";
        preg_match_all('/<author> <name>(.+)<\/name>/', $entry, $author_matches); // authors
        // print_r($author_matches);

        $is_similar = false;
        foreach ($author_matches[1] as $author)
        {
            $similarity = similar_text($author, $book['author'], $percent);

            // echo "<br><br><br><h1>$author = $similarity";
            // echo " / $percent" . "</h1><br><br><br>";

            if (false == $is_similar && $percent > 41)
            {
                $is_similar = true;
                break;
            }
        }

        if (!$is_similar)
        {
            continue;
        }

        // echo "<br><br><br><h1>DOWNLOADS [$i]</h1><br><br><br><br>";
        preg_match_all('/Скачиваний: (\d+)/', $entry, $downloads_matches); // downloads
        // print_r($downloads_matches);

        // echo "<br><br><br><h1>LANGUAGES [$i]</h1><br><br><br><br>";
        preg_match_all('/Язык: ([a-zA-Z]+)/', $entry, $lang_matches); // downloads
        // print_r($lang_matches);

        // echo "<br><br><br><h1>EPUB [$i]</h1><br><br><br><br>";
        preg_match_all('/"\/b\/([0-9]+)\/epub"/', $entry, $epub_matches); // epub links
        // print_r($epub_matches);

        $sql_update = "UPDATE books_public_domain SET language = '" . $lang_matches[1][0] . "', epub = '" . $epub_matches[0][0] . "', counter_downloads = " . $downloads_matches[1][0] . " WHERE id = " . $book['id'];
        // var_dump($sql_update);

        $sth = $dbh->prepare($sql_update);
        try {
            $sth->execute();
        } catch(Exception $e) {
            echo '<h1>An error has ocurred.</h1><pre>', $e->getMessage() ,'</pre>';
        }
    }
}

exit();

/*$stream = fopen('partners_utf.xml', 'r');
$parser = xml_parser_create();

while (($data = fread($stream, 16384))) {
    xml_parse($parser, $data); // parse the current chunk
}
xml_parse($parser, '', true); // finalize parsing

xml_parser_free($parser);
fclose($stream);*/
 
$countIx = 0;
 
$xml = new XMLReader();
$xml->open('partners_utf.xml');
 
while($xml->read() && $xml->name != 'offer') {}

$tags_all = array();
 
while($xml->name == 'offer')
{
    $element = new SimpleXMLElement($xml->readOuterXML());
    $flag_continue = false;
    $values = "(NULL , ";
    foreach ($element as $k => $v)
    {
        if ($k == 'publisher' && $v != 'Public Domain' && $v != 'Библиотечный фонд')
        {
            // $flag_continue = true;
            // break;
        } 
        else if ($k == 'genres_list')
        {
            $tags = array_unique(explode(',', $v));
            foreach ($tags as $t)
            {
                array_push($tags_all, $t);
            }
        }

        if (empty($v) && in_array($k, array('price', 'categoryId', 'year', 'age')))
        {
            $v = 0;
        }

        // echo "<br>";
        // var_dump($k);
        // echo $k . " => " . $v;
        // var_dump($v);
        $values .= "'" . $v . "' , ";
    }
    $values .= " '', NULL)";
    // var_dump($values);
	
	// print_r($element->currency->attributes()->id);
	//print "<br>";
	$countIx++;
	
    $xml->next('offer');
    
    // if ($countIx > 1) {return;die();exit();}
    
    unset($element);

    if ($flag_continue)
    {
        continue;
    }

    $sth = $dbh->prepare('INSERT INTO books_full VALUES ' . $values);
    try {
        // $sth->execute(array(':name' => $_GET['name'], ':level_name' => $_GET['level_name'], ':score' => $_GET['score']));
        $sth->execute();
    } catch(Exception $e) {
        echo '<h1>An error has ocurred.</h1><pre>', $e->getMessage() ,'</pre>';
        // die();
    }
}

// var_dump($tags_all);

$tags_all = array_unique($tags_all);

// echo "<br><br><br>";
// var_dump($tags_all);

// foreach ($tags_all as $t)
// {
//     $sql_insert_tag = 'INSERT INTO genres VALUES (' . (int)$t . ')';
//     // var_dump($sql_insert_tag);
//     // echo "<br>";

//     $sth = $dbh->prepare($sql_insert_tag);
//     try {
//         $sth->execute();
//     } catch(Exception $e) {
//         echo '<h1>An error has ocurred.</h1><pre>', $e->getMessage() ,'</pre>';
//         // die();
//     }
// }
 
print "<br><br><br>";
print "Number of items=$countIx<br>";
print "memory_get_usage() =" . memory_get_usage()/1024 . "kb<br>";
print "memory_get_usage(true) =" . memory_get_usage(true)/1024 . "kb<br>";
print "memory_get_peak_usage() =" . memory_get_peak_usage()/1024 . "kb<br>";
print "memory_get_peak_usage(true) =" . memory_get_peak_usage(true)/1024 . "kb<br>";
 
print "custom memory_get_process_usage() =" . memory_get_process_usage() . "kb<br>";
 
$xml->close();
 
/**
 * Returns memory usage from /proc<PID>/status in kbytes.
 *
 * @return int|bool sum of VmRSS and VmSwap in kbytes. On error returns false.
 */
function memory_get_process_usage()
{
	$status = file_get_contents('/proc/' . getmypid() . '/status');
	
	$matchArr = array();
	preg_match_all('~^(VmRSS|VmSwap):\s*([0-9]+).*$~im', $status, $matchArr);
	
	if(!isset($matchArr[2][0]) || !isset($matchArr[2][1]))
	{
		return false;
	}
	
	return intval($matchArr[2][0]) + intval($matchArr[2][1]);
}

?>