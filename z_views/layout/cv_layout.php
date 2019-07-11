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
        return $str;

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

        return $str;
    }

    function layout($opt) { 
        $side_padding = "20";
        ?>


        <style type="text/css">

            .category-heading {
                background-color: #BBD406; 
                margin-left: -<?php echo $side_padding ?>mm; 
                padding: 2mm;
                border-top-right-radius: 2mm; 
                border-bottom-right-radius: 2mm; 
                padding-left: <?php echo $side_padding ?>mm; 
                width: 60mm;
                font-size: 12pt;
                font-weight: bold;
                margin-bottom: 3mm;
                margin-top: 4mm;
            }

            .category-table {
                width: <?php echo (212 - (2 * $side_padding)); ?>mm;
                border-collapse: collapse;
            }

            .category-table tr td {
                border: 1px solid black;
            }

            .head {
                margin-left: -<?php echo $side_padding ?>mm; 
                margin-top: -<?php echo $side_padding ?>mm;
                background-color: #D9D9D9;
                width: 212mm;
            }

            .head-triangle {
                width: 0; 
                height: 0;
                border-left: 106mm solid transparent;
                border-right: 106mm solid transparent;
                margin-left: -<?php echo $side_padding ?>mm;
                border-top: 15mm solid #D9D9D9;
            }

            .info-icon {
                width: 16px;
                height: 16px;
                margin: 0.4mm;
                margin-right: 1mm;
            }

            td {
                vertical-align: top;
            }

        </style>

        <page backimg="assets/img/ACOPA_Logo_grau.png" backimgx="98%" backimgy="98%" backimgw="36%">
            <page_footer>
                <table style="width: 100%; font-size: 9pt;">
                    <tr>
                        <td style="width: 20%"><?php echo date('jS \o\f F Y') ?></td>
                        <td style="width: 60%; text-align: center">
                            <?php echo $opt["company_info"]["name"]." | ".$opt["company_info"]["addr_street"]." ".$opt["company_info"]["addr_street_number"]." | ".$opt["company_info"]["addr_zip"]." ".$opt["company_info"]["addr_city"]; ?> <br>
                            Curriculum Vitae – <?php echo $opt["user_information"]["firstName"] . " " . $opt["user_information"]["name"] ?></td>
                        <td style="width: 20%; text-align: right">Page <b>[[page_cu]]</b> of <b>[[page_nb]]</b></td>
                    </tr>
                </table>
            </page_footer>
        </page>

        <div style="width: 212mm;" class="head">
            <img style="width: 50mm; height: 50mm; border-radius: 50%; margin-left: 81mm; margin-top: 4mm" src="<?php echo $opt["profile_picture"]; ?>">
        </div>
        <div class="head-triangle" style="margin-bottom: 4mm"></div>

        <div style="text-align: center;">
            <span style="font-size: 18pt"><?php echo $opt["user_information"]["firstName"] . " " . $opt["user_information"]["name"] ?></span><br>
            <span style="font-style: italic;"><?php echo $opt["personal_information"]["position"] ?></span>
        </div>

        <table style="width: 212mm; margin-left: -<?php echo ($side_padding+1) ?>mm; margin-top: 5mm; font-size: 11pt">
            <tr>
                <td style="width: 40%; padding: 2mm; background-color: #BBD406; border-top-right-radius: 2mm; border-bottom-right-radius: 2mm; font-size: 12pt; font-weight: bold; padding-left: 0mm"><span style="margin-left: <?php echo $side_padding; ?>mm">Contact Information</span></td>
                <td style="width: 20%"></td>
                <td style="width: 40%; padding: 2mm; background-color: #BBD406; border-top-left-radius: 2mm; border-bottom-left-radius: 2mm; font-size: 12pt; font-weight: bold; text-align: center" >Personal Data</td>
            </tr>

            <tr><td style="height: 3mm;"></td></tr>

            <tr>
                <td style="padding-left: <?php echo $side_padding ?>mm;">
                    <?php echo $opt["company_info"]["name"]; ?><br>
                    <?php echo $opt["personal_information"]["addr_street"] . " " . $opt["personal_information"]["addr_street_number"] ?><br>
                    <?php echo $opt["personal_information"]["addr_zip"] . " " . $opt["personal_information"]["addr_city"] ?><br>
                    <?php echo $opt["personal_information"]["addr_country"] ?><br>
                    <br>
                    <?php if (!empty($opt["personal_information"]["email"])) { ?>
                        <img src="assets/img/cv/envelope.png" class="info-icon"><a><?php echo $opt["personal_information"]["email"] ?></a><br>
                    <?php } if (!empty($opt["personal_information"]["web"])) { ?>
                        <img src="assets/img/cv/globe.png" class="info-icon"><a><?php echo $opt["personal_information"]["web"] ?></a><br>
                    <?php } if (!empty($opt["personal_information"]["tel"])) { ?>
                        <img src="assets/img/cv/phone.png" class="info-icon"><?php echo $opt["personal_information"]["tel"] ?><br>
                    <?php } if (!empty($opt["personal_information"]["fax"])) { ?>
                        <img src="assets/img/cv/fax.png" class="info-icon"><?php echo $opt["personal_information"]["fax"] ?><br>
                    <?php } ?>
                    
                </td>
                <td></td>
                <td style="padding-left: 5mm">
                    <table style="width: 85mm">
                        <tr>
                            <td style="width: 30%">Name: </td>
                            <td style="width: 70%"><?php echo $opt["user_information"]["firstName"] . " " . $opt["user_information"]["name"] ?></td>
                        </tr>
                        <tr>
                            <td>Date of birth: </td>
                            <td><?php if($opt["personal_information"]["birthdate"] !== null) echo normalDate($opt["personal_information"]["birthdate"]); ?></td>
                        </tr>
                        <tr>
                            <td>Nationality: </td>
                            <td><?php echo $opt["personal_information"]["nationality"] ?></td>
                        </tr>
                        <tr>
                            <td>Expertise: </td>
                            <td>?</td>
                        </tr>
                        <tr>
                            <td>Position: </td>
                            <td><?php echo $opt["personal_information"]["position"] ?></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        
        <div class="category-heading">Summary of Qualifications</div>

        <?php if (count($opt["references"]) > 0) { ?>
            <div class="category-heading">Relevant Projects</div>
            <table class="category-table">
            <?php
                foreach($opt["references"] as $i => $reference) { ?>
                    <tr>
                        <td style="width: 25%; background-color: #dbdbdb; border: 1px solid black;"><p><b>Skill: <?php echo $reference["skillId"]; ?></b></p>                                                                                                                                            
                            <p><?php echo smallDate($reference["start"]); ?><?php echo ($reference["end"] != null ? " - " . smallDate($reference["end"]) : ""); ?></p>
                            <p><?php echo (($reference["client"] == null) ? "" : "Client: " .$reference["client"]. "<br><br>"); ?></p>
                            <p>Position: <?php echo $reference["position"]; ?></p>
                        </td>
                        <td style="width: 75%; border: 1px solid black;"><b><?php echo $reference["title"]; ?></b>
                            <br><br>
                            <?php echo custombbcode($reference["description"]); ?>
                        </td>
                    </tr>
                <?php } ?>
            </table>
            
            <?php echo ($i != @count($opt["references"]) - 1 ? "<br><br>" : ""); 
        } ?>

        <br><br>

        
        <?php if (count($opt["professional_history"]) > 0) { ?>
            <div class="category-heading">Professional History</div>
            <table class="category-table">
            <?php foreach($opt["professional_history"] as $i => $history) { ?>
                <tr>
                    <td style="width: 25%; background-color: #dbdbdb; border: 1px solid black;"><p><b><?php echo ($history["start"] != null ? smallDate($history["start"]) : "START"); ?><?php echo ($history["end"] != null ? " - " . smallDate($reference["end"]) : " - TODAY"); ?></b></p>
                    </td>
                    <td style="border: 1px solid black; width: 75%;"><b><?php echo $history["title"]; ?></b>
                        <br><br><?php echo custombbcode($history["position"]); ?>
                    </td>
                </tr>
            <?php } ?>
        </table><?php echo ($i != count($opt["professional_history"]) - 1 ? "<br><br>" : ""); } ?>


        <?php if (count($opt["education"]) > 0) { ?>
            <div class="category-heading">Education</div>
            <table class="category-table">
            <?php foreach($opt["education"] as $i => $education) { ?>
                <tr>
                    <td style="width: 25%; background-color: #dbdbdb; border: 1px solid black;"><p><b><?php echo ($education["start"] === null ? "" : smallDate($education["start"]) . " - "); ?><?php echo ($education["graduation"] != null ?  smallDate($education["graduation"]) : "TODAY"); ?></b></p>
                    </td>
                    <td style="border: 1px solid black; width: 75%;"><b><?php echo $education["title"]; ?></b>
                        <br><br><?php echo custombbcode($education["description"]); ?>
                    </td>
                </tr>
            <?php } ?>
        </table><?php echo ($i != count($opt["education"]) - 1 ? "<br><br>" : ""); } ?>

        <div class="category-heading">Certifications</div>
    <?php }

?>