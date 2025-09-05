<?php return [ 'body' => function($opt) { ?>
    <button id="add" data-test="add">Send</button>
    <button id="err" data-test="err">Err</button>
    <button id="cust" data-test="cust">Cust</button>
    <button id="custerr" data-test="custerr">CustErr</button>

    <span id="response" data-test="response"></span>

    <script>
        $("#add").click(() => {
            Z.Request.action("add", {
                number1: 5,
                number2: 6
            },(res) => {
                $("#response").text(res.response + " " + res.result);
            });
        });

        $("#err").click(() => {
            Z.Request.action("err", {},(res) => {
                $("#response").text(res.result);
            });
        });

        $("#cust").click(() => {
            Z.Request.action("cust", {},(res) => {
                $("#response").text(res.response);
            });
        });

        $("#custerr").click(() => {
            Z.Request.action("custerr", {},(res) => {
                console.log(res);
                $("#response").text(res.error.message);
            });
        });
    </script>
<?php }]; ?>