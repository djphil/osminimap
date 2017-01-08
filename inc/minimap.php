<?php
    require_once("config.php");

    $resolution = GetValue("resolution", 2, 128, 8);
    $height     = GetValue("size", 256, 4096, 1024);
    $min        = $CoordoXY - ($height/$resolution/2);
    $max        = $min + ($height/$resolution);
    $low        = $min * 256;
    $high       = $max * 256;	

	$link = @mysqli_connect($hostname, $username, $password, $database) or die ("Error: could not connect to database service");
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
    
	if (!$result) exit;

    $lines = mysqli_num_rows($result);

    $out    = imagecreate($height, $height);
    $black  = imagecolorallocate($out, 0, 32, 32);
    // $ocean  = imagecolorallocate($out, 0, 0, 128);
    $ocean  = imagecolorallocate($out, 29, 71, 96);
    $plaza  = imagecolorallocate($out, 0, 255, 0);
    $region = imagecolorallocate($out, 0, 128, 0);

    imagefilledrectangle($out, 0, 0, $height, $height, $black);

    for ($x = 0; $x < $height; $x += $resolution)
        for ($y = 0; $y < $height; $y += $resolution)
        {
            $x2 = $x + $resolution - 2;
            $y2 = $y + $resolution - 2;
            $gridx = $min + ($x / $resolution);
            $gridy = $min + ($y / $resolution);
            imagefilledrectangle($out, $x, $y, $x2, $y2, $ocean);
        }

    for ($i = 0; $i < $lines; ++$i)
    {
        list($RegionName, $x, $y, $host) = mysqli_fetch_row($result);	
        $thecolor = preg_match("/$highname/", $RegionName) ? $plaza : $region;
        $x1 = ($x - $min) * $resolution;
        $y1 = $height - (($y - $min) * $resolution);
        $x2 = $x1 + $resolution - 2;
        $y2 = $y1 + $resolution - 2;
        imagefilledrectangle($out, $x1, $y1, $x2, $y2, $thecolor);
    }
    header( "Content-type: image/png" );
    imagepng($out);
    imagedestroy($out);

    function GetValue($varname, $min, $max, $default)
    {
        $result = $default;
        if (isset($_GET[$varname]))
            $result = $_GET[$varname];
        if ($result < $min)
            $result=$min;
        // increase non-power-of-2 to next biggest power of 2
        $result = (($result-($j = pow(2, ((int)(log($result)/log(2))))))?$j<<1:$j);
        return ($result > $max) ? $max : $result;
    }

    mysqli_free_result($result);
    mysqli_close($link);
?>