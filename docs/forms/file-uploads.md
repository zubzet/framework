# Working with file uploads
## Manual
Sometimes it is needed that a user uploads a file. To handle incoming files, response has the [`upload()`](../api/classes/ZubZet-Framework-Message-Response.html#method_upload) method. It returns a [`z_upload`](../api/classes/ZubZet-Framework-Form-Upload.html) object which has more methods to handle with uploads.

```php
$upload = $res->upload();
if ($upload->upload($_FILES["file"], "uploads/", FILE_SIZE_100GB, ["txt", "jpg", "png"])) {
    $res->error();
}

$fileId = $upload->fileId;
```

`$file` is the file in [`$_FILES`](https://www.php.net/manual/en/reserved.variables.files.php).  
`$uploadDir` is the directory to place the file in. It must end with `/`: the generated file name is appended to the string as it is, so `"uploads"` would write `uploadsREF.ext` next to the folder instead of into it. The file is stored as `<uniqid>.<lowercased extension>`, the original name only in `z_file.name`. A missing folder is created with mode `0755`.  
`$maxSize` is the max file size in bytes, `0` means unlimited. The constants `FILE_SIZE_1MB`, `2MB`, `5MB`, `10MB`, `20MB`, `50MB`, `100MB`, `200MB`, `500MB`, `1GB`, `2GB`, `5GB`, `10GB`, `20GB`, `50GB` and `100GB` hold byte counts (the GB ones are n times 1000 MiB, so `FILE_SIZE_1GB` is 1048576000). `image()` (`jpg`, `jpeg`, `png`, `bmp`), `video()` (`mp4`, `m4a`, `m4v`, `mov`, `3gp`, `ogg`, `webm`, `flv`, `mkv`, `avi`, `wmv`) and `audio()` (`mp3`, `wav`, `wma`, `ogg`, `m4a`, `aiff`, `aac`) are `upload()` with that type filter and default to `FILE_SIZE_2MB`, `FILE_SIZE_100MB` and `FILE_SIZE_10MB`; `audio()` accepts `mp3`, `wav`, `wma`, `ogg`, `m4a`, `aiff` and `aac`. Raise `upload_max_filesize` and `post_max_size` in the `php.ini` for large files.  
`$typeArray` lists the accepted file extensions. The extension of the uploaded file name is lowercased and then compared with the entries, so write them in lowercase and without a dot (`"jpg"`, not `"JPG"` or `".jpg"`), which means `photo.JPG` is accepted by `"jpg"`. A file whose extension is not listed returns `UPLOAD_ERROR_WRONG_TYPE`. An empty array accepts every extension, including `php`.

On success the file is registered in `z_file`, the properties `ref`, `mime`, `srcName`, `extension`, `size`, `filePath` and `fileId` are filled and `UPLOAD_SUCCESS` (0) is returned. Otherwise the properties stay empty and the code is `UPLOAD_ERROR_TOO_BIG` (1), `UPLOAD_ERROR_WRONG_TYPE` (2), `UPLOAD_ERROR_NOT_MOVED` (3, `move_uploaded_file` failed) or `UPLOAD_ERROR_NO_FILE` (4, `$file` is empty or has no `name`).


## Using Z-Forms
When using [Z-Forms](auto-form-validation.md), error feedback automatically goes back to the user, and `insertDatabase()` and `updateDatabase()` store the files first: a field without a posted file is skipped (`noSave`), an upload error ends with `error("Upload error: <code>")`, otherwise the column gets the stored file id. For file uploads there is the special rule [`file()`](../api/classes/ZubZet-Framework-Form-Validation-Field.html#method_file).
```
(new FormField("file")) -> file(FILE_SIZE_1MB, ["txt", "jpg", "png"])
```
The first argument is the byte limit: a larger file fails with `file_to_big` (one o), and unlike `upload()` the value `0` does not mean unlimited. The second argument lists the accepted file extensions. The extension of the uploaded file name is lowercased and then compared with the entries, so write them in lowercase and without a dot (`"jpg"`, not `"JPG"` or `".jpg"`), which means `photo.JPG` is accepted by `"jpg"`. A file whose extension is not listed fails with the error type `file_type`; the error type `file` means a value was posted under the field name but no upload exists; like `file`, it has no default `Z.Lang.error_file_type` text, so define it in the layout. An empty array (the default) accepts every extension, including `php`.

Files uploaded with this method are moved into the `uploadFolder` setting, which defaults to `uploads/` (relative to the project root) when the key is missing.

The `uploadFolder` setting in `z_settings.ini` has to end with `/` as well (`uploadFolder = webroot/uploads/`). The framework passes it to `upload()` unchanged and does not add a missing slash, so `webroot/uploads` would create the folder but store the files as `webroot/uploadsREF.ext` beside it.

## Security
Uploads land in the folder passed to `upload()`, with Z-Forms in the `uploadFolder` setting, which is `webroot/uploads/` in a new or migrated project. The `.htaccess` in that folder keeps PHP files from running, but every file in it is served from the application's own domain and the browser displays what it can. A file the browser renders as a page runs its scripts with the session of whoever opens it.

Everything the check relies on comes from the client. Only the extension of the submitted file name is evaluated; the content is not inspected and the MIME type is not sniffed, so a file named `evil.jpg` with arbitrary content passes `["jpg"]`. The `mime` property of the upload object (and the value stored in `z_file`) is the type the browser reported, not a detected one. The `error` code PHP sets in `$_FILES` is not read either; a failed transfer only shows up indirectly, for example as `UPLOAD_ERROR_NOT_MOVED` when the temporary file is missing.

The file name is the `uniqid()` reference, which is time based and guessable. The framework ships no route that serves stored files; serving them is up to the project.

Which types are accepted is up to the project.
