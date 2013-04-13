<?

// Nice string conversion utility class picked up from the comments on php.net
define('STR_SYBASE', false);
class Str {
    function gpc2sql($gpc, $maxLength = false)
    {
        return Str::pure2sql(Str::gpc2pure($gpc), $maxLength);
    }
    function gpc2html($gpc, $maxLength = false)
    {
        return Str::pure2html(Str::gpc2pure($gpc), $maxLength);
    }
    function gpc2pure($gpc)
    {
        if (ini_get('magic_quotes_sybase'))
            $pure = str_replace("''", "'", $gpc);
        else $pure = get_magic_quotes_gpc() ? stripslashes($gpc) : $gpc;
        return $pure;
    }
    function html2pure($html)
    {
        return html_entity_decode($html);
    }
    function html2sql($html, $maxLength = false)
    {
        return Str::pure2sql(Str::html2pure($html), $maxLength);
    }
    function pure2html($pure, $maxLength = false)
    {
        return $maxLength ? htmlentities(substr($pure, 0, $maxLength))
                          : htmlentities($pure);
    }
    function pure2sql($pure, $maxLength = false)
    {
        if ($maxLength) $pure = substr($pure, 0, $maxLength);
        return (STR_SYBASE)
               ? str_replace("'", "''", $pure)
               : addslashes($pure);
    }
    function sql2html($sql, $maxLength = false)
    {
        $pure = Str::sql2pure($sql);
        if ($maxLength) $pure = substr($pure, 0, $maxLength);
        return Str::pure2html($pure);
    }
    function sql2pure($sql)
    {
        return (STR_SYBASE)
               ? str_replace("''", "'", $sql)
               : stripslashes($sql);
    }
}

// Logging function
function loggit($lognum, $message)
{
  //Get the big vars list
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Timestamp for this log
  $tstamp=date("Y.m.d - h:i:s");

  //Open the file
  switch($lognum) {
    case 1:
      if($log_errors_only == 1) { return(0); }
      $fd = fopen("$confroot/$log/$acclog", "a");
      break;
    case 2:
      $fd = fopen("$confroot/$log/$errlog", "a");
      break;
    case 3:
      $fd = fopen("$confroot/$log/$dbglog", "a");
      break;
  }

  //Write the message
  if(isset($_SERVER['REMOTE_ADDR'])) {
    fwrite($fd, "[$tstamp] [".$_SERVER['REMOTE_ADDR']."] (".$_SERVER['SCRIPT_NAME'].")(".__LINE__.") " . $message . "\n");
  } else {
    fwrite($fd, "[$tstamp] [LOCAL] (".$_SERVER['SCRIPT_NAME'].")(".__LINE__.") " . $message . "\n");
  }

  //Close the file
  fclose($fd);

  //Return
  return(0);
}


//Generates a random string of the specified length
function random_gen($length = 8, $chars = NULL, $seed = NULL)
{
  // start with a blank string
  $rstring = "";

  // define possible characters
  if($chars == NULL) {
    $possible = "98765432QWERTYUPASDFGHJKLZXCVBNMqwertyupasdfghjkzxcvbnm";
  } else {
    $possible = $chars;
  }

  // set up a counter
  $i = 0;

  // seed the generator if requested
  if($seed != NULL) {
    if( settype($seed, "integer") ) {
      mt_srand($seed);
    }
  }

  // add random characters to string until the length is reached
  while ($i < $length) {

    // pick a random character from the possible ones
    $char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
    $rstring.=$char;
    $i++;
  }

  // done!
  return $rstring;
}

