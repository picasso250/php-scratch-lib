<?php

// functions 
// by xc
// version 6.7

/** wrap cURL to get html
 * array(url, refer, cookie)
 */
function fetch_html($info_arr, $post='', $need_conv=0) {
    extract($info_arr);
    if (0 && there_is_cache($url)) {
        return get_cache_content($url);
    }

    // else we will fetch from internet
    $ch = curl_init();
    if (false === $ch)
        die('curl init error');
    safe_curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    safe_curl_setopt($ch, CURLOPT_URL, $url);
    safe_curl_setopt($ch, CURLOPT_HEADER, false);
    $user_agent = 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.4 (KHTML, like Gecko) Chrome/22.0.1229.92 Safari/537.4';
    safe_curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    safe_curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    if (isset($refer))
        safe_curl_setopt($ch, CURLOPT_REFERER, $refer);
    if (isset($cookie))
        safe_curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    safe_curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); // 30秒钟的等待
    safe_curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30秒钟的等待
    if ($post) {
        safe_curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'application/x-www-form-urlencoded; charset=UTF-8',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'X-AjaxPro-Method: GetLabelInnerHtml',
            'X-MicrosoftAjax: Delta=true' // fuck!! for aspx
        ));
        safe_curl_setopt($ch, CURLOPT_POST, 1);
        safe_curl_setopt($ch, CURLOPT_POSTFIELDS,  ($post));
        //safe_curl_setopt($ch,CURLOPT_POSTFIELDS,  json_encode($post));
    }
    if (isset($proxy)) {
        safe_curl_setopt($ch, CURLOPT_PROXY, $proxy);
    }
    $output = curl_exec($ch);
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        echo "when fetch url: $url", PHP_EOL;
        throw new Exception('Curl error: ' . $error);
    }
    curl_close($ch);
    if ($need_conv) {
        $output = iconv( "gb2312 ", "UTF-8 ",$output); // ?
    }
    cache_html($url, $output);
    return $output;
}

function get_cache_root() {
    $root = 'cache/';
    ensure_dir($root);
    return $root;
}

function fgetr($file) {
    if (!file_exists($file)) {
        touch($file); 
        return array();
    } else {
        $c = file_get_contents($file);
        if (false === $c) {
            throw new Exception;
        }
        if ('' === $c) {
            return array();
        } else {
            $arr = unserialize($c);
            if (false === $arr) {
                throw new Exception;
            }
            return $arr;
        }
    }
}

function there_is_cache($url) {
    $info = fgetr(get_cache_root() . '.info');
    return isset($info[$url]);
}

function cache_html($url, $content) {
    // question is, we need delete some file
    // delete who?
    $info_file = get_cache_root() . '.info';
    $info = fgetr($info_file);

    // empty $info array and delete file
    if (count($info) > 100) {
        foreach ($info as $url=>$fname) {
            // delete
            $r = unlink(get_cache_root() . $fname);
            if (false === $r) {
                throw new Exception();
            }
        }
        $info = array();
    }

    $fname = uniqid(); //time stamp??
    $info[$url] = $fname;
    $r = file_put_contents(get_cache_root() . $fname, $content);
    if (false === $r) {
        throw new Exception;
    }
    // store $info back
    $r = fputr($info_file, $info);
    if (false === $r) {
        throw new Exception;
    }
}

function fputr($info_file, $info) {
    $c = serialize($info);
    $r = file_put_contents($info_file, $c);
    if (false === $r) {
        throw new Exception;
    }
    return $r;
}

function get_cache_content($url) {
    $fname = get_cache_filename($url);
    if (false === $fname) {
        return false;
    }
    $c = file_get_contents($fname);
    if (false === $c) {
        throw new Exception;
    }
    return $c;
}

function get_cache_filename($url) {
    $info = fgetr(get_cache_root() . '.info');
    if (isset($info[$url])) {
        return $info[$url];
    } else {
        return false;
    }
}

function safe_curl_setopt($ch, $opt_name, $opt_value) {
    $r = curl_setopt($ch, $opt_name, $opt_value);
    if (false === $r)
        die("curl set error when set $opt_name");
}

/** 将数据写入缓存文件
 *
 */
function write_cache(&$data, $file){
    return file_put_contents($file, serialize($data)) ;
}

function read_cache($file) {
    $c = file_get_contents($file);
    if (false === $c) {
        die(" get $file");
    }
    return unserialize($c);
}

// version 6.7
function d($arr) {
    if (is_array($arr) || is_object($arr)) {
        print_r($arr);
    } else if (is_bool($arr) || empty($arr)) {
        var_dump($arr);
    } else {
        echo "$arr\n";
    }
}

// dom manipulation
function getByClass(&$dom_list, $class_name) {
    foreach ($dom_list as $elem) {
        if ($elem->hasAttribute('class')) {
            $classes = $elem->getAttribute('class');
            if (in_array($class_name, explode(' ', $classes))) {
                return $elem;
            }
        }
    }
    return false;
}

function getMultiByClass(&$dom_list, $class_name) {
    $ret = array();
    foreach ($dom_list as $elem) {
        if ($elem->hasAttribute('class')) {
            $classes = $elem->getAttribute('class');
            if (in_array($class_name, explode(' ', $classes))) {
                $ret[] = $elem;
            }
        }
    }
    return $ret;
}

function getByTagClass(&$elem, $tag_name, $class_name) {
    $dom_list = $elem->getElementsByTagName($tag_name);
    return getByClass($dom_list, $class_name);
}

function getByTag(&$elem, $tag) {
    return $elem->getElementsByTagName($tag)->item(0);
}

function appendCsvLine($file, $arr) {
    $str = arr2csvl($arr);
    return fwrite($file, $str);
}

function appendCsv($file_name, $arr) {
    $f = fopen($file_name, 'a');
    foreach ($arr as $v) {
        if (appendCsvLine($f, $v) === false) {
            fclose($f);
            return false;
        }
    }
    fclose($f);
    return 1;
}

function arr2csvl($arr) {
    return implode(',', array_map(function ($v) {
        return '"'.$v.'"';
    }, $arr))."\n";
}

function ensure_dir($dir) {
    if (!file_exists($dir)) {
        if (false === mkdir($dir)) {
            die("mkdir $dir");
        }
    }
    return true;
}
