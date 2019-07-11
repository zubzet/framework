<?php $body = function($opt) { ?>

<p><?php $opt["lang"]("hello"); ?> <?php echo $opt["employee"]["firstName"]; ?>,</p>
<p><?php $opt["lang"]("text") ?></p>
<p>
    <?php $opt["lang"]("time") ?>
    <?php echo $opt["date"] ?>
</p>
<p>
    <?php $opt["lang"]("request") ?><br>
    <?php $opt["lang"]("country") ?>: <?php echo $opt["ip"]->country_name; ?><br>
    <?php $opt["lang"]("region") ?>: <?php echo $opt["ip"]->region; ?><br>
    <?php $opt["lang"]("city") ?>: <?php echo $opt["ip"]->city; ?><br>
    <?php $opt["lang"]("ip") ?>: <?php echo $opt["ip"]->ip; ?>
</p>

<?php }; $getLangArray = function() {
    return [
        "de_formal" => [
            "hello" => "Hallo",
            "text" => "jemand hat versucht, sich mehrfach mit Ihrem Account und einem falschen Passwort anzumelden. Sollten das nicht Sie gewesen sein, wird ein starkes Passwort empfohlen.",
            "request" => "Der Zugriff kam vermutlich hierher: ",
            "time" => "Zeitpunkt: ",
            "country" => "Land",
            "region" => "Bundesland",
            "city" => "Stadt",
            "ip" => "IP-Adresse"
        ],
        "en" => [
            "hello" => "Hello",
            "text" => "someone has tried multiple times to login into your account with a wrong password. If that was not you, make sure to have a secure and strong password.",
            "request" => "The request was probably made from here: ",
            "time" => "Date: ",
            "country" => "Country",
            "region" => "Region",
            "city" => "City",
            "ip" => "IP Address"
        ]
    ];
} ?>