<?php

/*
 * 25.12.2016
 * This parser was designed and tested for the version 7.0 of the Tecalor Internet Service Gateway (ISG)
 * The goal was to read all available data that is provided by the rendered html website because there is no webservice
 * API to retrieve that data. The result is returned as JSON.
 *
 * There is nearly no errorhandling at all
 *
 * sorry for the dirty cooding style :)
 *
 * Use at your own risk
 *
 * For further information use https://github.com/np4me2k/TecalorISGParser
 *
 */


function sonderzeichen($string)
{
    $search = array("Ä", "Ö", "Ü", "ä", "ö", "ü", "ß", "´");
    $replace = array("Ae", "Oe", "Ue", "ae", "oe", "ue", "ss", "");
    return str_replace($search, $replace, $string);
}



/*
 * Change this to your local IP adress of your ISG
 */

$configuration = array(
    'srv_address' => 'http://192.168.178.52/?s=1,0'
);


$return = file_get_contents($configuration['srv_address']);

$start = strpos($return,"HEIZUNG</th></tr>");
$ende = strpos($return, "STATUS-OK</td>");

$relevanter_teil = trim(substr($return, ($start-38), $ende-$start-193+38));

$replaces = array(
    "<tr class=\"even\">", 
    "<tr class=\"odd\">", 
    "</tr>",
    "</div>", 
    "</table>",
    "<table class=\"info\">",
    "<tr>",
    "</td>",
    "<div class=\"span-11 append-1\" style=\"float:left\">",
    "<div class=\"span-11 prepend-1\" style=\"float:right\">",
    " round-leftbottom",
    " round-rightbottom",
    );

$relevanter_teil = str_replace ( $replaces , "" , $relevanter_teil);

$all_data = array();
$zeilen = explode("<th colspan=\"2\" class=\"round-top\">",$relevanter_teil);
$av_values = array();
foreach($zeilen as $zeile)
{
    $zeile = trim($zeile);

    //WO ist der Titel zu Ende?
    
    $ende_titel = strpos($zeile,"</th>");
    $gruppe = strtolower(sonderzeichen(substr($zeile,0, $ende_titel)));


    $group_string = substr($zeile,$ende_titel+9);
    $value_pairs = explode("<td class=\"key\">", $group_string);

    foreach($value_pairs as $row_value)
    {
        $row_value = trim($row_value);
        if(strlen($row_value)<10)
        {
            continue;
        }
        list($key, $value) = explode("<td class=\"value\">", $row_value); 

        $key = str_replace("." , "" , str_replace(" " , "_" , strtolower(trim(sonderzeichen($key)))));
        if(substr_count($value, " ") > 0)
        {
            list($value, $crap) = explode(" ", trim($value));        
        }
        else
        {
            $value = trim($value);
        }
        
        $value = str_replace(array(".",","), array(",","."), $value);   
        //echo($key." - ".$value);  

        $all_data[$gruppe."_".$key] = $value;
        array_push($av_values, $gruppe."_".$key); 
    }
    
}

header('Content-Type: application/json');

echo json_encode($all_data);


?>
