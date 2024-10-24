# Generating PDF's
PDF's are generated with a library called html2pdf which is based on TCPDF. A pdf can be shown to a user as a response to a request. The method for that is called `$res->renderPDF()`.

The document the path to a view. The view is not built up like a normal view. It is a file containing a layout method thats output is given into the `$html2pdf->writeHTML($html)` method. The layout method accepts one parameter which is $opt in render.

Example PDF response:
```php
$res->renderPDF("example.php", ["name" => "Thorsten"], "out.pdf", "I", ['P', 'A4', 'en', true, 'UTF-8', array(20, 20, 20, 5)]);
```

Example PDF document:
```php
<?php 
    function layout($opt) {
        <h1>Hello,</h1>
        <p>I am a PDF document for <?php echo $opt["name"]; ?></p>
    }
?>
```

For more information of what is possible, see here: https://github.com/spipu/html2pdf/blob/master/doc/README.md
