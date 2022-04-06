<?php 
/**
 * The log view. Only accessible with permission
 */

return ["head" => function($opt) { ?> <!-- File header -->

    <style>
        thead, tr {
            min-width: 100%;
        }
    </style>
    
<?php }, "body" => function($opt) { ?> <!-- File body -->	

    <h2><?php $opt["lang"]("title") ?></h2>

    <!-- Time span -->
    <div class="input-group">
        <div class="input-group-prepend">
            <label for="input-start" class="input-group-text"><?php $opt["lang"]("start_date") ?></label>
        </div>
        <input type="datetime-local" id="input-start" class="form-control">
        <div class="input-group-prepend">
            <label for="input-end" class="input-group-text"><?php $opt["lang"]("end_date") ?></label>
        </div>
        <input type="datetime-local" id="input-end" class="form-control">
    </div>

    <!-- Categories -->
    <label><?php $opt["lang"]("categories") ?> <span class="hide-for-small-only"><?php $opt["lang"]("ctrl_for_multiple") ?></span></label>
    <div class="input-group mb-1">
        <select id="input-cats" class="form-control" multiple style="height: 150px;">
        <?php 
            foreach ($opt["log_categories"] as $category) {
                echo "<option value='".$category["id"]."'>".$category["name"]."</option>";
            }
        ?>
        </select>
    </div>

    <button id="button-submit" class="btn btn-primary" disabled><?php $opt["lang"]("request") ?></button>
    <div class="btn-group">
        <a target="_blanc" id="download-csv" class="btn btn-secondary"><?php $opt["lang"]("download_csv") ?></a>
        <a target="_blanc" id="download-json" class="btn btn-secondary"><?php $opt["lang"]("download_json") ?></a>
        <a target="_blanc" id="download-txt" class="btn btn-secondary"><?php $opt["lang"]("download_txt") ?></a>
    </div>

    <hr>

    <h2><?php $opt["lang"]("results") ?></h2>

    <table class="table">
        <thead>
            <tr>
                <th scope="col"><?php $opt["lang"]("index") ?></th>
                <th scope="col"><?php $opt["lang"]("table-date") ?></th>
                <th scope="col"><?php $opt["lang"]("category") ?></th>
                <th scope="col"><?php $opt["lang"]("user") ?></th>
                <th scope="col"><?php $opt["lang"]("exec_user") ?></th>
                <th scope="col"><?php $opt["lang"]("text") ?></th>
            </tr>
        <thead>
        <tbody id="result-table"></tbody>
    </table>

    <script>

        var categoryList = [];
        <?php 
            foreach ($opt["log_categories"] as $category) {
                echo "categoryList[".$category["id"]."] = '".$category["name"]."';";
            }
        ?>

        document.getElementById("button-submit").addEventListener("click", () => {
            request();
            document.getElementById("button-submit").disabled = true;
        });

        function changeState() {
            document.getElementById("button-submit").disabled = false;
        }

        function request() {
            updateLink();
            fetch(`${link}/json`).then(response => {
                return response.json();
            }).then(data => {
                fillResultsFromData(data);
            });
        }

        document.getElementById("input-start").addEventListener("change", () => changeState());
        document.getElementById("input-end").addEventListener("change", () => changeState());
        document.getElementById("input-cats").addEventListener("change", () => changeState());

        function fillResultsFromData(data) {
            var table = document.getElementById("result-table");
            table.innerHTML = "";
            var lines = data.data;
            var tr, td;
            for (var line of lines) {
                tr = document.createElement("tr");
                tr.innerHTML = `
                    <td>`+line.id+`</td>
                    <td>`+line.created+`</td>
                    <td>`+categoryList[line.categoryId]+`</td>
                    <td>`+(line.name == null ? "annonymous" : line.name)+`</td>
                    <td>`+(line.name_exec == null ? "annonymous" : line.name_exec)+`</td>
                    <td>`+line.text+`</td>
                `;

                table.appendChild(tr);
            }
        }

        var link = "";
        function updateLink() {
            var date_start = document.getElementById("input-start").value.replace("T", " ") || "2000-1-1";
            var date_end = document.getElementById("input-end").value.replace("T", " ") || "9999-12-12";

            date_start = date_start.replace(/:/g, "%3A");
            date_end = date_end.replace(/:/g, "%3A");
            date_start = date_start.replace(/ /g, "%20");
            date_end = date_end.replace(/ /g, "%20");

            var cats_val = $("#input-cats").val();
            var cats;
            if (cats_val) {
                cats = cats_val.join(",");
            } else { //If none is selected, take it all!
                var cat_arr = [];
                var options = document.querySelectorAll("#input-cats>option");
                for (var o of options) {
                    cat_arr.push(o.value);
                }
                cats = cat_arr.join(",");
            }

            link = `log/ajax/${cats}/${date_start}/${date_end}`;
            document.getElementById("download-csv").setAttribute("href", link + "/csv");
            document.getElementById("download-txt").setAttribute("href", link + "/txt");
            document.getElementById("download-json").setAttribute("href", link + "/json");
        }
        updateLink();
    </script>

<?php }, "lang" => [
        "de_formal" => [
            "table-date" => "Zeit",
            "table-name" => "Benutzer",
            "table-text" => "Text",
            "title" => "Log / Statistiken",
            "request" => "Anfordern",
            "categories" => "Kategorien",
            "ctrl_for_multiple" => "(STRG halten für mehrfachauswahl)",
            "start_date" => "Von",
            "end_date" => "Bis",
            "download_txt" => "Download als .txt",
            "download_json" => "Download als .json",
            "download_csv" => "Download als .csv",
            "category" => "Kategorie",
            "user" => "Mitarbeiter",
            "exec_user" => "Exec Mitarbeiter",
            "text" => "Text",
            "value" => "Wert",
            "results" => "Ergebnisse",
            "index" => "ID"
        ], 
        "en" => [
            "table-date" => "Datetime",
            "table-name" => "User",
            "table-text" => "Text",
            "title" => "Log / Statistics",
            "request" => "Request",
            "categories" => "Categories",
            "ctrl_for_multiple" => "(Hold CTRL to select multiple)",
            "start_date" => "From",
            "end_date" => "To",
            "download_txt" => "Download as .txt",
            "download_json" => "Download as .json",
            "download_csv" => "Download as .csv",
            "category" => "Category",
            "user" => "user",
            "exec_user" => "Exec user",
            "text" => "Text",
            "value" => "Value",
            "results" => "Results",
            "index" => "ID"
        ]
    ]
]; ?>