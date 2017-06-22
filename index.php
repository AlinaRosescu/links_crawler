<?php
set_time_limit(0);
?>
<!DOCTYPE html>
<html>
<head>
    <style rel="stylesheet">
        body {
            text-align: center;
        }
        div {
            background-color:#9f9f9f;
            border: 1px solid #40546a;
            -webkit-border-radius: 5px;
            -moz-border-radius: 5px;
            border-radius: 5px;
            padding: 10px;
            display: inline-block;
        }
        table{
            border-collapse: collapse;
            margin: 0 auto;
        }
        th {
            font-size: 18px;
            background-color:#40546a;
            color: #ffffff;
        }
        tr, td {
            padding: 5px;
            border: 1px solid #9f9f9f;
        }
    </style>
</head>
<body>

<?php
if(!empty($_POST['submit'])) {
    if( isset($_FILES['csvFile'])) {
        $uploadedFile = $_FILES['csvFile']['tmp_name'];
        $file = fopen($uploadedFile,"r");
        $results= [];

        //patterns to search for
        $patternSpan = "/Aucun résultat trouvé./";
        $patternDiv = " n'ayant donné aucun résultat.";
    ?>
    <table>
        <tbody>
        <tr>
            <th>Nr.</th>
            <th>Link</th>
            <th>Result</th>
        </tr>
        <?php
        $count = 0;
        $result = true;
        while (($line = fgetcsv($file)) !== FALSE) {
            echo "<tr>";
            //go through the csv file
            foreach ($line as $key => $value) {
                if (!empty($value)) {
                    $count++;

                    echo "<td>" . $count . "</td>";
                    echo "<td style='text-align:left;'>" . utf8_encode($value) . "</td>";

                    //check if there is a redirect in header
                    $headers = get_headers($value);
                    foreach ($headers as $header) {
                        if (preg_match('/^Location:\s(.*)/', $header, $out)) {
                            //overwrite value with the redirect location
                            $patternRedirect1 = "/^\/pharmacie/";
                            if (preg_match($patternRedirect1, $out[1])) {
                                $value = str_replace("/pharmacie", "http://www.newpharma.be/pharmacie", $out[1]);
                            }
                            $patternRedirect2 = "/\/\/www./";
                            if (preg_match($patternRedirect2, $out[1])) {
                                $value = str_replace("//www.newpharma", "http://www.newpharma", $out[1]);
                            }
                        }
                    }
                    //create dom file
                    $html = file_get_contents($value);
                    $dom = new DOMDocument;
                    @$dom->loadHTML($html);

                    //get span elements from dom
                    $items = $dom->getElementsByTagName('span');
                    for ($i = 0; $i < $items->length; $i++) {
                        $span = html_entity_decode($items->item($i)->nodeValue);
                        //check if span matches "Aucun résultat trouvé."
                        if (preg_match($patternSpan, $span)) {
                            $result = false;
                            array_push($results, $value);
                            echo "<td>" . $span;
                        }
                    }

                    //get div elements with class name "noresults"
                    $className = "noresults";
                    $finder = new DomXPath($dom);
                    $div = $finder->query("//*[contains(@class, '$className')]");
                    //if div is found push to array
                    if ($div->length == 1) {
                        $result = false;
                        parse_str(parse_url($value, PHP_URL_QUERY), $output);
                        if(in_array($value, $results)) {
                            echo  "<br>" . $output['noresults'] . $patternDiv . "</td>";
                        } else {
                            echo  "<td>" . $output['noresults'] . $patternDiv .  "</td>";
                            array_push($results, $value);
                        }
                    }
                    if($result == true) {
                        echo "<td>OK</td>";
                    }
                    $result = true;
                }
            }
            echo "</tr>";
        }
        //write array into csv file
        $newfile = fopen("results/results.csv", 'a');
        fputcsv($newfile, $results, "\n");
        fclose($file);
        ?>
        </tbody>
    </table>
    <?php }
        } else {
    ?>
    <div>
        <form action="" method="post" enctype="multipart/form-data">
            <label style="padding-right:20px;">Select file to upload:</label>
            <input type="file" name="csvFile" id="fileToUpload">
            <input style="display:block; margin-top:10px;" type="submit" value="Upload" name="submit">
        </form>
    </div>
    <?php
        }
    ?>
</body>
</html>