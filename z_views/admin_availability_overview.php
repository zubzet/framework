<?php function head($opt) { ?> <!-- File header -->

    <script src="<?php echo $opt["root"]; ?>assets/js/autocomplete.js"></script>
    <link rel="stylesheet" href="<?php echo $opt["root"]; ?>assets/css/autocomplete.css">

    <style>
        #result .row {
            box-shadow: 0 0 5px 0 rgba(0, 0, 0, 0.15);
            background-color: #f8f8f8;
            border-bottom: 1px dashed #d7d7d7;
            padding: 10px;
        }

        #result .row.timerow {
            margin-left: 10px;
        }

        #result .row.daterow {
            background-color: #f1f1f1;
            font-weight: bold;
        }

        #employee-list {
            box-shadow: 0 0 5px 0 rgba(0, 0, 0, 0.15);
            background-color: #f8f8f8;
        }

        .employee {
            width: 100%;
            border-bottom: 1px dashed #d7d7d7;
            padding: 10px;
        }

        .employee-remove {
            float: right;
            margin: -8px;
        }
    </style>

<?php } function body($opt) { ?> <!-- File body -->	

    <h1><?php echo $opt["lang"]("title") ?></h1>

    <!-- Time span -->
    <div class="row">
        <div class="medium-6 small-12 columns">
            <label for="input-from"><?php echo $opt["lang"]("from") ?></label>
            <input type="date" id="input-from" value="1970-01-01">
        </div>

        <div class="medium-6 small-12 columns">
            <label for="input-to"><?php echo $opt["lang"]("to") ?></label>
            <input type="date" id="input-to" value="9999-12-31">
        </div>
    </div>

    <div class="row">
        <div class="medium-12 columns">
            <label for="employee-adder"><?php $opt["lang"]("add_employee"); ?></label>
            <input type="text" id="employee-adder" class="autocomplete" autofocus >
        </div>
    </div>

    <hr>

    <h5><?php echo $opt["lang"]("selected_employees"); ?></h5>
    <div id="employee-list" class="input"></div>

    <hr>

    <h5><?php echo $opt["lang"]("times"); ?></h5>
    <div id="result" class="columns"></div>

    <script>

        document.getElementById("input-from").addEventListener("change", () => request());
        document.getElementById("input-to").addEventListener("change", () => request());

        function request() {
            var start = document.getElementById("input-from").value;
            var end = document.getElementById("input-to").value;
            
            var link = `availability_overview/ajax/${start}/${end}/${selectedEmployees.map(emp => emp.id).join(",")}`;
            console.log("Fetching from: ", link);

            fetch(link).then(response => {
                return response.json();
            }).then(data => {
                console.log(data);
                renderRequest(data.data);
            });
        }

        //employees
        var empList = [
            <?php 
                foreach($opt["employees"] as $row) {
                    echo "{id: " . $row["id"] . ', text: "'.$row["full_name"].'"},';
                }
            ?>
        ];

        var autoList = [
            <?php 
                foreach($opt["auto_complete_employees"] as $row) {
                    echo "{id: " . $row["id"] . ', text: "'.$row["full_name"].'"},';
                }
            ?>
        ];

        var nameById = {};
        for (var emp of empList) {
            nameById[emp.id] = emp.text;
        }

        autocomplete(document.getElementById("employee-adder"), autoList.concat(empList), (dat) => {
            var employee = empList.find(em => em.id == dat.id);
            if (!selectedEmployees.includes(employee)) {
                selectedEmployees.push(employee);
            }
            request();
            renderSelectedEmployees();

            document.getElementById("employee-adder").value = "";
        });

        var selectedEmployees = [];
        function renderSelectedEmployees() {
            var list = document.getElementById("employee-list");
            list.innerHTML = "";
            for (let employee of selectedEmployees) {
                var div = document.createElement("div");
                div.classList.add("employee");
                div.innerHTML = employee.text;
                list.appendChild(div);

                var remover = document.createElement("button");
                remover.classList.add("button", "employee-remove");
                remover.innerHTML = "Remove";
                remover.addEventListener("click", e => {
                    var index = selectedEmployees.indexOf(employee);
                    selectedEmployees.splice(index, 1);
                    request();
                    renderSelectedEmployees();
                });

                div.appendChild(remover);
            }

            if (selectedEmployees.length == 0) {
                list.innerHTML = "<div class='employee'><?php echo $opt["lang"]("not_chosen"); ?></div>";
            }
        }

        function renderRequest(data) {
            var sorted = data.sort((a, b) => {
                return (new Date(a.day) - new Date(b.day)) || (+a.start.replace(/:/g, "") - (+b.start.replace(/:/g, "")));
            });

            var html = "";
            var currentDate = null;
            for (var row of sorted) {
                if (row.day != currentDate) {
                    currentDate = row.day;
                    var date = new Date(currentDate);
                    html += `<div class="row daterow">${date.toLocaleDateString()}</div>`;
                }
                html += `<div class="row timerow">
                    <div class="medium-4 small-12">${nameById[row.employeeId]}</div>
                    <div class="medium-4 small-12">${row.start.substring(0, 5)} - ${row.end.substring(0, 5)}</div>
                    <div class="medium-4 small-12"><?php echo $opt["lang"]("for_minutes"); ?>${row.duration} <?php echo $opt["lang"]("minutes"); ?></div>
                </div>`;
            }

            if (sorted.length == 0) {
                html = "<div class='row'><?php echo $opt["lang"]("no_results_found"); ?></div>"
            }

            document.getElementById("result").innerHTML = html;
        }

        renderSelectedEmployees();
        renderRequest([]);

    </script>

<?php } function getLangArray() {

    return [
        "de_formal" => [
            "title" => "Verfügbarkeitsübersicht",
            "request" => "Anfordern",
            "from" => "Von",
            "to" => "Bis",
            "add_employee" => "Mitarbeiter hinzufügen",
            "selected_employees" => "Ausgewählte Mitarbeiter",
            "times" => "Zeiten",
            "for_minutes" => "Für ",
            "minutes" => "Minuten",
            "not_chosen" => "Noch kein Mitarbeiter ausgewählt.",
            "no_results_found" => "Keine Ergebnisse gefunden."
        ], 
        "en" => [
            "title" => "Availability overview",
            "request" => "Request",
            "from" => "From",
            "to" => "To",
            "add_employee" => "Add employee",
            "selected_employees" => "Selected employees",
            "times" => "Times",
            "for_minutes" => "for ",
            "minutes" => "minutes",
            "not_chosen" => "No employee chosen yet.",
            "no_results_found" => "No results found."
        ]
    ];

} ?>