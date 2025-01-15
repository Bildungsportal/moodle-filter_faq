<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>

<form id="issuecollector" method="post" enctype="multipart/form-data" action="post-file.php">
    <div class="grid-container">
        <div class="item-17">
            Secret: <input name="secret" value="1234">
        </div>
        <div class="item-17">
            Seed: <input name="seed" value="seed">
        </div>
        <div class="item-17">
            <input name="files[]" type="file" accept=".png, .jpg, .jpeg, .pdf" multiple>
        </div>
        <div class="item-15">
            <button type="submit" class="btn btn-primary">absenden</button>
        </div>
    </div>
</form>

</body>
</html>
