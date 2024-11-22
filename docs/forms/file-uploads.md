# Working with file uploads
## Manual
Sometimes it is needed that a user uploads a file. To handle incoming files, response has the `upload()` method. It returns a `z_upload` object which has more methods to handle with uploads.

```php
$upload = $res->upload();
if ($upload->upload($_FILES["file"], "uploads/", FILE_SIZE_100GB, ["txt", "jpg", "png"])) {
    $res->error();
}

$fileId = $upload->fileId;
```

`$file` is the file in `$_FILES`.  
`$uploadDir` is the directory to place the file in. Ending with `/`.  
`$maxSize` is the max file size. For some values there are already constants in the framework.  
`$typeArray` array of accepted file types.


## Using Z-Forms
When using Z-Forms, files will be stored and error feedback automatically goes back to the user. For file uploads there is the special rule `file()`.
```
(new FormField("file")) -> file(FILE_SIZE_1MB, ["txt", "jpg", "png"])
```
Files uploaded with this method will always be moved into the "uploads/" directory.