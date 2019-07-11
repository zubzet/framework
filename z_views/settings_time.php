<?php function head($opt) { ?> <!-- File header -->

    <script>

        const weekdays = [ "Su.", "Mo.", "Tu.", "We.", "Th.", "Fr.", "Sa."];

        class Day {
            constructor(rowid, date, start, end, durationMinutes) {
                this.rowid = rowid;

                var minsSingle = Math.floor(durationMinutes) % 60;
                var minsHour = Math.floor(durationMinutes / 60);
                var duration = (minsHour < 10 ? "0" : "") + minsHour + ":" + (minsSingle < 10 ? "0" : "") + minsSingle;

                var dom = $(`<div class="row" id="row-${rowid}"></div>`);

                var blockDate = $(`<div class="medium-3 small-12 columns"><label class="show-for-small-only">Date</label></div>`)
                this.inputDate = $(`<input type="date" value="${date}">`);
                blockDate.append(this.inputDate);

                var blockStart = $(`<div class="medium-3 small-6 columns"><label class="show-for-small-only">Start</label></div>`)
                this.inputStart = $(`<input type="time" value="${start}">`);
                blockStart.append(this.inputStart);

                var blockEnd = $(`<div class="medium-3 small-6 columns"><label class="show-for-small-only">End</label></div>`);
                this.inputEnd = $(`<input type="time" value="${end}">`);
                blockEnd.append(this.inputEnd);

                var blockDuration = $(`<div class="medium-2 small-9 columns"><label class="show-for-small-only">Duration</label></div>`)
                this.inputDuration = $(`<input type="time" value="${duration}">`);
                blockDuration.append(this.inputDuration);

                var blockRemove = $(`<div class="medium-1 small-3"><label class="show-for-small-only"><?php $opt["lang"]("remove"); ?></label></div>`);
                var inputRemove = $(`<input type="button" value="<?php $opt["lang"]("remove"); ?>" class="button borderless">`);
                blockRemove.append(inputRemove);

                dom.append(blockDate);
                dom.append(blockStart);
                dom.append(blockEnd);
                dom.append(blockDuration);
                dom.append(blockRemove);

                dom.append(`<hr class="show-for-small-only">`);

                inputRemove.click(() => {
                    this.destroy();
                });

                function ch() {
                    confirmedSave = false;
                    hintUnsaved();
                    checkOrder();
                }
                
                this.inputStart.on('change', ch);
                this.inputEnd.on('change', ch);
                this.inputDuration.on('change', ch);
                this.inputDate.on("change", () => {
                    var date = new Date(this.inputDate.val());
                    var text = weekdays[date.getDay()];
                    //console.log(text);
                    ch();
                });

                this.dom = dom;
                tableDays.push(this);
            }

            destroy() {
                hintUnsaved();
                this.dom.remove();
                tableDays.splice(tableDays.indexOf(this), 1);
            }

            getDate() {return this.inputDate.val(); }
            getStart() {return this.inputStart.val(); }
            getEnd() {return this.inputEnd.val(); }
            getDuration() {
                var ints = this.inputDuration.val().split(":");
                return +ints[1] + (+ints[0] * 60);
            }
        }

        var rowid = 0;
        var tableDays = [];

        function sort() {
            tableDays.sort((a, b) => {
                return new Date(a.getDate()) - new Date(b.getDate());
            });

            for (var i = 0; i < tableDays.length; i++) {
                tableDays[i].dom.detach();
                $("#time-table").append(tableDays[i].dom);
            }

            console.log("sorted");

            checkOrder();
        }

        $(function() {
            $("#btn-add-day").click(() => {
                hintUnsaved();
                if ($("#use_smartInput").prop("checked")) {
                    if (tableDays.length > 0) {
                    var lastDay = tableDays[tableDays.length - 1];

                    var lastDate = new Date(lastDay.getDate());

                    if (isNaN(lastDate.getDate())) {
                        lastDate = new Date();
                    }
                    var weekIterator = 5; //probably changing depending on the timezone (friday = 5)
                    if (lastDate.getDay() == weekIterator) { //friday
                        lastDate.setDate(lastDate.getDate() + 3);
                    } else if (lastDate.getDay() == weekIterator+1) { //saturday
                        lastDate.setDate(lastDate.getDate() + 2);
                    } else {
                        lastDate.setDate(lastDate.getDate() + 1);
                    }

                    addDay(lastDate.toJSON().slice(0,10), lastDay.getStart(), lastDay.getEnd(), lastDay.getDuration());
                    } else {
                        addDay(new Date().toJSON().slice(0,10), "09:00", "17:30", 8*60);
                    }
                } else {
                    addDay(0, 0, 0, 0);
                }
            });

            <?php 
                foreach($opt["timeTable"] as $row) {
                    echo "addDay('".$row["day"]."','".$row["start"]."','".$row["end"]."','".$row["duration"]."');";
                }
            ?>

        });

        function addDay(date, start, end, duration) {
            var day = new Day(rowid++, date, start, end, duration);
            $("#time-table").append(day.dom);
        }

        var confirmedSave = false;
        function conFirmSave() {
            sendData();
        }

        function checkOrder() {
            $("#wrong-order-warning").addClass("hide");
            for (var i = 1; i < tableDays.length; i++) {
                var lastDay = tableDays[i - 1];
                var currentDay = tableDays[i];

                if (new Date(lastDay.getDate()).getTime() > new Date(currentDay.getDate()).getTime()) {
                    $("#wrong-order-warning").removeClass("hide");
                    return;
                }
            }
        }

        function sendData() {

            var str = "Save=1";
            for (var i = 0; i < tableDays.length; i++) {
                var day = tableDays[i];                
                str += `&timeTableLine[${i}][date]=${day.getDate()}`;
                str += `&timeTableLine[${i}][start]=${day.getStart()}`;
                str += `&timeTableLine[${i}][end]=${day.getEnd()}`;
                str += isNaN(day.getDuration()) ? "0" : `&timeTableLine[${i}][duration]=${day.getDuration()}`;
            }

            sendPost("", str, (text) => {
                var obj = JSON.parse(text);
                if (obj.result == "success") {
                    hintSaved();
                    //yay \o/
                } else {
                    hintUnsaved();
                    alert('<?php $opt["lang"]("not_saved"); ?>');   
                }
            }, () => {
                hintUnsaved();
                alert('<?php $opt["lang"]("response_not_parsed"); ?>'); 
            });
        }

        function sendPost(Purl, Pparams, cb = null, er = null) {
			var http = new XMLHttpRequest();
			var url = Purl;
			var params = Pparams;
			http.open('POST', url, true);
			http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
			http.onreadystatechange = function() {
                if (http.readyState == 4) {
                    if (http.status == 200) {
                        console.log(http.responseText);
                        if (cb != null) {
                            cb(http.responseText);
                        }
                    } else {
                        if (er != null) {
                            er(http.responseText);
                        }
                    }
                }
			}
			http.send(params);
        }
        
        function hintUnsaved() {
            $("#button-save").attr("disabled", false);
            $("#save-feedback").slideUp();
            $("#unsaved-feedback").fadeIn();
            $("#unsaved-spacer").slideDown();
        }

        function hintSaved() {
            $("#button-save").attr("disabled", true);
            $("#save-feedback").slideDown();
            $("#unsaved-feedback").fadeOut();
            $("#unsaved-spacer").hide();
        }

    </script>

    <style>
        .unsaved-fixed {
            position: fixed;
            z-index: 1;
        }

        .unsaved-callout {
            padding: 8px 32px;
        }
    </style>

<?php } function body($opt) { ?> <!-- File body -->

    <div id="save-feedback" class="callout success" data-closable style="display: none">
        <span><?php $opt["lang"]("feedback_saved"); ?></span>
    </div>

    <div id="unsaved-feedback" class="unsaved-callout unsaved-fixed unsaved-callout callout warning" data-closable style="display: none">
        <span><?php $opt["lang"]("feedback_unsaved"); ?></span>
    </div>

    <div id="unsaved-spacer" class="unsaved-callout callout warning" data-closable style="display: none; opacity: 0">
        <span>Spaaace</span>
    </div>

    <h1><?php $opt["lang"]("available_time"); ?></h1>
    <div class="input-group">
        <label for="use_smartInput">
            <input name="use_smartInput" id="use_smartInput" type="checkbox" class="input-group-field" checked>
            <?php $opt["lang"]("smart_input"); ?>
            <span data-tooltip aria-haspopup="true" class="has-tip" title="<?php $opt["lang"]("smart_input_tooltip"); ?>"><?php $opt["lang"]("smart_input_what"); ?></span>
        </label>
    </div>


    <div class="time-table-wrapper">
        <div class="grid-container">
            <div class="hide-for-small-only row">
                <div class="medium-3 columns">
                    <?php $opt["lang"]("date"); ?>
                    <span style="color: red;">*</span>
                </div>
                <div class="medium-3 columns"><?php $opt["lang"]("start"); ?></div>
                <div class="medium-3 columns"><?php $opt["lang"]("end"); ?></div>
                <div class="medium-2 columns">
                    <?php $opt["lang"]("duration"); ?>
                    <span style="color: red;">*</span>
                </div>
                <div class="medium-1 columns"></div>
            </div>
            <div class="grid-x grid-padding-x" id="time-table">
                <!-- Time table content -->
            </div>
        </div>
    </div>
    <div id="wrong-order-warning" class="hide callout primary">
        Looks like your table dates have just dived into choas! 
        <span class="clickable" onClick="sort();">Sort it</span>?
    </div>

    <input id="button-save" type="submit" class="button" name="Save" value="<?php $opt["lang"]("save"); ?>" onClick="conFirmSave();" disabled>

    <button class="button round" id="btn-add-day">
        <span class="show-for-sr"><?php $opt["lang"]("add"); ?></span>
        <span aria-hidden="true">+</span>
    </button>


    <!--<blockquote>
        Only time will tell if the time table will tell the time.
        <cite>Alex Zierhut</cite>
    </blockquote>-->

<?php } function getLangArray() {
    return [
        "de_formal" => [
            "available_time" => "Verfügbare Zeit",
            "smart_input" => "Benutzen Sie <b>Smart Input</b> zum Ausfüllen der Tabelle.",
            "smart_input_what" => "(Was ist Smart Input?)",
            "smart_input_tooltip" => "Smart Input füllt die Tage entsprechend Ihren vorherigen Eingaben aus. Wenn diese Option aktiviert ist, werden die Wochenenden für Sie übersprungen.",
            "date" => "Datum",
            "start" => "Beginn",
            "end" => "Ende",
            "duration" => "Dauer",
            "remove" => "Entfernen",
            "add" => "Hinzufügen",
            "save" => "Speichern",
            "feedback_saved" => "Gepseichert!",
            "feedback_unsaved" => "Änderungen sind noch nicht gespeichert",
            "response_not_parsed" => "Die Antwort des Servers konnte nicht verarbeitet werden. Bitte kontaktieren Sie einen Administator!",
            "not_saved" => "Ihre Daten konnten nicht gespeichert werden."
        ], 
        "en" => [
            "available_time" => "Available time",
            "smart_input" => "Use <b>smart input</b> when filling in the time table.",
            "smart_input_what" => "(What is smart input?)",
            "smart_input_tooltip" => "Smart input fills in the days for you according to your previous inputs. When enabled the mode also skips weekends for you.",
            "date" => "Date",
            "start" => "Start",
            "end" => "End",
            "duration" => "Duration",
            "remove" => "Remove",
            "add" => "Add",
            "save" => "Save",
            "feedback_saved" => "Saved!",
            "feedback_unsaved" => "There are unsaved changes",
            "response_not_parsed" => "The response of the server could not be parsed. Please contact an administator.",
            "not_saved" => "There was an error saving your data."
        ]
    ];
}
?>