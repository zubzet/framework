<?php return ["head" => function($opt) { ?> <!-- File header -->

    <style>
        thead, tr {
            min-width: 100%;
        }
    </style>

<?php }, "body" => function($opt) { ?> <!-- File body -->

    <h2>Log / Statistics</h2>

    <!-- Time span -->
    <div class="input-group">
        <div class="input-group-prepend">
            <label for="input-start" class="input-group-text">From</label>
        </div>
        <input type="datetime-local" id="input-start" class="form-control">
        <div class="input-group-prepend">
            <label for="input-end" class="input-group-text">To</label>
        </div>
        <input type="datetime-local" id="input-end" class="form-control">
    </div>

    <!-- Categories -->
    <label>Categories <span class="hide-for-small-only">(Hold CTRL to select multiple)</span></label>
    <div class="input-group mb-1">
        <select id="input-cats" class="form-control" multiple style="height: 150px;">
        <?php 
            foreach ($opt["log_categories"] as $category) {
                echo "<option value='".$category["id"]."'>".$category["name"]."</option>";
            }
        ?>
        </select>
    </div>

    <button id="button-submit" class="btn btn-primary" disabled>Request</button>
    <div class="btn-group">
        <a target="_blanc" id="download-csv" class="btn btn-secondary">Download as .csv</a>
        <a target="_blanc" id="download-json" class="btn btn-secondary">Download as .json</a>
        <a target="_blanc" id="download-txt" class="btn btn-secondary">Download as .txt</a>
    </div>

    <hr>

    <h2>Results</h2>

    <table class="table">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Datetime</th>
                <th scope="col">Category</th>
                <th scope="col">user</th>
                <th scope="col">Exec user</th>
                <th scope="col">Text</th>
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

<?php }]; ?>