//Get error message
function get_system_message($id = NULL, $type = NULL)
{

  //If id is zero then balk
  if($id == NULL) {
    loggit(2,"Can't lookup this string id: [$id]");
    return(FALSE);
  }
  if($type == NULL) {
    loggit(2,"Can't lookup this string type: [$type]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Look for the sid in the session table
  $stmt="SELECT message FROM $table_string WHERE id=? AND type=?";
  $sql=$dbh->prepare($stmt) or loggit(2, $dbh->error);
  $sql->bind_param("ii", $id, $type) or loggit(2, $dbh->error);
  $sql->execute() or loggit(2, $dbh->error);
  $sql->store_result() or loggit(2, $dbh->error);
  //See if the session is valid
  if($sql->num_rows() < 1) {
    $sql->close() or loggit(2, $dbh->error);
    loggit(2,"Bad error message lookup attempt for id/type: [$id/$type] using: [$stmt]");
    return(FALSE);
  }
  $sql->bind_result($message) or loggit(2, $dbh->error);
  $sql->fetch() or loggit(2, $dbh->error);
  $sql->close() or loggit(2, $dbh->error);

  loggit(1,"Returned error message: [$message] for id: [$id]");

  //Return the error string
  return $message;
}

function array_ereg_search($val, $array) {

  $i = 0;
  $return = array();

  foreach($array as $v) {
    if(preg_match("/$val/i", serialize($v)) > 0) $return[] = $i;
    $i++;
  }

  return $return;
}


//Clean the memo input by doing a proper html-safe encoding on it.
function clean_html($htmldata = NULL)
{
  //If htmldata is zero then balk
  if($htmldata == NULL) {
    loggit(2,"Can't work on the given html data: [$htmldata]");
    return(FALSE);
  }

  //Return a properly encoded HTML-safe string
  return Str::gpc2html($htmldata);

}

//Truncate the text in a string to a specific word count
function limit_words($text, $limit) {
      if (strlen($text) > $limit) {
          $words = str_word_count($text, 2);
          $pos = array_keys($words);
          $text = substr($text, 0, $pos[$limit]);
      }
      return $text;
}

//Truncate the text in a string to a specific word count
function limit_text($text, $limit) {
      if (strlen($text) > $limit) {
          $words = str_word_count($text, 2);
          $pos = array_keys($words);
          $text = substr($text, 0, $pos[$limit]) . '...';
      }
      return $text;
}

//Truncate the text in a string to a certain character count
function truncate_text($string = NULL, $length){
    if($string == NULL) {
      return("");
    }
    if(strlen($string) < $length) {
      return($string);
    }
    $output="";
    settype($string, 'string');
    settype($length, 'integer');
    for($a = 0; $a < $length AND $a < strlen($string); $a++){
        $output .= $string[$a];
    }
    return($output);
}


//http://stackoverflow.com/questions/1193500/php-truncate-html-ignoring-tags
//Truncate html to a specific word count, but keep it valid
function truncate_html($html, $max_words) {
  if( str_word_count($html) > $max_words ) {
    $buffer = tidy_repair_string(limit_words($html, $max_words)."...", array('wrap' => 0, 'show-body-only' => TRUE), 'utf8');
    $buffer = preg_replace("/\<.*\.\.\..*\>/", "", $buffer);
    return($buffer);
  }

  return($html);
}


//Extract img, video, audio and iframe tags from a piece of html
function extract_media($html) {
  $media_tags = array();
  $tag_types = array('a','img','audio','video','iframe');
  $idx = 0;

  //Load the document
  $dom = new DOMDocument;
  libxml_use_internal_errors(true);
  $dom->loadHTML(tidy_repair_string($html));
  libxml_clear_errors();

  //Loop through the given tag types and determine what type of media they reference
  foreach( $tag_types as $tagname ) {
    $tags = $dom->getElementsByTagName($tagname);

    //Loop through any extracted tags of this type
    foreach($tags as $tag) {

      //Check for an href first
      $src = (string)$tag->getAttribute('href');
      if( strlen($src) < 8 ) {
        //If href value looks bogus, try for a src value
        $src = (string)$tag->getAttribute('src');
        if( strlen($src) < 8 ) {
          //That looks bad too. Skip to next tag found.
          continue;
        }
      }

      //Ignore junk
      if( strpos($src, 'feedsportal.com') !== FALSE ) {  continue;  }
      if( strpos($src, 'feedburner.com') !== FALSE ) {  continue;  }
      if( strpos($src, 'jw-share-this') !== FALSE ) {  continue;  }
      if( strpos($src, 'add-to-any') !== FALSE ) {  continue;  }

      //See if this is an external url that we've never seen before
      if( strpos($src, 'http') === 0 && !in_array_r($src, $media_tags) ) {

	//Now check what type it is based on the url text
        $thistype = url_is_media($src);
        if( $thistype == 'image' || $tagname == 'img' ) {
          $media_tags[$idx] = array( 'tag'  => 'img',
                                     'stag' => $tagname,
                                     'type' => 'image',
                                     'src'  => trim($src),
	    	                     'raw'  => "<img src=\"$src\" />" );
          $idx++;
        } else
        if( $thistype == 'audio' || $tagname == 'audio' ) {
          $media_tags[$idx] = array( 'tag'  => 'audio',
                                     'stag' => $tagname,
                                     'type' => 'audio',
                                     'src'  => trim($src),
	    		             'raw'  => "<audio src=\"$src\"></audio>" );
          $idx++;
        } else
        if( $thistype == 'video' || $tagname == 'video' ) {
          $media_tags[$idx] = array( 'tag'  => 'video',
                                     'stag' => $tagname,
                                     'type' => 'video',
                                     'src'  => trim($src),
	    		             'raw'  => "<video src=\"$src\"></video>" );
          $idx++;
        } else
        if( $tagname == 'iframe' ) {
          $media_tags[$idx] = array( 'tag'  => 'iframe',
                                     'stag' => $tagname,
                                     'type' => 'text/html',
                                     'src'  => trim($src),
	    		             'raw'  => "<iframe src=\"$src\"></iframe>" );
          $idx++;
        } else {
	  continue;
	}
          //loggit(3, "DEBUG: $src");
      }
    }
  }

  //loggit(3, "DEBUG: media_tags: ".print_r($media_tags, TRUE));
  return($media_tags);
}


//Take a chunk of text or html, clean it up and make is safe for the aggregator
function clean_feed_item_content($content = "", $length = 0, $asarray = FALSE, $withmedia = TRUE)
{
    if( empty($content) ) {
        if( $asarray == TRUE ) {
          return( array('text' => '', 'media' => '') );
        }
        return('');
    }

    //Let's not waste time if this is not html
    if( !this_is_html($content) ) {
        //loggit(3, "DEBUG: No html found in item body.");
        if( $asarray == TRUE ) {
          return( array('text' => trim($content), 'media' => '') );
        }
        return(trim($content));
    }

    //First, extract all the media related tags
    $media_tags = extract_media($content);

    //Strip all line breaks since breakage is controlled by the markup
    $content = preg_replace("/[\r\n]+/", "", $content);

    //Replace encoded html with real html
    $content = str_replace('&amp;', '&', $content);
    $content = str_replace(array('&lt;','&gt;','&nbsp;'), array('<','>',' '), $content);

    //Strip out all the html tags except for the ones that control textual layout
    $content = strip_tags($content, '<p><br><hr><h1><h2><h3><h4>');

    //Strip the attributes from remaining tags
    $content = stripAttributes($content);

    //Replace continuous whitespace with just one space
    $content = preg_replace("/\ \ +/", " ", $content);

    //Replace all tags that would normally cause line breaks with actual line break codes
    $content = str_replace(array('<p>',  '<p >',  '</p>', '<br>',  '<br/>', '<br />', '<hr>', '<hr/>', '<hr />',
                                 '<h1>', '</h1>', '<h2>', '</h2>', '<h3>',  '</h3>',  '<h4>', '</h4>'), "\n\n", $content);

    //Strip tab codes
    $content = preg_replace('/\t+/', '', $content);

    //Consolidate all strings of multiple linebreaks down to just 2
    $content = preg_replace("/\ +\n\n\ +/", "\n\n", $content);
    $content = preg_replace("/\n\ +\n/", "\n\n", $content);
    $content = preg_replace("/[\r\n]\n+/", "\n\n", $content);

    //If a length was requested, chop it
    if( $length > 0 ) {
      $content = truncate_html($content, $length);
    }

    //Now trim leading and trailing line breaks and white space
    $content = trim($content);

    //Return the clean content and the media tags in an array
    if( $asarray == TRUE ) {
      if( $withmedia == TRUE ) {
        return( array('text' => $content, 'media' => $media_tags) );
      } else {
        return( array('text' => $content) );
      }
    }

    //Build the string to pass back
    if( $withmedia == TRUE ) {
      foreach( $media_tags as $tag ) {
  	$content .= $tag['raw'];
      }
    }

    return($content);
}


//Take the html of an article and clean it up
function clean_article_content($content = "", $length = 0, $asarray = FALSE, $withmedia = TRUE)
{
    if( empty($content) ) {
        if( $asarray == TRUE ) {
          return( array('text' => '', 'media' => '') );
        }
        return('');
    }

    //Let's not waste time if this is not html
    if( !this_is_html($content) ) {
        //loggit(3, "DEBUG: No html found in item body.");
        if( $asarray == TRUE ) {
          return( array('text' => $content, 'media' => '') );
        }
        return($content);
    }

    //First, extract all the media related tags
    if($withmedia == TRUE) {
      $media_tags = extract_media($content);
    }

    //Strip all line breaks since breakage is controlled by the markup
    $content = preg_replace("/[\r\n]+/", "", $content);

    //Replace encoded html with real html
    $content = str_replace('&amp;', '&', $content);
    $content = str_replace(array('&lt;','&gt;','&nbsp;'), array('<','>',' '), $content);

    //Strip out all the html tags except for the ones that control textual layout
    $content = strip_tags($content, '<p><h1><h2><h3><h4><ul><ol><li><table><thead><tbody><tr><td><a><img>');

    //Strip the attributes from remaining tags
    $content = stripAttributes($content, array('href','src'));

    //Replace continuous whitespace with just one space
    $content = preg_replace("/\ \ +/", " ", $content);

    //Strip tab codes
    $content = preg_replace('/\t+/', '', $content);

    //If a length was requested, chop it
    if( $length > 0 ) {
      $content = truncate_html($content, $length);
    }


    //Return the clean content and the media tags in an array
    if( $asarray == TRUE ) {
      if( $withmedia == TRUE ) {
        return( array('text' => trim($content), 'media' => $media_tags) );
      } else {
        return( array('text' => trim($content)) );
      }
    }

    //Build the string to pass back
    if( $withmedia == TRUE ) {
      foreach( $media_tags as $tag ) {
  	$content .= $tag['raw'];
      }
    }

    return(trim($content));
}



//Extensions to the mysqli class to allow returning fetch_assoc possible
//See here: http://www.php.net/manual/en/mysqli-stmt.fetch.php#72720
class mysqli_Extended extends mysqli
{
    protected $selfReference;

    public function __construct($dbHost, $dbUsername, $dbPassword, $dbDatabase)
    {
        parent::__construct($dbHost, $dbUsername, $dbPassword, $dbDatabase);

    }

    public function prepare($query)
    {
        $stmt = new stmt_Extended($this, $query);

        return $stmt;
    }
}

class stmt_Extended extends mysqli_stmt
{
    protected $varsBound = false;
    protected $results;

    public function __construct($link, $query)
    {
        parent::__construct($link, $query);
    }

    public function fetch_assoc()
    {
        // checks to see if the variables have been bound, this is so that when
        //  using a while ($row = $this->stmt->fetch_assoc()) loop the following
        // code is only executed the first time
        if (!$this->varsBound) {
            $meta = $this->result_metadata();
            while ($column = $meta->fetch_field()) {
                // this is to stop a syntax error if a column name has a space in
                // e.g. "This Column". 'Typer85 at gmail dot com' pointed this out
                $columnName = str_replace(' ', '_', $column->name);
                $bindVarArray[] = &$this->results[$columnName];
            }
            call_user_func_array(array($this, 'bind_result'), $bindVarArray);
            $this->varsBound = true;
        }

        if ($this->fetch() != null) {
            // this is a hack. The problem is that the array $this->results is full
            // of references not actual data, therefore when doing the following:
            // while ($row = $this->stmt->fetch_assoc()) {
            // $results[] = $row;
            // }
            // $results[0], $results[1], etc, were all references and pointed to
            // the last dataset
            foreach ($this->results as $k => $v) {
                $results[$k] = $v;
            }
            return $results;
        } else {
            return null;
        }
    }
}

//_______________________________________________________________________________________
//Send a status update to twitter
function tweet($uid = NULL, $content = NULL, $link = "")
{
  //Check parameters
  if($uid == NULL) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }
  if($content == NULL) {
    loggit(2,"The post content is blank or corrupt: [$content]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
  require_once "$confroot/$libraries/oauth/tmhOAuth.php";

  //Globals
  $prefs = get_user_prefs($uid);
  //loggit(3, "Twitter key: [".$prefs['twitterkey']."]");
  //loggit(3, "Twitter secret: [".$prefs['twittersecret']."]");
  //loggit(3, "Twitter user token: [".$prefs['twittertoken']."]");
  //loggit(3, "Twitter user secret: [".$prefs['twittertokensecret']."]");
  $charcount = 138;

  //Connect to twitter using oAuth
  $connection = new tmhOAuth(array(
        'consumer_key' => $prefs['twitterkey'],
        'consumer_secret' => $prefs['twittersecret'],
        'user_token' => $prefs['twittertoken'],
        'user_secret' => $prefs['twittertokensecret'],
        'curl_ssl_verifypeer'   => false
  ));

  if( !empty($link) ) {
    $charcount -= 21;
  }

  //Truncate text if too long to fit in remaining space
  if( strlen($content) > $charcount ) {
    loggit(1,"Had to truncate tweet: [$content] to: [$twcontent] for user: [$uid].");
    $twcontent = truncate_text($content, ($charcount - 3))."...";
  } else {
    $twcontent = $content;
  }

  //Assemble tweet
  $tweet = $twcontent." ".$link;

  //Make an API call to post the tweet
  $code = $connection->request('POST', $connection->url('1/statuses/update'), array('status' => $tweet));

  //Log and return
  if ($code == 200) {
    loggit(1,"Tweeted a new post: [$tweet] for user: [$uid].");
    //loggit(3,"Tweeted a new post: [$tweet] for user: [$uid].");
    return(TRUE);
  } else {
    $twresponse = $connection->response['response'];
    $twrcode = $connection->response['code'];
    loggit(2,"Twitter post did not work posting: [$tweet] for user: [$uid]. Response code: [$twrcode|$twresponse].");
    //loggit(3,"Twitter post did not work posting: [$tweet] for user: [$uid]. Response code: [$twrcode|$twresponse].");
    return(FALSE);
  }
}


//_______________________________________________________________________________________
//Get a twitter user's profile
function get_twitter_profile($username = NULL)
{
  //Check parameters
  if($username == NULL) {
    loggit(2,"The username is blank or corrupt: [$username]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
  require_once "$confroot/$libraries/oauth/tmhOAuth.php";

  //Globals
  if( !sys_twitter_is_enabled() ) {
    loggit(2,"System level Twitter credentials are not enabled.  Check configuration file.");
    return(FALSE);
  }

  //Connect to twitter using oAuth
  $connection = new tmhOAuth(array(
        'consumer_key' => $tw_sys_key,
        'consumer_secret' => $tw_sys_secret,
        'user_token' => $tw_sys_token,
        'user_secret' => $tw_sys_tokensecret,
        'curl_ssl_verifypeer'   => false
  ));

  //Make an API call to get the information in JSON format
  $code = $connection->request('GET', $connection->url('1/users/show'), array('screen_name' => $username));


  //Log and return
  if ($code == 200) {
    $twresponse = $connection->response['response'];
    $twrcode = $connection->response['code'];
    loggit(1,"Got twitter profile for user: [$username] on behalf of user: [$uid]. Response code: [$twrcode].");
    //loggit(3,"Got twitter profile for user: [$username]. Response code: [$twrcode|$twresponse].");
    return(json_decode($twresponse, TRUE));
  } else {
    $twresponse = $connection->response['response'];
    $twrcode = $connection->response['code'];
    loggit(2,"Failed to get twitter profile for user: [$username]. Response code: [$twrcode].");
    //loggit(3,"Failed to get twitter profile for user: [$username] on behalf of [$uid]. Response code: [$twrcode|$twresponse].");
    return(FALSE);
  }
}



//Do a HEAD request on a url to see what the Last-Modified time is
function check_head_lastmod($url, $timeout = 5){

  //Check parameters
  if($url == NULL) {
    loggit(2,"The url is blank or corrupt: [$url]");
    return(FALSE);
  }

  $url = clean_url($url);

  $curl = curl_init();

  curl_setopt($curl, CURLOPT_URL,$url);
    //don't fetch the actual page, you only want headers
    curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
    //stop it from outputting stuff to stdout
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, $timeout );
    curl_setopt( $curl, CURLOPT_TIMEOUT, 30 );

    // attempt to retrieve the modification date
    curl_setopt($curl, CURLOPT_FILETIME, true);

    $result = curl_exec($curl);

    $info = curl_getinfo($curl);

    if ($info['filetime'] != -1) { //otherwise unknown
      return($info['filetime']);
    } else {
      return(FALSE);
    }

}

//Do a HEAD request on a url to see what the Last-Modified time is
function check_head_size($url, $timeout = 3){

  //Check parameters
  if($url == NULL) {
    loggit(2,"The url is blank or corrupt: [$url]");
    return(FALSE);
  }

  $url = clean_url($url);

  $curl = curl_init();

  curl_setopt($curl, CURLOPT_URL,$url);
    //don't fetch the actual page, you only want headers
    curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
    //stop it from outputting stuff to stdout
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, $timeout );
    curl_setopt( $curl, CURLOPT_TIMEOUT, $timeout );

    loggit(3, "CURL: Head check on: [$url]");

    $result = curl_exec($curl);

    $info = curl_getinfo($curl);

    if( $info['download_content_length'] != -1 ) { //otherwise unknown
      return($info['download_content_length']);
    } else {
      //loggit(3, "CURL: ".print_r($info, TRUE));
      return('');
    }

}

//Clean up potentially problematic urls
function clean_url($url = NULL)
{
    //Check parameters
    if( empty($url) ) {
      loggit(2, "The url is missing or empty: [$url].");
      return(FALSE);
    }

    //Detect whether or not this url is encoded
    if( strpos($url, "%") !== FALSE ) {
      //loggit(3, "Url in: [$url].");
      $url = urldecode($url);
      //loggit(3, "Url out: [$url].");
    }

    $url = str_replace( "&amp;", "&", trim($url) );
    $url = str_replace( "feed://", "http://", $url );
    $url = str_replace( ' ', '%20', $url );

    return($url);
}

//Follow redirects to get to the final, good url
function get_final_url( $url, $timeout = 5 )
{
    $url = clean_url($url);
    $ua = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:18.0) Gecko/20100101 Firefox/18.0';

    $cookie = tempnam ("/tmp", "CURLCOOKIE");
    $ch = curl_init();
    curl_setopt( $ch, CURLOPT_USERAGENT, $ua );
    curl_setopt( $ch, CURLOPT_URL, $url );
    curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookie );
    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
    curl_setopt( $ch, CURLOPT_ENCODING, "" );
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
    curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
    curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
    curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
    $content = curl_exec( $ch );
    $response = curl_getinfo( $ch );
    curl_close ( $ch );
    unlink($cookie);

    //Normal re-direct
    if ($response['http_code'] == 301 || $response['http_code'] == 302)
    {
        ini_set("user_agent", $ua);
        $headers = get_headers($response['url']);

        $location = "";
        foreach( $headers as $value )
        {
            if ( substr( strtolower($value), 0, 9 ) == "location:" )
		//loggit(3, "DEBUG: This was a normal http redirect.");
                return get_final_url( trim( substr( $value, 9, strlen($value) ) ) );
        }
    }

    //Javascript re-direct
    if( preg_match("/window\.location\.replace\('(.*)'\)/i", $content, $value) || preg_match("/window\.location\=\"(.*)\"/i", $content, $value) ) {
	//loggit(3, "DEBUG: This was a javascript redirect.");
        if( strpos($value[1], "http") !== FALSE ) {
          return get_final_url ( $value[1] );
        } else {
          return $response['url'];
	}
    } else {
	//loggit(3, "DEBUG: No redirection.");
        return $response['url'];
    }
}

/* gets the data from a URL */
function fetchUrl($url, $timeout = 30)
{
	$url = clean_url($url);

	$ch = curl_init();
        $ua = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:18.0) Gecko/20100101 Firefox/18.0';
	curl_setopt($ch,CURLOPT_USERAGENT, $ua);
	curl_setopt($ch,CURLOPT_URL, $url);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch,CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch,CURLOPT_ENCODING, "");
	$data = curl_exec($ch);
        $response = curl_getinfo($ch);
	curl_close($ch);

	$rcode = $response['http_code'];
	if($rcode != 200) {
                loggit(2, "Got back response code: [$rcode] while fetching: [$url].");
		return(FALSE);
	}

	return $data;
}

