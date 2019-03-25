<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Itshot.com - Automatic Upload Image</title>
        <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
        <link rel="stylesheet" href="/resources/demos/style.css">
        <style>
            .ui-progressbar {
                position: relative;
            }
            .progress-label {
                position: absolute;
                left: 50%;
                top: 4px;
                font-weight: bold;
                text-shadow: 1px 1px 0 #fff;
            }
        </style>
        <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
        <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    </head>
    <body>
        <div>
            <h1>Itshot.com - Automatic Upload Image</h1>
            <div id="progressbar" data-total=''><div class="progress-label">Loading...</div></div>
            <script>
                $(function () {
                    var progressbar = $("#progressbar"),
                            progressLabel = $(".progress-label");

                    progressbar.progressbar({
                        value: false,
                        change: function () {
                            progressLabel.text(progressbar.progressbar("value") + "%");
                        },
                        complete: function () {
                            progressLabel.text("Complete!");
                        }
                    });

                    $.ajax({
                        url: '/adempierstock/imagescript/getListImages.php',
                        success: function (data) {
                            var percent = 1 / data.length * 100;
                            if (data.length > 0) {
                                data.forEach(function (a) {
                                    $.ajax({
                                        url: '/adempierstock/imagescript/processImage.php',
                                        data: {filename: a},
                                        success: function (data) {
                                            if (!data.errno) {
                                                var val = progressbar.progressbar("value") || 0;
                                                progressbar.progressbar("value", Math.ceil(val + percent));
                                            } else {
                                                console.log(a);    
                                            }
                                        }
                                    });
                                });
                            } else {
                                alert('Not found images');
                            }
                        }
                    });

                });
            </script>
        </div>
    </body>
</html>
