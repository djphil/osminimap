<?php
$config = './inc/config.php';
if (file_exists($config) AND filesize($config ) > 0) require_once("./inc/config.php");
else echo "The file ".$config." have a problem ...";

function GetValue($varname, $min, $max, $default)
{
    $result = $default;
    if (isset($_GET[$varname]))
        $result = $_GET[$varname];
    if ($result < $min)
        $result=$min;
    // increase non-power-of-2 to next biggest power of 2
    $result = (($result - ($j = pow(2, ((int)(log($result)/log(2))))))?$j << 1 : $j);
    return ($result>$max) ? $max : $result;
}

$resolution = GetValue("resolution", 2, 128, 8);
$height = GetValue("size", 256, 4096, 512);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="description" content="<?php echo $osminimap.' v'.$version; ?>">
    <meta name="author" content="Philippe Lemaire (djphil)">
    <link rel="icon" href="img/favicon.ico">
    <link rel="author" href="inc/humans.txt" />

    <title><?php echo $osminimap.' v'.$version; ?></title>

    <!-- Bootstrap core CSS -->
    <!-- <link href="css/bootstrap.css" rel="stylesheet"> -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <link href="css/ie10-viewport-bug-workaround.css" rel="stylesheet">

    <link rel="stylesheet" href="css/gh-fork-ribbon.min.css" />
    <link href="css/minimap.css" rel="stylesheet">

    <!--[if lt IE 9]>
        <link rel="stylesheet" href="css/gh-fork-ribbon.ie.min.css" />
    <![endif]-->
</head>

<body>
<div class="github-fork-ribbon-wrapper left">
    <div class="github-fork-ribbon">
        <a href="https://github.com/djphil/osminimap" target="_blank">Fork me on GitHub</a>
    </div>
</div>

<div align="center">
    <h1><?php echo $osminimap.' v'.$version; ?></h1>

    <?php echo "<img id=\"quickmap\" src=\"inc/minimap.php?resolution=$resolution&size=$height\" width=\"$height\" height=\"$height\" border\"=0\" usemap=\"#mapcoords\" onMouseover=\"tip('CCCCCC', 'FFFFFF')\" onMouseout=\"hidetip()\" onMousemove=\"movetip()\"/>"; ?>

    <map id="mapcoords" name="mapcoords">
    <?php
    $imagemap = "";
    $min  = $CoordoXY - ($height/$resolution/2);
    $max  = $min + ($height/$resolution);
    $low  = $min * 256;
    $high = $max * 256;
    $link = @mysqli_connect($hostname, $username, $password, $database) or die ("Error: could not connect to database service");

    if (mysqli_connect_errno())
    {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }

    $result = mysqli_query($link, "
        select regionName, 
        cast(locX/256 as unsigned), 
        cast(locY/256 as unsigned) 
        from regions 
        where locX >= $low 
        and locY >= $low 
        and locX <= $high 
        and locY <= $high 
        order by regionName
    ");

    if (!$result)
    {
        echo "Error obtaining data from database.\n";
        exit;
    }

    $lines = mysqli_num_rows($result);

    for ($i = 0; $i < $lines; ++$i)
    {
        list($RegionName, $x, $y, $host) = mysqli_fetch_row($result);

        $x1 = ($x - $min) * $resolution;
        $y1 = $height - (($y - $min) * $resolution);
        $x2 = $x1 + $resolution - 1;
        $y2 = $y1 + $resolution - 1;
        $gridx = $min + ($x / $resolution);
        $gridy = $min + ($y / $resolution);
        $html = htmlentities($RegionName, ENT_QUOTES);
        $slashes = addslashes($RegionName);
        $imagemap .= "<area shape=\"rect\" coords=\"$x1, $y1, $x2, $y2\" onMouseover=\"tip('<b>$slashes</b> (X: $x, Y: $y)')\" onMouseout=\"hidetip()\" />\n";
    }

    echo $imagemap;
    mysqli_free_result($result);
    mysqli_close($link);
    ?>
    </map>

    <ul class="list-inline">
        <br /><span class="text-white">Résolutions:</span>
        <li><a class="btn btn-primary btn-xs" href="./">Default</a></li>
        <li><a class="btn btn-default btn-xs" href="?resolution=2">2</a></li>
        <li><a class="btn btn-default btn-xs" href="?resolution=4">4</a></li>
        <li><a class="btn btn-default btn-xs" href="?resolution=8">8</a></li>
        <li><a class="btn btn-default btn-xs" href="?resolution=16">16</a></li>
        <li><a class="btn btn-default btn-xs" href="?resolution=32">32</a></li>
        <li><a class="btn btn-default btn-xs" href="?resolution=64">64</a></li>
        <li><a class="btn btn-default btn-xs" href="?resolution=128">128</a></li>
    </ul>

    <footer class="footer">
        <?php echo $osminimap.' v'.$version; ?> by djphil (CC-BY-NC-SA 4.0)
    </footer>
</div>

<div id="tooltip"></div>

<script language=Javascript src="js/minimap.js"></script>
<script type="text/javascript">
function movetip()
{
    if (enabletip)
    {
        var thex = Math.floor((savex-mapleft) / <?php echo $resolution; ?>) + <?php echo $min; ?>;
        var they = <?php echo $max; ?> - (Math.floor((savey-maptop) / <?php echo $resolution; ?>));
        tipobj.innerHTML = thex + "," + they;
    }
}
</script>

</body>
</html>