function safe_feof($fp, &$start = NULL) {
 $start = microtime(true);

 return feof($fp);
}

function httpRequest($host, $port, $method, $path, $params, $timeout = 30) {
  // Params are a map from names to values
  $paramStr = "";
  foreach ($params as $name => $val) {
    $paramStr .= $name . "=";
    $paramStr .= urlencode($val);
    $paramStr .= "&";
  }

  // Assign defaults to $method and $port, if needed
  if (empty($method)) {
    $method = 'GET';
  }
  $method = strtoupper($method);
  if (empty($port)) {
    $port = 80; // Default HTTP port
  }

  // Create the connection
  $sock = fsockopen($host, $port, $errno, $errst, $timeout);
  stream_set_timeout($sock, $timeout);
  if ($method == "GET") {
    $path .= "?" . $data;
  }
  fputs($sock, "$method $path HTTP/1.1\r\n");
  fputs($sock, "Host: $host\r\n");
  fputs($sock, "Content-type: " . "application/x-www-form-urlencoded\r\n");
  if ($method == "POST") {
    fputs($sock, "Content-length: " . strlen($paramStr) . "\r\n");
  }
  fputs($sock, "Connection: close\r\n\r\n");
  if ($method == "POST") {
    fputs($sock, $paramStr);
  }

  // Buffer the result
  $start = NULL;
  $timeout = ini_get('default_socket_timeout');
  $result = "";
  while( (!safe_feof($sock, $start) && (microtime(true) - $start) < $timeout) && ($sock != FALSE) ) {
    $result .= fgets($sock,1024);
    if($result == FALSE) {
      break;
    }
  }

  fclose($sock);
  return $result;
}

