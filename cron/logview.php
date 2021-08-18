<?php
$logfilename     = "logdatei.txt";
$logfilenametemp = "logdatei.temp";

$log_to  = "log/";
$logFile = "$log_to$logfilename";

$dateiname = $logFile;
$speichern = $_POST["speichern"];
$edit      = $_POST["edit"];
$do        = $_POST["do"];

if ($do == "Editieren") {
    $fp   = fopen("$dateiname", 'w');
    $edit = $edit . "";
    fputs($fp, $edit, strlen($edit));
    fclose($fp);

    $fp   = fopen("$dateiname", 'rb');
    $file = fread($fp, filesize("$dateiname"));
    fclose($fp);
}

if ($do == "Einlesen") {

    $fp   = fopen("$dateiname", 'rb');
    $file = fread($fp, filesize("$dateiname"));
    fclose($fp);
}

if ($do == "LoadOLD") {
    $Ofile = "$log_to$logfilenametemp";
    $fp    = fopen("$Ofile", 'rb');
    $file  = fread($fp, filesize("$Ofile"));
    fclose($fp);
}

if ( ! $do) {

    $fp   = fopen("$dateiname", 'rb');
    $file = fread($fp, filesize("$dateiname"));
    fclose($fp);
}

if ($do == "Save") {

    $oldfile = $dateiname;
    $newfile = "$log_to$logfilenametemp";

    if ( ! copy($oldfile, $newfile)) {
        echo "UPS das copy $file schlug fehl...\n";
    }
}

?>
<html lang="en-US">
<head>
    <title>LogViewer</title>
    <meta charset="utf-8">
    <script>
        function scrdown() {
            var elem = document.getElementById('view');
            elem.scrollTop = elem.scrollHeight;
        };
    </script>
</head>
<body onload="scrdown()">
<form action="<?php echo $PHP_SELF; ?>" method="post" onSubmit="return check()">
    <h3> Logdatei <?php echo $logfilename; ?> bearbeiten:</h3>
    <p>
        <textarea id="view" name="edit" cols="95" rows="50"><?php echo $file; ?></textarea>
        <input type="hidden" name="speichern" value="ok">
    <p><input type="submit" name="do" value="Save">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="do"
                                                                                              value="LoadOLD">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input
                type="submit" name="do" value="Editieren">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit"
                                                                                                     name="do"
                                                                                                     value="Einlesen">
    <p>
</form>

</body>
</html>
