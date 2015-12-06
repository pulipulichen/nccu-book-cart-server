<?php

include_once 'config.php';
include_once 'utils.php';
include_once 'lib/rb.config.php';

header('Content-Type: application/json; charset=utf-8');


// --------------------------------
// 如果沒有資料
if (isset($_GET["isbn"]) === FALSE) {
    $json = json_encode(array(
        "error" => "NO_ISBN"
            ), JSON_UNESCAPED_UNICODE);
    echo $json;
    book_list_log($json);
    exit();
}

$isbn = $_GET["isbn"];

//$isbn = 9789862168370;
//2147483647
//$isbn = "http://jenda.lib.nccu.edu.tw/search~S5*cht?/X{u8CC8}{u4F2F}{u65AF}{u50B3}&SORT=D/X{u8CC8}{u4F2F}{u65AF}{u50B3}&SORT=D&SUBKEY=賈伯斯傳/1%2C29%2C29%2CB/frameset&FF=X{u8CC8}{u4F2F}{u65AF}{u50B3}";
//$isbn = "http://jenda.lib.nccu.edu.tw/search~S5*cht?/X{u8CC8}{u4F2F}{u65AF}{u50B3}&SORT=D/X{u8CC8}{u4F2F}{u65AF}{u50B3}&SORT=D&SUBKEY=賈伯斯傳/1%2C29%2C29%2CB/frameset&FF=X{u8CC8}{u4F2F}{u65AF}{u50B3}&SORT=D&1%2C1%2C#.VmP-GxkVHqA";
//$isbn = "http://jenda.lib.nccu.edu.tw/search~S5*cht?/X&SORT=D/X";
//
//$isbn = "http://jenda.lib.nccu.edu.tw/search~S5*cht?/XWord+2007&SORT=D/XWord+2007&SORT=D&SUBKEY=Word+2007/1%2C90%2C90%2CB/frameset&FF=XWord+2007&SORT=D&2%2C2%2C#.VmQxx7iGSko";
//$isbn = "http://jenda.lib.nccu.edu.tw/search~S5*cht?/dWORD%28{u96FB}{u8166}{u7A0B}{u5F0F}%29/dword+{215f55}{215365}{214f35}{213d39}/-3%2C-1%2C0%2CB/frameset&FF=dwordless+picture+book&1%2C1%2C#.VmQzEbiGSko";
//$isbn = "http://jenda.lib.nccu.edu.tw/search~S5*cht?/dCommunism+--+Social+aspects+--+Soviet+Union+--+Hi/dcommunism+social+aspects+soviet+union+history/-3%2C-1%2C0%2CB/frameset&FF=dcommunism+soviet+union+addresses+essays+lectures&1%2C1%2C#.VmQzmbiGSko";
//$isbn = "http://jenda.lib.nccu.edu.tw/search~S5*cht?/XWord+2013&SORT=D/XWord+2013&SORT=D&SUBKEY=Word+2013/1%2C42%2C42%2CB/frameset&FF=XWord+2013&SORT=D&1%2C1%2C#.VmQyCLiGSko";
//echo $isbn;
// ---------------------
// 先取得快取
$result = R::find("cache_query_result", 'isbn = ? AND timestamp > ?'
                , array(
            $isbn,
            time() - $CONFIG["cache_sec"]
        ));

//echo time()-100;
if (($CONFIG["cache_sec"] !== 0) && count($result) > 0) {
    $json = "";
    foreach ($result as $r) {
        $json = "" . $r->json;
        //echo "/*" . $r->timestamp . "*/";
    }
    echo $json;
    book_list_log($json);

    exit();
}
//echo count($result);
//exit();
// ----------------------
// 準備開始查詢
// 不管什麼查詢，都先睡3秒鐘，以免過渡頻繁的查詢
sleep($CONFIG["request_wait_sec"]);

//$url = "http://jenda.lib.nccu.edu.tw/search~S5*cht/?searchtype=i&searcharg=" . $isbn  . "&searchscope=5&sortdropdown=-&SORT=DZ&extended=0&SUBMIT=%E6%9F%A5%E8%A9%A2&availlim=1&searchlimits=&searchorigarg=X%7Bu8CC8%7D%7Bu4F2F%7D%7Bu65AF%7D%7Bu50B3%7D%26SORT%3DD#.Vk6H3HYrLRY";
if (substr($isbn, 0, 4) !== "http") {
    // 如果開頭是網址，那就讓他保留網址的樣子
    $url = "http://jenda.lib.nccu.edu.tw/search~S5*cht/?searchtype=i&searcharg=" . $isbn . "&searchscope=5&sortdropdown=-&SORT=DZ&extended=0&SUBMIT=%E6%9F%A5%E8%A9%A2&searchlimits=&searchorigarg=X%7Bu8CC8%7D%7Bu4F2F%7D%7Bu65AF%7D%7Bu50B3%7D%26SORT%3DD#.Vk6H3HYrLRY";
} else {
    $url = $isbn;
//    $url = preg_replace_callback('/{u([0-9a-fA-F]{4})}/', function ($match) {
//        return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
//    }, $url);
    $url = urlencode($url);
}

