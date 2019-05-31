<?php
/**
 * xhprof性能报告列表
 *
 * @package    
 * @copyright  	copyright(2011) weibo.com all rights reserved
 * @author     	hqlong <qinglong@staff.sina.com.cn> baojun <baojun4545@sina.com>
 * @version    	2011-3-28
 */

$output_dir = ini_get('xhprof.output_dir');
$dirs = new DirectoryIterator($output_dir);
$all_profile = array();
$edition = array();
if (empty($dirs)) {
    exit("No performance report");
}
foreach ($dirs as $file) {
    $file_name = $file->getFilename();
    if (in_array($file_name, array('.', '..'))) {
        continue;
    }
    list ($pid, $source) = explode(".", $file_name);
    $profile = array('pid' => $pid, 'source' => $source);
    $all_profile[$pid] = $profile;
    array_push($edition, $pid);
}
array_multisort($edition, SORT_DESC, $all_profile);
?>
<html>
<title>Performance Reprot list</title>
<script>
function get_checked_list() {
    var items = document.getElementsByTagName("li");
    var list =[];
    for (var i = 0; i < items.length; i++) {
        var obj = items[i].getElementsByTagName("input")[0];
        if (obj.checked) list.push(obj.value);
    }
    return list;
}
function diff() {
    var list = get_checked_list();
    if (list.length == 0) {
        alert("Please select item");
        return false;
    }
    var run_ids = [];
    for(var i = 1; i <= list.length; i++) {
        run_ids.push("run"+i+"="+encodeURIComponent(list[i-1]));
    }
    var url = "index.php?" + run_ids.join("&");
    window.open(url);
}

function Aggregate() {
    var list = get_checked_list();
    if (list.length == 0) {
        alert("Please select item");
        return false;
    }
    var run_ids = [];
    for(var i = 0; i < list.length; i++) {
        run_ids.push(encodeURIComponent(list[i]));
    }
    var url = "index.php?run=" + run_ids.join(",");
    window.open(url);
}
</script>
<body>
<div>
<?php
foreach ($all_profile as $profile) :
    ?>
<li><input type="checkbox"
	value="<?php
    echo $profile['pid'];
    ?>" /><a
	href="index.php?run=<?php
    echo $profile['pid'];
    ?>&source=<?php
    echo $profile['source'];
    ?>"
	target="__blank"><?php
    echo $profile['pid'];
    ?></a></li>
<?php
endforeach
;
?>
</div>
<div><input type="button" value="diff" onclick="diff()" />&nbsp;&nbsp;<input
	type="button" value="Aggregate" onclick="Aggregate()" /></div>
</body>
</html>
