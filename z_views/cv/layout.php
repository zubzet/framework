<?php 

    function smallDate($date) {
        return $date;

        $blocks = explode("-", $date);
        return $blocks[1] . "/" . $blocks[0];
    }

    function normalDate($date) {
        $blocks = explode("-", $date);
        return $blocks[2] . "/" . $blocks[1] . "/" . $blocks[0];        
    }

    function custombbcode($str) {

        //Trimming
        $str = ltrim($str);

        //BOLD Text
		$str = explode("**", $str);
		foreach ($str as $i => $element) {
			if ($i % 2 == 1) $str[$i] = "<b>$element</b>";
        }
        $str = implode("", $str);

        //Lists
        if (str_replace("-- ", "", $str) != $str) {
            $str = explode("\n", $str);
            $inlist = false;
            foreach ($str as $i => $line) {
                if (mb_substr($line, 0, 3) == "-- ") {
                    $str[$i] = substr($line, 3);
                    if($inlist) {
                        $str[$i] = "<li>" .str_replace('<br />', '', $str[$i]) . "</li>";
                    } else {
                        $str[$i] = "<ul><li>" .$str[$i]. "</li>";
                        $inlist = true;
                    }
                } else {
                    if($inlist) {
                        $str[$i] = "</ul>" .$str[$i];
                        $inlist = false;
                    } else {
                        $str[$i] .= "\n";
                    }
                }
            }
            $str = implode("", $str);
        }

        //New lines
        $str = nl2br($str);

        $side_padding = "10mm";

        return $str;
    }

    function layout($opt) { ?>

        <style>
            .category-heading {
                background-color: #BBD406; 
                margin-left: -<?php echo $side_padding ?>mm; 
                padding: 8px; 
                border-top-right-radius: 5px; 
                border-bottom-right-radius: 5px; 
                padding-left: 10mm; 
                width: 40%; 
                font-size: 12pt;
                font-weight: bold;
            }
            .category-heading-right {
                width: 40%; 
                border-top-left-radius: 5px; 
                border-bottom-left-radius: 5px; 
                background-color: #BBD406; 
                margin-right: -10mm; 
                padding: 8px;
                font-size: 12pt;
                font-weight: bold;
            }

        </style>

        <table style="width: 212mm; margin-left: -11mm;">
            <tr>
                <td style="width: 40%" class="category-heading">Contact Information</td>
                <td style="width: 20%"></td>
                <td style="width: 40%" class="category-heading-right">Personal Data</td>
            </tr>

            <tr>
                <td style="padding-left: 10mm">
                    <?php echo $opt["companyName"]; ?><br>
                    <?php echo $opt["personal_information"]["addr_street"] . " " . $opt["personal_information"]["addr_street_number"] ?><br>
                    <?php echo $opt["personal_information"]["addr_zip"] . " " . $opt["personal_information"]["addr_city"] ?><br>
                    <?php echo $opt["personal_information"]["addr_country"] ?><br>
                    <br>
                    <?php echo $opt["personal_information"]["email"] ?><br>
                    <?php echo $opt["personal_information"]["web"] ?><br>
                    <?php echo $opt["personal_information"]["tel"] ?><br>
                    <?php echo $opt["personal_information"]["mobil"] ?><br>
                    </td>
                <td></td>
                <td style="padding-left: 5mm">
                    Name: <?php echo $opt["user_information"]["firstName"] . " " . $opt["user_information"]["name"] ?><br>
                    Date of birth: <?php echo normalDate($opt["personal_information"]["birthdate"]) ?><br>
                    Nationality: <?php echo $opt["personal_information"]["nationality"] ?><br>
                    Expertise: ?<br>
                    Position: <?php echo $opt["personal_information"]["position"] ?><br>
                </td>
            </tr>
        </table>

        
        <div class="category-heading">Summary of Qualifications</div>

        <table cellpadding="4">
        <?php 
        if (count($opt["references"]) > 0) {
            foreach($opt["references"] as $i => $reference) { ?>
                    <tr>
                        <td width="22%" style="background-color: #dbdbdb; border: 1px solid black;"><p><b>Skill: <?php echo $reference["skillId"]; ?></b></p>                                                                                                                                            
                            <p><?php echo smallDate($reference["start"]); ?><?php echo ($reference["end"] != null ? " - " . smallDate($reference["end"]) : ""); ?></p>
                            <p><?php echo (($reference["client"] == null) ? "" : "Client: " .$reference["client"]. "<br><br>"); ?></p>
                            <p>Position: <?php echo $reference["position"]; ?></p>
                        </td>
                        <td style="border: 1px solid black; width: 78%;"><b><?php echo $reference["title"]; ?></b>
                            <br><br>
                            <?php echo custombbcode($reference["description"]); ?>
                        </td>
                    </tr>
                    <?php } ?>
            </table><?php echo ($i != @count($opt["references"]) - 1 ? "<br><br>" : ""); } ?>

        <br><br>

        <div class="category-heading">Professional History</div>
        
        <table cellpadding="4">
        <?php 
        if (count($opt["professional_history"]) > 0) {
            foreach($opt["professional_history"] as $i => $history) { ?>
                <tr>
                    <td width="22%" style="background-color: #dbdbdb; border: 1px solid black;"><p><b><?php echo ($history["start"] != null ? smallDate($history["start"]) : "START"); ?><?php echo ($history["end"] != null ? " - " . smallDate($reference["end"]) : " - TODAY"); ?></b></p>
                    </td>
                    <td style="border: 1px solid black; width: 78%;"><b><?php echo $history["title"]; ?></b>
                        <br><br><?php echo custombbcode($history["position"]); ?>
                    </td>
                </tr>
            <?php } ?>
        </table><?php echo ($i != count($opt["professional_history"]) - 1 ? "<br><br>" : ""); } ?>

        <div class="category-heading">Education</div>

        <table cellpadding="4">
        <?php 
        if (count($opt["education"]) > 0) {
            foreach($opt["education"] as $i => $education) { ?>
                <tr>
                    <td width="22%" style="background-color: #dbdbdb; border: 1px solid black;"><p><b><?php echo ($education["start"] === null ? "" : smallDate($education["start"]) . " - "); ?><?php echo ($education["graduation"] != null ?  smallDate($education["graduation"]) : "TODAY"); ?></b></p>
                    </td>
                    <td style="border: 1px solid black; width: 78%;"><b><?php echo $education["title"]; ?></b>
                        <br><br><?php echo custombbcode($education["description"]); ?>
                    </td>
                </tr>
            <?php } ?>
        </table><?php echo ($i != count($opt["education"]) - 1 ? "<br><br>" : ""); } ?>
        
        <!--<p style="font-size: 15pt">Relevant Projects</p>
        <p style="font-size: 15pt">Language Skills</p>
        <p style="font-size: 15pt">Software Skills</p>
        <p style="font-size: 15pt">Certifications</p>-->
    <?php }

?>