// 測試檔案
//$url = "query_test/found_book_link.html";
//$url = "query_test/found_book_available.html";
//$url = "query_test/found_book_multi_available.html";
//$url = "query_test/isbn_not_found.html";
//$url = "query_test/found_book_not_available.html";
//echo $url;
//$content = file_get_contents($url);
//echo $content;
//exit();
// --------------------------------------------------

require 'lib/querypath/src/qp.php';

$qp = htmlqp($url);
//echo $url;
//echo $qp->html();

if ($qp->find('.msg td:contains("無查獲符合查詢條件的館藏;相近 國際標準號碼 是:")')->size() > 0 || $qp->find('.msg td:contains("無查獲符合的,可用相近 國際標準號碼 的是:")')->size() > 0) {

    // ---------------------------------------------
    // isbn_not_found
    // ---------------------------------------------


    $data = array(
        "error" => "NOT_FOUND"
    );
}   //if (htmlqp($url, '.msg td:contains("無查獲符合查詢條件的館藏;相近 國際標準號碼 是:")')->size() > 0) {
else if ($qp->find('.bibItemsEntry td:contains("可流通")')->size() === 0) {

    // ---------------------------------------------
    // found_book_not_available
    // ---------------------------------------------

    $full_title = $qp->find('.bibInfoLabel:contains("題名/作者")')->eq(0)->next()->find("strong:first")->text();
    $title = substr($full_title, 0, strpos($full_title, " / "));
    $title = trim($title);

    $data = array(
        "error" => "NOT_AVAILABLE",
        "title" => $title
    );
}   // else if ($qp->find('.bibItemsEntry td:contains("可流通")')->size() === 0) {
else {

    // ---------------------------------------------
    // found_book_available
    // ---------------------------------------------

    $data = array();

    $author = $qp->find('.bibInfoLabel:contains("作者")')->eq(0)->next()->text();
//echo $author;

    $full_title = $qp->find('.bibInfoLabel:contains("題名/作者")')->eq(0)->next()->find("strong:first")->text();
    $title = substr($full_title, 0, strpos($full_title, " / "));
    $title = trim($title);
//echo $title;
    $isbn = $qp->find('.bibInfoLabel:contains("國際標準書號")')->eq(0)->next()->text();
    $isbn = substr($isbn, 0, strpos($isbn, " : "));
    $isbn = trim($isbn);
    $isbn = floatval($isbn);

    // 處理圖片的部分
    $img_base64 = NULL;
    $img = $qp->find('#qrcode')->prev()->find("img");
    if ($img->size() > 0) {
        $img = $img->attr("src");
        $img = str_replace("/large", "/small", $img);
        $img_file = file_get_contents($img);
        $img_base64 = 'data:image/image/jpeg;base64,' . base64_encode($img_file);

        $null_image = array(
            "data:image/image/jpeg;base64,/9j/4AAQSkZJRgABAgAAZABkAAD/7AARRHVja3kAAQAEAAAAPAAA/+4ADkFkb2JlAGTAAAAAAf/bAIQABgQEBAUEBgUFBgkGBQYJCwgGBggLDAoKCwoKDBAMDAwMDAwQDA4PEA8ODBMTFBQTExwbGxscHx8fHx8fHx8fHwEHBwcNDA0YEBAYGhURFRofHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8fHx8f/8AAEQgAagBQAwERAAIRAQMRAf/EAIgAAQACAgMBAAAAAAAAAAAAAAADBQQGAQIHCAEBAQEBAQAAAAAAAAAAAAAAAAECAwQQAAEDAgIFBgsIAwAAAAAAAAEAAgMRBBIFITETFQZBUXEiMhRhkdGSssIzc6M0NYHBQnKCI2NURVUWEQEBAQABBQEBAAAAAAAAAAAAARECITFREgOhgf/aAAwDAQACEQMRAD8A+qUBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQVeZZzLaXTbaG1Nw/Z7RxxhgALi0a+hZvLFxi/9FmH+u+MzyKe18GORxDmJ/wAd8dnkT2vgwfxFfRsMkuX4Y26XuEzCQOgBPe+DF4xwc0OGoraOUBAQEBBoPHxIzu1odcHrFcPr3a4qaDL764YXwQySNBoS0EiqxJVS7mzb+rN5rlfWmorMETkGtQ14IP5SpB6la+wZ0L0xhKqCAgICDQOP/rdp7j1iuH17tcXHBxO85RybF3ptT591pmsOcw5nPeRNkEMb9o19ThoNPPqTlukVNq4uuCTrLXk+aViD1G0+XZ0L0xhKqCAgICDQOP8A63ae49Yrh9e7XF14O+py+4d6bU+fdaws8e/et03EcOM6K6Fnl3IxrP2/6H+iVIPUrT5dnQvTGEqoICAgINM42yu4uMxt7iItIbEWFpND2ifvXH6Tq1Ko4bLNIHF8LjE8ihcx5aac1QsZVdZMuzCR5fJ13u0uc5xJJ8JKZTUlrll42YdVukFva1YhSupJDXpNu0thaDrAXpjCRAQEGNNmNjDdRWkszWXM/sozrd0eJTYJ45WSBxYa4XFrulughUYt5ZtlkEjh2QpYMS0hsryLbWz2yx1LcbdVRrCkyqn3YzmTBy3LWg6uQjxhMRnhaBAQEFLn+TnM3GNtWSsiL7acfgma8FunkWOXHVlVcMubOiy+TMYp4bZ77k37ImyB20pSMkM62EnS2miv2LM3pqr3h7eG5bXeOLvmE7TH2u0cOKvLhpVb47nVK16CwzDulhHsriMHM5DO1u0Z+yXE4nUp1fCucl/Vd4oc4iex7RdVjzkwsBMrh3E15DUFmntHxq9f0R27c5OZWmMXJvG3FybwnamADCdj/Hh5qKTdGNbOzF13BEx0+9O53LpW3BfTvNThLcfVrzYdGpSb/RsXDTZxCTKbnaGOMTR3DJGhsjQQ4gyFxcXcpb1V04JV0toICAgICAgIII7C1juHXLWkzuBbtHuc8hpNcLcROEV5ApgnVBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBB//9k=",
            "data:image/image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAUDBAQEAwUEBAQFBQUGBwwIBwcHBw8LCwkMEQ8SEhEPERETFhwXExQaFRERGCEYGh0dHx8fExciJCIeJBweHx7/wAALCAAwADABAREA/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/9oACAEBAAA/APsuiiijI9aKRwShAOCR19KwNHXUfN0tri4ndfs8pmDH7zbhjP5/pVyQDzW4m+8f5n/P0qNsbOBJ0/i/D9f/AK9R1q2P/Hon0pbuXykHylt3HBxisu1jS3e3ZWu38hGRQ8xIbJzluOTS+esjyOpk2qw3ZbpliP55/AUxZVkjjI8396SsYYjllIBz6cjr9al8olFK5MjOU2HA5Gc8/hWhYkG0QrnGO9Rapjy0zj73fHp71Q+X/Z/So4SHudhURKzqCR9WIP5qD9Wo0+RDDuaGIKFwEPCDc5BPPQfKGP8AvCpBGMJIYrXYZWG4/dPB7+nHH4VrWjiS2RwgQFeFHQfT2qUgHrSEDBwBntxWOn9slELwwk5xJlRyOOnP1qay/tRiDcxRL83zcDkfhWkVBXaQMelLRRRRRRX/2Q=="
        );
        //echo $img_base64;exit();


        if (in_array($img_base64, $null_image) !== FALSE) {
            $img_base64 = NULL;
        }
    }

    $available_td_list = $qp->find('.bibItemsEntry td:contains("可流通")');
    for ($i = 0; $i < $available_td_list->size(); $i++) {
        $call_number = $available_td_list->eq($i)->prev()->text();
        $call_number = trim($call_number);

        $location = $available_td_list->eq($i)->prev()->prev()->text();
        $location = trim($location);
        $data[] = array(
//            "title" => urlencode($title),
//            "call_number" => urlencode($call_number),
//            "location" => urlencode($location),
//            "isbn" => urlencode($isbn)
            "title" => $title,
            "call_number" => $call_number,
            "location" => $location,
            "isbn" => $isbn,
            "img" => $img_base64
        );
    }

//    $data = array(
//        "error" => "NOT_FOUND"
//    );
}   // if (htmlqp($url, '.bibItemsEntry td:contains("可流通")')->size() > 0) {
// ---------------------------
// 轉換
$json = json_encode($data, JSON_UNESCAPED_UNICODE);
//$json = json_encode($data);
// ---------------------------
// 備份快取資料
$result = R::findOrCreate('cache_query_result', [
            'isbn' => $isbn
        ]);
$result->isbn = $isbn;

$result->json = $json;
$result->timestamp = time();
R::store($result);


echo $json;
book_list_log($json);