function get_short_url($uid = NULL, $longurl = NULL) {
  //Check parameters
  if (empty($uid)) {
    loggit(2, "The user id is missing or empty: [$uid].");
    return(FALSE);
  }
  if (empty($longurl)) {
    loggit(2, "The long url is missing or empty: [$longurl].");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
  require_once "$confroot/$libraries/oauth/tmhOAuth.php";

  //Get the prefs for this user
  $prefs = get_user_prefs($uid);

  //Internal url shortener always takes precedence
  if( !empty($prefs['s3shortbucket'])  ) {
    $shortcode = get_next_short_url($prefs['lastshortcode']);
    $file = create_short_url_file($longurl);
    $result = putInS3($file, $shortcode, $prefs['s3shortbucket'], $prefs['s3key'], $prefs['s3secret'], "text/html");
    if($result == FALSE) {
      return(FALSE);
    } else {
      update_last_shortcode($uid, $shortcode);
      return("http://".$prefs['s3shortbucket']."/".$shortcode);
    }
  }

  //Use the given external shortener if one was specified
  if( !empty($prefs['urlshortener']) ) {
    $apicall = str_replace('%@', rawurlencode($longurl), $prefs['urlshortener'], $shorted);
    $shorturl = @file_get_contents($apicall);

    if($shorturl == FALSE || $shorted < 1) {
      loggit(2, "Failed to get a short url for long url: [$longurl]. Check shortener server: [$apicall].");
      return(FALSE);
    } else {
      loggit(1, "Got a short url: [$shorturl] for long url: [$longurl].");
      return($shorturl);
    }
  }

  return(FALSE);
}

function create_short_url_file($url = NULL) {
  //Check parameters
  if (empty($url)) {
    return(FALSE);
  }

  return("<html><head><meta http-equiv=\"refresh\" content=\"1;URL='$url'\"></head><body></body></html>");
}

function get_s3_buckets($key, $secret) {
  //Check parameters
  if (empty($key)) {
    loggit(2, "Key missing from S3 put call: [$key].");
    return(FALSE);
  }
  if (empty($secret)) {
    loggit(2, "Secret missing from S3 put call: [$secret].");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Set up
  require_once "$confroot/$libraries/s3/S3.php";
  $s3 = new S3($key, $secret);

  //Get a list of buckets
  $buckets = $s3->listBuckets();

  //Were we able to get a list?
  if($buckets == FALSE) {
    loggit(2, "Could not get a bucket list using: [$key | $secret].");
    return(FALSE);
  }

  //Give back the buckets array
  return($buckets);
}

function get_s3_bucket_location($key, $secret, $bucket) {
  //Check parameters
  if (empty($key)) {
    loggit(2, "Key missing from S3 put call: [$key].");
    return(FALSE);
  }
  if (empty($secret)) {
    loggit(2, "Secret missing from S3 put call: [$secret].");
    return(FALSE);
  }
  if (empty($bucket)) {
    loggit(2, "Bucket missing from S3 put call: [$bucket].");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Set up
  require_once "$confroot/$libraries/s3/S3.php";
  $s3 = new S3($key, $secret);

  //Get a list of buckets
  $bucketloc = $s3->getBucketLocation($bucket);

  //Were we able to get a list?
  if($bucketloc == FALSE) {
    loggit(2, "Could not get a bucket location using: [$key | $secret | $bucket].");
    return(FALSE);
  }

  //Give back the buckets array
  //loggit(3, "DEBUG: Bucket: [$bucket] is located in: [$bucketloc].");
  return($bucketloc);
}

function get_s3_regional_dns($location) {

  //Check parameters
  if (empty($location)) {
    loggit(2, "Location missing from S3 call: [$location].");
    return(FALSE);
  }

  //Open the file
  switch($location) {
    case "EU":
      return("s3-eu-west-1.amazonaws.com");
      break;
    case 2:
      break;
    case 3:
      break;
    default:
      return("s3.amazonaws.com");
      break;
  }
}

function putInS3($content, $filename, $bucket, $key, $secret, $headers = NULL, $private = FALSE) {

  //Check parameters
  if (empty($content)) {
    loggit(2, "Content missing from S3 put call: [$content].");
    return(FALSE);
  }
  if (empty($filename)) {
    loggit(2, "Filename missing from S3 put call: [$filename].");
    return(FALSE);
  }
  if (empty($bucket)) {
    loggit(2, "Bucket missing from S3 put call: [$bucket].");
    return(FALSE);
  }
  if (empty($key)) {
    loggit(2, "Key missing from S3 put call: [$key].");
    return(FALSE);
  }
  if (empty($secret)) {
    loggit(2, "Secret missing from S3 put call: [$secret].");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
  require_once "$confroot/$libraries/oauth/tmhOAuth.php";

  //Set up
  require_once "$confroot/$libraries/s3/S3.php";
  $s3 = new S3($key, $secret);


  //Construct bucket subfolder path, if any
  $s3bucket = $bucket;
  if(stripos($s3bucket, '/', 1) === FALSE) {
        $subpath = "";
  } else {
        $spstart = stripos($s3bucket, '/', 1);
        $bucket  = str_replace('/', '', substr($s3bucket, 0, stripos($s3bucket, '/', 1)));
        $subpath = rtrim(substr($s3bucket, $spstart + 1), '/')."/";
  }

  loggit(1, "putInS3(): Putting file in S3: [$filename], going to attempt bucket: [$bucket] and subpath: [$subpath].");
  //get_s3_bucket_location($key, $secret, $bucket);

  if( $private ) {
    $s3res = $s3->putObject($content, $bucket, $subpath.$filename, S3::ACL_PRIVATE, array(), $headers);
  } else {
    $s3res = $s3->putObject($content, $bucket, $subpath.$filename, S3::ACL_PUBLIC_READ, array(), $headers);
  }
  if(!$s3res) {
       	loggit(2, "Could not create S3 file: [$bucket/$subpath$filename].");
       	//loggit(3, "Could not create S3 file: [$bucket/$subpath$filename].");
	return(FALSE);
  } else {
  	$s3url = "http://s3.amazonaws.com/$bucket/$subpath$filename";
  	loggit(1, "Wrote feed to S3: [$s3url].");
  }

  return(TRUE);
}

//This puts a file into S3 from an actual file
function putFileInS3($file, $filename, $bucket, $key, $secret, $contenttype = NULL, $private = FALSE) {

  //Check parameters
  if (empty($file)) {
    loggit(2, "File missing from S3 put call: [$file].");
    return(FALSE);
  }
  if (empty($filename)) {
    loggit(2, "Filename missing from S3 put call: [$filename].");
    return(FALSE);
  }
  if (empty($bucket)) {
    loggit(2, "Bucket missing from S3 put call: [$bucket].");
    return(FALSE);
  }
  if (empty($key)) {
    loggit(2, "Key missing from S3 put call: [$key].");
    return(FALSE);
  }
  if (empty($secret)) {
    loggit(2, "Secret missing from S3 put call: [$secret].");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
  require_once "$confroot/$libraries/oauth/tmhOAuth.php";

  //Set up
  require_once "$confroot/$libraries/s3/S3.php";
  $s3 = new S3($key, $secret);

  //Construct bucket subfolder path, if any
  $s3bucket = $bucket;
  if(stripos($s3bucket, '/', 1) === FALSE) {
        $subpath = "";
  } else {
        $spstart = stripos($s3bucket, '/', 1);
        $bucket  = str_replace('/', '', substr($s3bucket, 0, stripos($s3bucket, '/', 1)));
        $subpath = rtrim(substr($s3bucket, $spstart + 1), '/')."/";
  }

  loggit(1, "Putting file in S3: [$filename], going to attempt bucket: [$bucket] and subpath: [$subpath].");
  //get_s3_bucket_location($key, $secret, $bucket);

  $content = $s3->inputFile($file);
  if($contenttype == NULL) {
    if($private == FALSE) {
      $s3res = $s3->putObject($content, $bucket, $subpath.$filename, S3::ACL_PUBLIC_READ);
    } else {
      $s3res = $s3->putObject($content, $bucket, $subpath.$filename, S3::ACL_PRIVATE);
    }
  } else {
    if($private == FALSE) {
      $s3res = $s3->putObject($content, $bucket, $subpath.$filename, S3::ACL_PUBLIC_READ, array(), $contenttype);
    } else {
      $s3res = $s3->putObject($content, $bucket, $subpath.$filename, S3::ACL_PRIVATE, array(), $contenttype);
    }
  }
  if(!$s3res) {
       	loggit(2, "Could not create S3 file: [$bucket/$subpath$filename].");
       	loggit(3, "Could not create S3 file: [$bucket/$subpath$filename].");
	return(FALSE);
  } else {
  	$s3url = "http://s3.amazonaws.com/$bucket/$subpath$filename";
  	loggit(1, "Wrote file to S3: [$s3url].");
  }

  return(TRUE);
}

function get_next_short_url($previousNumber) {
		// Begin Config
		$characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$bannedWords = "fuck,ass,dick,balls,pussy,tits,bitch,shit,cunt,shit";
		$bannedWordCaseSensitive = FALSE;
		// End Config

		// Turn strings into arrays
		$characters = str_split($characters);
		$bannedWords = explode(",", $bannedWords);

		$previousNumberArray = array_reverse(str_split($previousNumber)); // Return an array containing the previous number, ready for incrementation

		// Check that the previous number only contains valid characters
		for ($i1 = 0; $i1 < count($previousNumberArray); $i1++) {
			if (array_search($previousNumberArray[$i1], $characters) === FALSE) {
				return "Error, please enter only alphanumeric characters";  // Throw toys out the pram
			}
		}

		$currentColumn = 0;

		do {
			// Get the character of the letter in the previous number in the character array
			if (isset($previousNumberArray[$currentColumn])) {
				$characterPosition = array_search($previousNumberArray[$currentColumn], $characters);
			} else {
				$characterPosition = array_search($previousNumberArray[$currentColumn - 1], $characters);
			}

			// Figure out if more than 1 column needs changed
			if (count($characters)-1 != $characterPosition) {
				$newCharacter = $characters[$characterPosition + 1];
				$incrementNextColumn = false;
			} else {
				$newCharacter = $characters[0];
				$incrementNextColumn = true;
			}

			// Perform the swap
			$previousNumberArray[$currentColumn] = $newCharacter;

			// Increment column number
			$currentColumn++;
		} while ($incrementNextColumn); // Repeat if another column needs swapped

		// Reassemble number array into a string
		$previousNumberArray = array_reverse($previousNumberArray);
		$newNumber = implode("", $previousNumberArray);

		// Keep generating new numbers if a banned word is found until the number is no longer banned
		while(isBannedWord($newNumber, $bannedWords, $bannedWordCaseSensitive)) {
			$newNumber = countWithCharacters($newNumber);
		}

		// Return the new number
		return $newNumber;
}

// Banned word checker
function isBannedWord($word, $bannedWords, $caseSensitive) {
	$isBanned = false;

	foreach($bannedWords as $bannedWord) {
		if ($caseSensitive) {
			if ($word == $bannedWord) {
				$isBanned = true;
			}
		} else {
			if (strtolower($word) == strtolower($bannedWord)) {
				$isBanned = true;
			}
		}
	}

	return $isBanned;
}

//_______________________________________________________________________________________
//Update the last shortcode pref for a user
function update_last_shortcode($uid = NULL, $shortcode = NULL)
{
  //Check parameters
  if(empty($uid)) {
    loggit(2,"The user id is blank or corrupt: [$uid]");
    return(FALSE);
  }
  if(empty($shortcode)) {
    loggit(2,"The shortcode is blank or corrupt: [$shortcode]");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Connect to the database server
  $dbh=new mysqli($dbhost,$dbuser,$dbpass,$dbname) or print(mysql_error());

  //Now that we have a good id, put the article into the database
  $stmt = "UPDATE $table_prefs SET lastshortcode=? WHERE uid=?";
  $sql=$dbh->prepare($stmt) or print(mysql_error());
  $sql->bind_param("ss", $shortcode, $uid) or print(mysql_error());
  $sql->execute() or print(mysql_error());
  $sql->close() or print(mysql_error());

  //Log and return
  loggit(1,"Set last shortcode to: [$shortcode] for user: [$uid].");
  return(TRUE);
}

/**
* Launch Background Process
*
* Launches a background process (note, provides no security itself, $call must be sanitized prior to use)
* @param string $call the system call to make
* @author raccettura
*/
function launchBackgroundProcess($call = NULL) {

    pclose(popen($call.' /dev/null &', 'r'));

    return true;
}

//A lock helper class to make sure cron jobs don't overlap
class cronHelper {

	private static $pid;

	function __construct() {}

	function __clone() {}

	private static function isrunning() {
		$pids = explode(PHP_EOL, `ps -e | awk '{print $1}'`);
		if(in_array(self::$pid, $pids))
			return TRUE;
		return FALSE;
	}

	public static function lock() {
		global $argv;

		//Includes
		include get_cfg_var("cartulary_conf").'/includes/env.php';

		$lock_file = $lockdir.basename($argv[0]).$locksuffix;

		if(file_exists($lock_file)) {
			//return FALSE;

			// Is running?
			self::$pid = file_get_contents($lock_file);
			if(self::isrunning()) {
				loggit(2, "==".self::$pid."== Already in progress...");
				return FALSE;
			}
			else {
				loggit(2, "==".self::$pid."== Previous job died abruptly...");
			}
		}

		self::$pid = getmypid();
		file_put_contents($lock_file, self::$pid);
		loggit(1, "==".self::$pid."== Lock acquired, processing the job...");
		return self::$pid;
	}

	public static function unlock() {
		global $argv;

		//Includes
		include get_cfg_var("cartulary_conf").'/includes/env.php';

		$lock_file = $lockdir.basename($argv[0]).$locksuffix;

		if(file_exists($lock_file))
			unlink($lock_file);

		loggit(1, "==".self::$pid."== Releasing lock...");
		return TRUE;
	}

}

//Encode entities safely for xml output
if( !function_exists( 'xmlentities' ) ) {
    function xmlentities( $string ) {
        $not_in_list = "A-Z0-9a-z\s_-";
        return preg_replace_callback( "/[^{$not_in_list}]/" , 'get_xml_entity_at_index_0' , $string );
    }

    function get_xml_entity_at_index_0( $CHAR ) {
        if( !is_string( $CHAR[0] ) || ( strlen( $CHAR[0] ) > 1 ) ) {
            loggit(2, "XMLENTITIES: 'get_xml_entity_at_index_0' requires data type: 'char' (single character). '{$CHAR[0]}' does not match this type." );
        }
        switch( $CHAR[0] ) {
	    case '"':    case '&':    case '<':    case '>':
                return htmlspecialchars( $CHAR[0], ENT_QUOTES );
                break;
            default:
                $rch = numeric_entity_4_char($CHAR[0]);
		$apre = array( '&#036;', '&#044;', '&#046;', '&#194;', '&#171;', '&#124;', '&#058;', '&#226;', '&#128;', '&#153;',
			       '&#039;', '&#047;', '&#061;', '&#156;', '&#157;', '&#148;', '&#091;', '&#093;', '&#160;', '&#063;',
                               '&#037;', '&#040;', '&#041;', '&#059;', '&#147;', '&#035;', '&#043;', '&#064;', '&#13;',  '&#123;',
                               '&#125;', '&amp;#13;', '&#162;', '&#033;', '&#132;', '&#179;', '&#187;', '&#195;', '&#161;', '&#166;',
                               '&#239;', '&#191;', '&#152;', "'", '&#042;', '&#169;', '&#136;', '&#146;' );
		$apst = array( '$', ',', '.', '', '', '|', ':', '&apos;', '', '', '&apos;', '/', '=', '&apos;', '&apos;', '--', '[', ']', ' ', '?', '%',
                               '(', ')', ';', '&apos;', '#', '+', '@', '', '{', '}', '', '', '!', '', '&quot;', '&gt;&gt;', '', '', '...', '', '', '', '&apos;',
			       '*', '(C)', '', '&apos;' );

		$rch = str_replace($apre, $apst, $rch);

		return $rch;
                break;
        }
    }

    function numeric_entity_4_char( $char ) {
        return "&#".str_pad(ord($char), 3, '0', STR_PAD_LEFT).";";
    }
}


//First do a full replacement, then selectively re-convert safe tags
function safe_html($content = NULL, $tags = array()) {

  $content = str_replace("\n", '<br/>', $content);

  $content = xmlentities($content);

  $content = str_replace('&lt;br/&gt;', '<br/>', $content);


  return($content);
}

//Search in a multidimensional array
function in_array_r($needle, $haystack, $strict = true) {
    foreach ($haystack as $item) {
        if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict))) {
            return true;
        }
    }

    return false;
}

function xml_safe_url($url = NULL) {
  if(empty($url)) {
    loggit(2, "XMLSAFEURL: Corrupt url passed in: [$url].");
    return '';
  }

  $up = explode('?', $url, 2);

  return $up[0].rawurlencode($up[1]);

}

function endc( $array ) { return end( $array ); }

//_____________________________________________________________________________________________
//http://stackoverflow.com/questions/4795385/how-do-you-use-bcrypt-for-hashing-passwords-in-php
class Bcrypt {
  private $rounds;
  public function __construct($rounds = 12) {
    if(CRYPT_BLOWFISH != 1) {
      throw new Exception("bcrypt not supported in this installation. See http://php.net/crypt");
    }

    $this->rounds = $rounds;
  }

  public function hash($input) {
    $hash = crypt($input, $this->getSalt());

    if(strlen($hash) > 13)
      return $hash;

    return false;
  }

  public function verify($input, $existingHash) {
    $hash = crypt($input, $existingHash);

    return $hash === $existingHash;
  }

  private function getSalt() {
    $salt = sprintf('$2a$%02d$', $this->rounds);

    $bytes = $this->getRandomBytes(16);

    $salt .= $this->encodeBytes($bytes);

    return $salt;
  }

  private $randomState;
  private function getRandomBytes($count) {
    $bytes = '';

    if(function_exists('openssl_random_pseudo_bytes') &&
        (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN')) { // OpenSSL slow on Win
      $bytes = openssl_random_pseudo_bytes($count);
    }

    if($bytes === '' && is_readable('/dev/urandom') &&
       ($hRand = @fopen('/dev/urandom', 'rb')) !== FALSE) {
      $bytes = fread($hRand, $count);
      fclose($hRand);
    }

    if(strlen($bytes) < $count) {
      $bytes = '';

      if($this->randomState === null) {
        $this->randomState = microtime();
        if(function_exists('getmypid')) {
          $this->randomState .= getmypid();
        }
      }

      for($i = 0; $i < $count; $i += 16) {
        $this->randomState = md5(microtime() . $this->randomState);

        if (PHP_VERSION >= '5') {
          $bytes .= md5($this->randomState, true);
        } else {
          $bytes .= pack('H*', md5($this->randomState));
        }
      }

      $bytes = substr($bytes, 0, $count);
    }

    return $bytes;
  }

  private function encodeBytes($input) {
    // The following is code from the PHP Password Hashing Framework
    $itoa64 = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

    $output = '';
    $i = 0;
    do {
      $c1 = ord($input[$i++]);
      $output .= $itoa64[$c1 >> 2];
      $c1 = ($c1 & 0x03) << 4;
      if ($i >= 16) {
        $output .= $itoa64[$c1];
        break;
      }

      $c2 = ord($input[$i++]);
      $c1 |= $c2 >> 4;
      $output .= $itoa64[$c1];
      $c1 = ($c2 & 0x0f) << 2;

      $c2 = ord($input[$i++]);
      $c1 |= $c2 >> 6;
      $output .= $itoa64[$c1];
      $output .= $itoa64[$c2 & 0x3f];
    } while (1);

    return $output;
  }
}


//Build an s3 url off of a given users prefs and a path and filename
function get_s3_url($uid = NULL, $path = NULL, $filename = NULL) {

  if( empty($uid) ) {
    loggit(2, "The user id was empty: [$uid].");
    return(FALSE);
  }

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Get key s3 info
  $s3info = get_s3_info($uid);
  $slashpos = strpos($s3info['bucket'], "/");
  if( $slashpos === FALSE ) {
    $mybucket = $s3info['bucket'];
    $myfolder = "";
  } else {
    $mybucket = substr($s3info['bucket'], 0, $slashpos);
    $myfolder = substr($s3info['bucket'], $slashpos + 1);
  }

  //Globals
  $url = '';
  $prot = 'http://';
  $host = $mybucket.'.s3.amazonaws.com';
  $path = trim($path, '/');
  $filename = ltrim($filename, '/');

  //First let's get a proper hostname value
  if( !empty($s3info['cname']) ) {
    if( $s3info['sys'] == TRUE ) {
      $url = $prot.trim($s3info['cname'], '/').'/'.$s3info['uname'];
    } else {
      $url = $prot.trim($s3info['cname'], '/');
    }
  } else {
    $url = $prot.$host;
    if( !empty($s3info['bucket']) ) {
      //$url .= "/".trim($s3info['bucket'], '/');
      $url .= "/".$myfolder;
    }
  }

  $url = trim($url, "/");

  if( !empty($path) ) {
    $url .= "/".$path;
  }

  if( !empty($filename) ) {
    $url .= "/".$filename;
  }

  //loggit(3, "DEBUG: ".print_r($s3info, TRUE));
  //loggit(3, "DEBUG: $url");
  return($url);
}


//Build an s3 url for the server's river files
function get_server_river_s3_url($path = NULL, $filename = NULL) {

  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';

  //Globals
  $url = '';
  $prot = 'http://';
  $host = 's3.amazonaws.com';
  $path = trim($path, '/');
  $filename = ltrim($filename, '/');

  //Get key s3 info
  $s3info = get_sys_s3_info();

  //First let's get a proper hostname value
  if( !empty($s3info['rivercname']) ) {
      $url = $prot.trim($s3info['rivercname'], '/');
  } else {
    $url = $prot.$host;
    if( !empty($s3info['riverbucket']) ) {
      $url .= "/".trim($s3info['riverbucket'], '/');
    }
  }

  if( !empty($path) ) {
    $url .= "/".$path;
  }

  if( !empty($filename) ) {
    $url .= "/".$filename;
  }

  return($url);
}

//Remove all characters except alphanum and dashes
function stripText($text)
{
  $text = strtolower(trim($text));
  // replace all white space sections with a dash
  $text = str_replace(' ', '-', $text);
  // strip all non alphanum or -
  $clean = ereg_replace("[^A-Za-z0-9\-]", "", $text);

  return $clean;
}

//Make a filename html/script safe by taking out funky stuff
function cleanFilename($text)
{
  $text = strtolower(trim($text));

  // strip all except these characters
  $clean = ereg_replace("[^A-Za-z0-9\-\.]", "", $text);

  return $clean;
}


//http://www.php.net/manual/en/function.json-decode.php#107107
//Clean data for json_encode
function prepareJSON($input) {

    //This will convert ASCII/ISO-8859-1 to UTF-8.
    //Be careful with the third parameter (encoding detect list), because
    //if set wrong, some input encodings will get garbled (including UTF-8!)
    $imput = mb_convert_encoding($input, 'UTF-8', 'ASCII,UTF-8,ISO-8859-1');

    //Remove UTF-8 BOM if present, json_decode() does not like it.
    if(substr($input, 0, 3) == pack("CCC", 0xEF, 0xBB, 0xBF)) $input = substr($input, 3);

    return stripslashes($input);
}

function getAlternateLinkUrl($html = NULL) {
  $dom = new DOMDocument();
  libxml_use_internal_errors(true);
  $dom->loadHTML($html);
  libxml_clear_errors();
  $xpath = new DOMXPath($dom);

  $links = array();
  $seenurls = array();
  $count = 0;

  //First look for the right way to do it
  $tags = $xpath->query('//link[@rel="alternate" and contains(@type, "rss") or contains(@type, "atom") or contains(@type, "opml")]');
  foreach( $tags as $tag ) {
    $url = (string)trim($tag->getAttribute("href"));
    if( !in_array($url, $seenurls) ) {
      $links[$count] = array( 'url' => $url,
                              'type' => (string)trim($tag->getAttribute("type")),
			      'title' => (string)trim($tag->getAttribute("title")),
			      'text' => '',
			      'element' => 'link' );
      $seenurls[$count] = $url;
      $count++;
    }
  }

  //Now try and find any anchors that have rss in their hrefs
  $tags = $xpath->query('//a[contains(@href, "rss") or contains(@href, "feed")]');
  foreach( $tags as $tag ) {
    $url = (string)trim($tag->getAttribute("href"));
    if( !in_array($url, $seenurls) ) {
      $links[$count] = array( 'url' => $url,
                              'type' => 'href',
			      'title' => (string)trim($tag->getAttribute("title")),
			      'text' => (string)trim($tag->getAttribute("title")),
			      'element' => 'a' );
      $seenurls[$count] = $url;
      $count++;
    }
  }

  //Now try and find any anchors that have rss in their class name
  $tags = $xpath->query('//a[contains(@class, "rss")]');
  foreach( $tags as $tag ) {
    $url = (string)trim($tag->getAttribute("href"));
    if( !in_array($url, $seenurls) ) {
      $links[$count] = array( 'url' => $url,
                              'type' => 'class',
			      'title' => (string)trim($tag->getAttribute("title")),
			      'text' => (string)trim($tag->nodeValue),
			      'element' => 'a' );
      $seenurls[$count] = $url;
      $count++;
    }
  }

  //Return the array
  return($links);
}

function absolutizeUrl($url = NULL, $rurl = NULL) {
  //loggit(3, "Absolutizing url: [$url] with referer: [$rurl].");

  //Check if the url is good first
  $url = clean_url($url);
  $pos = strpos($url, 'http');
  if( $pos !== FALSE && $pos == 0 ) {
    return($url);
  }

  //Check if url has a preceding slash
  $pos = strpos($url, '/');
  if( $pos !== FALSE && $pos == 0 ) {
    //loggit(3, "Url: [$url] is root-relative.");
    $rp = parse_url($rurl);
    if( $rp != FALSE ) {
      return($rp['scheme']."://".$rp['host'].$url);
    } else {
      return($url);
    }
  }

  //Check if url has preceding dots
  $pos = strpos($url, '../');
  if( $pos !== FALSE && $pos == 0 ) {
    //loggit(3, "Url: [$url] is dot-relative.");
    $rp = parse_url($rurl);
    if( $rp != FALSE ) {
      $slashpos = strrpos(rtrim($rp['path'], '/'), '/');
      $newpath = substr($rp['path'], 0, $slashpos)."/";
      $slashpos = strrpos(rtrim($newpath, '/'), '/');
      $newpath = substr($newpath, 0, $slashpos)."/";
      $newurl = substr($url, 3);
      return($rp['scheme']."://".$rp['host'].$newpath.$newurl);
    } else {
      return($url);
    }
  }

  //Fix up the referring url as a base url
  //loggit(3, "Url: [$url] is truly relative.");
  $rp = parse_url($rurl);
  if( $rp != FALSE ) {
    return(rtrim($rp['scheme']."://".$rp['host'].$rp['path'], '/')."/".$url);
  } else {
    return($url);
  }

  //loggit(3, "Url: [$url] was messed up.");
  return($url);
}

//Remove duplicate array entries
function remove_dup($matriz) {
    $aux_ini=array();
    $entrega=array();
    for($n=0;$n<count($matriz);$n++)
    {
        $aux_ini[]=serialize($matriz[$n]);
    }
    $mat=array_unique($aux_ini);
    for($n=0;$n<count($matriz);$n++)
    {
            $entrega[]=unserialize($mat[$n]);
    }
    return $entrega;
}

/**
 * Indents a flat JSON string to make it more human-readable.
 *
 * @param string $json The original JSON string to process.
 *
 * @return string Indented version of the original JSON string.
 */
//pilfered from http://recursive-design.com/blog/2008/03/11/format-json-with-php/
function format_json($json)
{

    $result      = '';
    $pos         = 0;
    $strLen      = strlen($json);
    $indentStr   = '    ';
    $newLine     = "\n";
    $prevChar    = '';
    $outOfQuotes = true;

    for ($i=0; $i<=$strLen; $i++) {

        // Grab the next character in the string.
        $char = substr($json, $i, 1);

        // Are we inside a quoted string?
        if ($char == '"' && $prevChar != '\\') {
            $outOfQuotes = !$outOfQuotes;

        // If this character is the end of an element,
        // output a new line and indent the next line.
        } else if(($char == '}' || $char == ']') && $outOfQuotes) {
            $result .= $newLine;
            $pos --;
            for ($j=0; $j<$pos; $j++) {
                $result .= $indentStr;
            }
        }

        // Add the character to the result string.
        $result .= $char;

        // If the last character was the beginning of an element,
        // output a new line and indent the next line.
        if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
            $result .= $newLine;
            if ($char == '{' || $char == '[') {
                $pos ++;
            }

            for ($j = 0; $j < $pos; $j++) {
                $result .= $indentStr;
            }
        }

        $prevChar = $char;
    }

    return $result;
}


//Discover what kind of device this is
function get_device_type()
{
  $device = "";

  //Be nice to the error logs
  if ( !isset($_SERVER['HTTP_USER_AGENT']) ) {
    return($device);
  }

  if ( strstr($_SERVER['HTTP_USER_AGENT'], "iPad")) {
    $device = "ipad";
  } else if ( strstr($_SERVER['HTTP_USER_AGENT'], "iPhone")) {
    $device = "iphone";
  } else if ( strstr($_SERVER['HTTP_USER_AGENT'], "Android")) {
    $device = "android";
  } else if ( strstr($_SERVER['HTTP_USER_AGENT'], "Windows Phone")) {
    $device = "wphone";
  }

  return($device);
}


//Discover what kind of platform this is
function get_platform_type()
{
  $platform = "";

  //Be nice to the error logs
  if ( !isset($_SERVER['HTTP_USER_AGENT']) ) {
    return($platform);
  }

  if ( strstr($_SERVER['HTTP_USER_AGENT'], "iPad")) {
    $platform = "tablet";
  } else if ( strstr($_SERVER['HTTP_USER_AGENT'], "iPhone")) {
    $platform = "mobile";
  } else if ( strstr($_SERVER['HTTP_USER_AGENT'], "Android")) {
    $platform = "mobile";
  } else if ( strstr($_SERVER['HTTP_USER_AGENT'], "Windows Phone")) {
    $platform = "mobile";
  }

  return($platform);
}


//Discover what version of device this is
function get_device_version()
{
  $device = "";

  //Be nice to the error logs
  if ( !isset($_SERVER['HTTP_USER_AGENT']) ) {
    return($device);
  }

  if ( strstr($_SERVER['HTTP_USER_AGENT'], "Android 2")) {
    $device = "2";
  } else
  if ( strstr($_SERVER['HTTP_USER_AGENT'], "Android 3")) {
    $device = "3";
  } else
  if ( strstr($_SERVER['HTTP_USER_AGENT'], "Android 4")) {
    $device = "4";
  } else
  if ( strstr($_SERVER['HTTP_USER_AGENT'], "OS 3")) {
    $device = "3";
  } else
  if ( strstr($_SERVER['HTTP_USER_AGENT'], "OS 4")) {
    $device = "4";
  } else
  if ( strstr($_SERVER['HTTP_USER_AGENT'], "OS 5")) {
    $device = "5";
  } else
  if ( strstr($_SERVER['HTTP_USER_AGENT'], "OS 6")) {
    $device = "6";
  }


  return($device);
}



//_____________________________________________________________________
//_____http://www.php.net/manual/en/function.filesize.php#106935
//Make a friendlier version of long byte strings
function format_bytes($a_bytes)
{
    if ($a_bytes < 1024) {
        return $a_bytes .' B';
    } elseif ($a_bytes < 1048576) {
        return round($a_bytes / 1024, 2) .' KiB';
    } elseif ($a_bytes < 1073741824) {
        return round($a_bytes / 1048576, 2) . ' MiB';
    } elseif ($a_bytes < 1099511627776) {
        return round($a_bytes / 1073741824, 2) . ' GiB';
    } elseif ($a_bytes < 1125899906842624) {
        return round($a_bytes / 1099511627776, 2) .' TiB';
    } elseif ($a_bytes < 1152921504606846976) {
        return round($a_bytes / 1125899906842624, 2) .' PiB';
    } elseif ($a_bytes < 1180591620717411303424) {
        return round($a_bytes / 1152921504606846976, 2) .' EiB';
    } elseif ($a_bytes < 1208925819614629174706176) {
        return round($a_bytes / 1180591620717411303424, 2) .' ZiB';
    } else {
        return round($a_bytes / 1208925819614629174706176, 2) .' YiB';
    }
}


//Get some user input from the command line
function get_user_response()
{
  $handle = fopen ("php://stdin","r");
  $line = fgets($handle);

  return( trim($line) );
}


//Get a filename without any trailing extensions
function chop_extension( $file = NULL )
{
  $info = pathinfo($file);
  $file_name = @basename($file, '.'.$info['extension']);

  return($file_name);
}


//http://stackoverflow.com/questions/6284553/using-an-array-as-needles-in-strpos
//Search for a substring with an array as the needle
function strposa($haystack, $needles=array(), $offset=0) {
        $chr = array();
        foreach($needles as $needle) {
                $res = strpos($haystack, $needle, $offset);
                if ($res !== false) $chr[$needle] = $res;
        }
        if(empty($chr)) return false;
        return min($chr);
}


//Do our best to make a good mime type for a given url/type
function make_mime_type( $url = NULL, $type = NULL )
{

  //Check params
  if( empty($url) ) {
    loggit(2, "The url was blank or corrupt: [$url]");
    return(FALSE);
  }

  //Let's be clean
  $url = clean_url($url);
  $type = trim($type);

  // ----- Pictures
  if( strposa($url, array('.jpg','.jpeg')) !== FALSE ) {  return('image/jpeg');  }
  if( strposa($url, array('.png')) !== FALSE ) {  return('image/png');  }
  if( strposa($url, array('.gif')) !== FALSE ) {  return('image/gif');  }

  // ----- Textual
  if( strposa($url, array('.htm','.html')) !== FALSE ) {  return('text/html');  }
  if( strposa($url, array('.pdf')) !== FALSE ) {  return('application/pdf');  }


  // ----- Audio
  if( strposa($url, array('.mp3')) !== FALSE ) {  return('audio/mpeg3');  }
  if( strposa($url, array('.wav')) !== FALSE ) {  return('audio/wav');  }
  if( strposa($url, array('.m4a')) !== FALSE ) {  return('audio/mp4');  }
  if( strposa($url, array('.aac')) !== FALSE ) {  return('audio/mp4');  }
  if( strposa($url, array('.wma')) !== FALSE ) {  return('audio/x-ms-wma');  }
  if( strposa($url, array('.ogg')) !== FALSE ) {  return('audio/ogg');  }
  if( strposa($url, array('.oga')) !== FALSE ) {  return('audio/ogg');  }


  // ----- Video
  if( strposa($url, array('.m4v')) !== FALSE ) {  return('video/mp4');  }
  if( strposa($url, array('.mp4')) !== FALSE ) {  return('video/mp4');  }
  if( strposa($url, array('.wmv')) !== FALSE ) {  return('video/x-ms-wmv');  }
  if( strposa($url, array('.ogv')) !== FALSE ) {  return('video/ogg');  }
  if( strposa($url, array('.avi')) !== FALSE ) {  return('video/avi');  }
  if( strposa($url, array('.mov')) !== FALSE ) {  return('video/quicktime');  }


  //If none of those matched, see if there was a type hint
  if( !empty($type) ) {
    //Does this look like a good type already?
    if( strpos($type, '/') !== FALSE) {
      return($type);
    }
    //Is this a text type?
    if( strpos($type, 'text') !== FALSE) {
      return("text/plain");
    }

    //Guess not.  Let's assume that what's already
    //given is a primary type and just add x-unknown
    return($type."/x-unknown");
  }


  //Give up
  return("application/octet-stream");
}


//Determine what type of media a url points to based on the extension in the url
function url_is_media( $url = NULL )
{

  //Be clean
  $url = clean_url($url);

  //Pictures
  if( strposa($url, array('.jpg','.png','.jpeg','.gif')) !== FALSE ) {
    return('image');
  }
  //Audio
  if( strposa($url, array('.mp3','.m4a','.wav','.ogg','.wmv')) !== FALSE ) {
    return('audio');
  }
  //Video
  if( strposa($url, array('.m4v','.mp4','.avi','.mov')) !== FALSE ) {
    return('video');
  }

  return(FALSE);
}


//Determine if a url points to a picture file based on the extension
function url_is_a_picture( $url = NULL )
{

  if( strposa($url, array('.jpg','.png','.jpeg','.gif')) !== FALSE ) {
    return(TRUE);
  }

  return(FALSE);
}


//Determine if a url points to an audio file based on the extension
function url_is_audio( $url = NULL )
{

  if( strposa($url, array('.mp3','.m4a','.wav','.ogg','.wmv')) !== FALSE ) {
    return(TRUE);
  }

  return(FALSE);
}


//Determine if a url points to a video file based on the extension
function url_is_video( $url = NULL )
{

  if( strposa($url, array('.m4v','.mp4','.avi','.mov')) !== FALSE ) {
    return(TRUE);
  }

  return(FALSE);
}


//http://stackoverflow.com/questions/770219/how-can-i-remove-attributes-from-an-html-tag
//Strip attributes from an html tag
function stripAttributes($s, $allowedattr = array()) {
  if (preg_match_all("/<[^>]*\\s([^>]*)\\/*>/msiU", $s, $res, PREG_SET_ORDER)) {
   foreach ($res as $r) {
     $tag = $r[0];
     $attrs = array();
     preg_match_all("/\\s.*=(['\"]).*\\1/msiU", " " . $r[1], $split, PREG_SET_ORDER);
     foreach ($split as $spl) {
      $attrs[] = $spl[0];
     }
     $newattrs = array();
     foreach ($attrs as $a) {
      $tmp = explode("=", $a);
      if (trim($a) != "" && (!isset($tmp[1]) || (trim($tmp[0]) != "" && !in_array(strtolower(trim($tmp[0])), $allowedattr)))) {

      } else {
          $newattrs[] = $a;
      }
     }
     $attrs = implode(" ", $newattrs);
     $rpl = str_replace($r[1], $attrs, $tag);
     $s = str_replace($tag, $rpl, $s);
   }
  }
  return $s;
}


//http://stackoverflow.com/questions/5732758/detect-html-tags-in-a-string
//Detect whether a string contains html tags
function this_is_html( $string )
{
  if($string != strip_tags($string)) {
    return(TRUE);
  }
  return(FALSE);
}


//Get an external ip address for this server
function get_external_ip_address( $reflector_url = NULL )
{
  //Check params
  if( empty($reflector_url) ) {
    loggit(2, "The ip reflector url was blank or corrupt: [$reflector_url]");
    return(FALSE);
  }

  $data = fetchUrl($reflector_url);
  $ret = preg_match('/\d{1,3}.\d{1,3}.\d{1,3}.\d{1,3}/', $data, $ipaddr);

  if($ret === FALSE || $ret == 0) {
    return(FALSE);
  }

  return(trim($ipaddr[0]));
}


//Create a redirect file for S3 that sends external users to the external
//ip address of this host
function create_external_access_file( $ipaddr = NULL, $bucket = NULL, $filename = NULL )
{
    //Check params
    if( empty($ipaddr) ) {
      loggit(2, "The ip address was blank or corrupt: [$ipaddr]");
      return(FALSE);
    }
    if( empty($bucket) ) {
      loggit(2, "The bucket name was blank or corrupt: [$bucket]");
      return(FALSE);
    }
    if( empty($filename) ) {
      loggit(2, "The file name was blank or corrupt: [$filename]");
      return(FALSE);
    }

    //Make sure we have the library
    require_once "$confroot/$libraries/s3/S3.php";

    //Get system S3 info
    $s3info = get_sys_s3_info();

    //Create a redirect stub
    $file = create_short_url_file("http://".$ipaddr."/");
    $result = putInS3($file, $filename, $bucket, $s3info['key'], $s3info['secret'], "text/html");


    return($result);
}


//Set an amazon bucket to redirect to another location
function set_bucket_redirect($bucket = NULL, $location = NULL) {

  //Check parameters
  if( empty($location) ) {
    loggit(2, "Location missing from S3 redirect call: [$location].");
    return(FALSE);
  }
  if( empty($bucket) ) {
    loggit(2, "Bucket missing from S3 redirect call: [$bucket].");
    return(FALSE);
  }


  //Includes
  include get_cfg_var("cartulary_conf").'/includes/env.php';
  require_once "$confroot/$libraries/oauth/tmhOAuth.php";
  require_once "$confroot/$libraries/s3/S3.php";

  //Get system S3 info
  $s3info = get_sys_s3_info();

  //Set up
  $s3 = new S3($s3info['key'], $s3info['secret']);

  $s3res = $s3->setBucketRedirect($bucket, $location);
  if(!$s3res) {
       	loggit(3, "Could not create S3 bucket redirect: [$bucket -> $location].");
	return(FALSE);
  }

  //loggit(3, "Redirected S3 bucket: [$bucket] to [$location].");
  return(TRUE);
}


//_____________________________________________________________________
// Take an array of objects and build an rss feed from them
function convert_to_rss( $items = array() )
{

  //Root object
  $xml = new SimpleXMLElement('<rss version="2.0"></rss>');

  //Channel
  $xml->addChild('channel');

  //Channel attributes
  $xml->channel->addChild('title', 'title');
  $xml->channel->addChild('link', 'link');
  $xml->channel->addChild('description', 'description');
  $xml->channel->addChild('pubDate', 'pubDate');

  //Add the items
  foreach( $items as $item ) {
	//Create the item
	$item = $xml->channel->addChild('item');

	//Add item attributes
	$item->addChild('title', 'title');
	$item->addChild('link', 'link');
	$item->addChild('description', 'description');
	$item->addChild('pubDate', 'pubDate');
  }

  //Make the formatting pretty
  $dom = dom_import_simplexml($xml)->ownerDocument;
  $dom->formatOutput = true;
  $feed = $dom->saveXML();


  //Give back the feed as a string
  return($feed);
}


//http://www.php.net/manual/vote-note.php?id=69826&page=function.array-search&vote=up
//Search a multi-dimensional array and return info about it if found
function array_search_ext($arr, $search, $exact = true, $trav_keys = null)
{
  if(!is_array($arr) || !$search || ($trav_keys && !is_array($trav_keys))) return false;
  $res_arr = array();
  foreach($arr as $key => $val)
  {
    $used_keys = $trav_keys ? array_merge($trav_keys, array($key)) : array($key);
    if(($key === $search) || (!$exact && (strpos(strtolower($key), strtolower($search)) !== false))) $res_arr[] = array('type' => "key", 'hit' => $key, 'keys' => $used_keys, 'val' => $val);
    if(is_array($val) && ($children_res = array_search_ext($val, $search, $exact, $used_keys))) $res_arr = array_merge($res_arr, $children_res);
    else if(($val === $search) || (!$exact && (strpos(strtolower($val), strtolower($search)) !== false))) $res_arr[] = array('type' => "val", 'hit' => $val, 'keys' => $used_keys, 'val' => $val);
  }
  return $res_arr ? $res_arr : false;
}


?>
