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

                    function progress() {
                        var val = progressbar.progressbar("value") || 0;
                        progressbar.progressbar("value", val + 2);
                        if (val < 99) {
                            setTimeout(progress, 80);
                        }
                    }
                    setTimeout(progress, 2000);
                });
            </script>
        </div>
    </body>
</html>
