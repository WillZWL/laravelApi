<!DOCTYPE html>
<html>
    <head>
        <title>Accelerator  Admincenter</title>
        <!-- HTML meta refresh URL redirection -->
        <meta http-equiv="refresh" content="0; url= {{ url( 'scout' ) }}">
        <link href="https://fonts.googleapis.com/css?family=Lato:100" rel="stylesheet" type="text/css">
        <style>
            html, body {
                height: 100%;
            }
            body {
                margin: 0;
                padding: 0;
                width: 100%;
                display: table;
                font-weight: 100;
                font-family: 'Lato', sans-serif;
            }
            .container {
                text-align: center;
                display: table-cell;
                vertical-align: middle;
            }

            .content {
                text-align: center;
                display: inline-block;
            }
            .title {
                font-size: 52px;
            }
            .title a {
              text-decoration:none;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="content">
                <div class="title">
                  The page has moved to : <a href="{{ url( 'scout' ) }}"><b>Accelerator</b></a>
                </div>
            </div>
        </div>
    </body>
</html>
