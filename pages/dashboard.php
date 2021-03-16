<!--
     TODO: - mehr charts
           - zwischen wetterstationen wechseln (vlt a homepage)
 -->

<?php
    session_start();

    $conn2 = new mysqli("weatherwebapp-db-new.cikkod1lareu.us-east-1.rds.amazonaws.com", "user", "UserPassword123!", "WeatherWebApp");

    if ($conn2->connect_error) {
        die("Connection failed: " . $conn2->connect_error);
    }
    $sql = "SELECT * FROM TMeasurement ORDER BY measurementID ASC LIMIT 10;";
    $result = $conn2->query($sql);
    $conn2->close();
    if (mysqli_num_rows($result) > 0) {
        // output data of each row
        $counter = 0;
        while($row = mysqli_fetch_assoc($result)) {
            $temperatureMeas[$counter] = $row["temperature"];
            $humidityMeas[$counter] = $row["humidity"];
            $pressureMeas[$counter] = $row["pressure"];
            $dateMeas[$counter] = $row["dateTime"];
            $counter++;
        }
    } else {
        echo "<script>alert('Error: No Data in Database')</script>";
    }

    if(isset($_SESSION['measUnit'])){
        if($_SESSION['measUnit'] == "imperial"){
            for ($j = 0; $j < $counter; $j++){
                $temperatureMeas[$j] = ($temperatureMeas[$j] * 9/5) + 32;
                $pressureMeas[$j] = round($pressureMeas[$j] * 0.0295301, 2);
            }
            $upperLimitPresChart = 32 - $pressureMeas[$counter - 1];
            $upperLimitTempChart = 134 - $temperatureMeas[$counter - 1];

            $tempString = $temperatureMeas[$counter - 1] . "°F";
            $pressString = $pressureMeas[$counter - 1] . "in";
        }elseif ($_SESSION['measUnit'] == "metric"){
            $upperLimitPresChart = 1183 - $pressureMeas[$counter - 1];
            $upperLimitTempChart = 57 - $temperatureMeas[$counter - 1];
            $tempString = $temperatureMeas[$counter - 1] . "°C";
            $pressString = $pressureMeas[$counter - 1] . "mb";
        }
    }
?>

<!doctype html>
<html lang="en">
<head>
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/png" href="../sources/favicon.png"/>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta http-equiv="refresh" content="1500">
    <title>Dashboard - Villnöss</title>
    <link rel="stylesheet" href="../css/dashboard_stylesheet.css">

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css"
          integrity="sha512-xodZBNTC5n17Xt2atTPuE1HxjVMSvLVW9ocqUKLsCC5CXdbqCmblAshOMAS6/keqq/sMZMZ19scR4PsZChSR7A=="
          crossorigin=""/> <!-- leaflet karten integration -->
    <script type="text/javascript" src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"
            integrity="sha512-XQoYMqMTK8LvdxXYG3nZ448hOEQiglfqkJs1NOQV44cWnUrBc8PkAOcXy20w0vlaXaVUearIOBhiXZ5V3ynxwA=="
            crossorigin=""></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.bundle.js"></script>   <!-- chart.js diagramme -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.bundle.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>    <!-- moment.js für zeitunterschied -->
</head>
<body>
    <div class="station-info">
        <h1 class="station-location">VILLNÖSS</h1>
        <p class="station-location-further">Bolzano, Italy</p>
        <a class="station-coordinates-time">46.64, 11.67</a>
        <p class="station-local-time-label">Local Time: </p>
        <p class="station-coordinates-time" id="time"></p>
        <div class="active-badge" id="activityBadge"></div>
        <script type="text/javascript" src="../js/get_localTime.js"></script>
    </div>

    <div class="divider"></div>
    <div class="dashboard-meas">
        <h3 class="dashboard-meas-heading">Latest Observation</h3>

        <p class="dashboard-meas-latest-data" id="latestData"></p>
        <script type="text/javascript">
            formatDate()

            function formatDate() {
                let dateMeas = "<?php echo $dateMeas[$counter - 1] ?>";
                let date = new Date();
                let time;
                let hours = date.getHours().toString();
                let min = date.getMinutes().toString();
                let sec = date.getSeconds().toString();

                /* Zeug für tage unterschied
                let day = date.getDate().toString();
                let month = date.getMonth().toString();
                let year = date.getFullYear().toString();

                month = (parseInt(month) + 1).toString();
                month = formatDateWithZero(month);

                day = (parseInt(day) + 1).toString();
                day = formatDateWithZero(day);
                //date = day + "/" + month + "/" + year;
                //date = date + " " + time;
                */
                hours = ("0" + hours).slice(-2);
                min = ("0" + min).slice(-2);
                sec = ("0" + sec).slice(-2);

                time = hours + ":" + min + ":" + sec;
                let timeMEAS = dateMeas.split(" ");
                document.getElementById("latestData").innerHTML = timeDiffCalc(time, timeMEAS[1]);
            }

            function timeDiffCalc(now, then) {
                let a = moment(now, "HH:mm:ss");
                let b = moment(then, "H:mm:ss");
                let diffSec = a.diff(b, 'seconds');
                let diffMin = a.diff(b, 'minutes');

                if(diffMin.toString() === "0"){
                    return "Updated " + diffSec + " seconds ago";
                }else if(parseInt(diffMin.toString()) > 60 || parseInt(diffMin.toString()) < 0) {
                    return "Updated more than an hour ago";
                }else {
                    return "Updated " + diffMin + " minutes ago";
                }
            }

        </script>
        <script type="text/javascript" src="../js/stationActivity.js"></script>

        <div class="donutCharts">
            <div class="chart-container" style="margin-left: -3px;">
                <canvas id="tempChart" height="130"></canvas>
                <script type="text/javascript">
                    let temperatureMeas = "<?php echo $temperatureMeas[$counter - 1] ?>";
                    let ctxTemp = document.getElementById('tempChart');
                    let tempString = '<?php echo $tempString ?>';
                    let upperLimitTemp = '<?php echo $upperLimitTempChart ?>';

                    let tempChart = new Chart(ctxTemp, {
                        type: 'doughnut',
                        data: {
                            labels: ['Temp', ''],
                            datasets: [{
                                label: '# of Votes',
                                data: [temperatureMeas, upperLimitTemp],
                                backgroundColor: [
                                    'rgb(60, 188, 195)',
                                    'rgb(235, 237, 239)',
                                ],
                                borderColor: [
                                    'rgb(60, 188, 195)',
                                    'rgb(235, 237, 239)',
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            legend: {
                                display: false
                            },
                            elements: {
                                center: {
                                    text: tempString,
                                    color: '#3cbcc3', // Default is #000000
                                    fontStyle: 'Calibri', // Default is Arial
                                    sidePadding: 20, // Default is 20 (as a percentage)
                                    minFontSize: 20, // Default is 20 (in px), set to false and text will not wrap.
                                    lineHeight: 25 // Default is 25 (in px), used for when text wraps
                                },
                            },
                            responsive: true,
                            maintainAspectRatio: true,
                            cutoutPercentage: 65
                        }
                    });

                    Chart.pluginService.register({
                        beforeDraw: function(chart) {
                            if (chart.config.options.elements.center) {
                                // Get ctx from string
                                var ctx = chart.chart.ctx;

                                // Get options from the center object in options
                                var centerConfig = chart.config.options.elements.center;
                                var fontStyle = centerConfig.fontStyle || 'Arial';
                                var txt = centerConfig.text;
                                var color = centerConfig.color || '#000';
                                var maxFontSize = centerConfig.maxFontSize || 75;
                                var sidePadding = centerConfig.sidePadding || 20;
                                var sidePaddingCalculated = (sidePadding / 100) * (chart.innerRadius * 2)
                                // Start with a base font of 30px
                                ctx.font = "30px " + fontStyle;

                                // Get the width of the string and also the width of the element minus 10 to give it 5px side padding
                                var stringWidth = ctx.measureText(txt).width;
                                var elementWidth = (chart.innerRadius * 2) - sidePaddingCalculated;

                                // Find out how much the font can grow in width.
                                var widthRatio = elementWidth / stringWidth;
                                var newFontSize = Math.floor(30 * widthRatio);
                                var elementHeight = (chart.innerRadius * 2);

                                // Pick a new font size so it will not be larger than the height of label.
                                var fontSizeToUse = Math.min(newFontSize, elementHeight, maxFontSize);
                                var minFontSize = centerConfig.minFontSize;
                                var lineHeight = centerConfig.lineHeight || 25;
                                var wrapText = false;

                                if (minFontSize === undefined) {
                                    minFontSize = 20;
                                }

                                if (minFontSize && fontSizeToUse < minFontSize) {
                                    fontSizeToUse = minFontSize;
                                    wrapText = true;
                                }

                                // Set font settings to draw it correctly.
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'middle';
                                var centerX = ((chart.chartArea.left + chart.chartArea.right) / 2);
                                var centerY = ((chart.chartArea.top + chart.chartArea.bottom) / 2);
                                ctx.font = fontSizeToUse + "px " + fontStyle;
                                ctx.fillStyle = color;

                                if (!wrapText) {
                                    ctx.fillText(txt, centerX, centerY);
                                    return;
                                }

                                var words = txt.split(' ');
                                var line = '';
                                var lines = [];

                                // Break words up into multiple lines if necessary
                                for (var n = 0; n < words.length; n++) {
                                    var testLine = line + words[n] + ' ';
                                    var metrics = ctx.measureText(testLine);
                                    var testWidth = metrics.width;
                                    if (testWidth > elementWidth && n > 0) {
                                        lines.push(line);
                                        line = words[n] + ' ';
                                    } else {
                                        line = testLine;
                                    }
                                }

                                // Move the center up depending on line height and number of lines
                                centerY -= (lines.length / 2) * lineHeight;

                                for (n = 0; n < lines.length; n++) {
                                    ctx.fillText(lines[n], centerX, centerY);
                                    centerY += lineHeight;
                                }
                                //Draw text in center
                                ctx.fillText(line, centerX, centerY);
                            }
                        }
                    });
                </script>
                <p class="text" style="margin: 12px 0 0 92px; color: #262626">TEMPERATURE</p>
            </div>

            <div class="chart-container" style="margin-left: -97px;">
                <canvas id="presChart" height="130"></canvas>
                <script type="text/javascript">
                    let pressureMeas = "<?php echo $pressureMeas[$counter - 1] ?>";
                    let pressureLimit = "<?php echo $upperLimitPresChart ?>";
                    let pressureString = '<?php echo $pressString ?>'
                    let ctxPres = document.getElementById('presChart');

                    let presChart = new Chart(ctxPres, {
                        type: 'doughnut',
                        data: {
                            labels: ['Press', ''],
                            datasets: [{
                                label: '# of Votes',
                                data: [pressureMeas, pressureLimit],
                                backgroundColor: [
                                    'rgb(37,42,52)',
                                    'rgb(235, 237, 239)',
                                ],
                                borderColor: [
                                    'rgb(37,42,52)',
                                    'rgb(235, 237, 239)',
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            legend: {
                                display: false
                            },
                            elements: {
                                center: {
                                    text: pressureString,
                                    color: '#252a34', // Default is #000000
                                    fontStyle: 'Calibri', // Default is Arial
                                    sidePadding: 20, // Default is 20 (as a percentage)
                                    minFontSize: 20, // Default is 20 (in px), set to false and text will not wrap.
                                    lineHeight: 25 // Default is 25 (in px), used for when text wraps
                                },
                            },
                            responsive: true,
                            maintainAspectRatio: true,
                            cutoutPercentage: 65
                        }
                    });

                </script>
                <p class="text" style="margin: 12px 0 0 119px; color: #262626">PRESSURE</p>
            </div>

            <div class="chart-container" style="margin-left: -100px;">
                <canvas id="humChart" height="130"></canvas>
                <script type="text/javascript">
                    let humidityMeas = "<?php echo $humidityMeas[$counter - 1] ?>";
                    let ctxHum = document.getElementById('humChart');

                    let presHum = new Chart(ctxHum, {
                        type: 'doughnut',
                        data: {
                            labels: ['Hum', ''],
                            datasets: [{
                                label: '',
                                data: [humidityMeas, 100],
                                backgroundColor: [
                                    'rgb(255,46,99)',
                                    'rgb(235, 237, 239)',
                                ],
                                borderColor: [
                                    'rgb(255,46,99)',
                                    'rgb(235, 237, 239)',
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            legend: {
                                display: false
                            },
                            elements: {
                                center: {
                                    text: humidityMeas + '%',
                                    color: '#ff2e63', // Default is #000000
                                    fontStyle: 'Calibri', // Default is Arial
                                    sidePadding: 20, // Default is 20 (as a percentage)
                                    minFontSize: 20, // Default is 20 (in px), set to false and text will not wrap.
                                    lineHeight: 25 // Default is 25 (in px), used for when text wraps
                                },
                            },
                            responsive: true,
                            maintainAspectRatio: true,
                            cutoutPercentage: 65
                        }
                    });
                </script>
                <p class="text" style="margin: 12px 0 0 118px; color: #262626">HUMIDITY</p>
            </div>
        </div>
    </div>
    <div id="stationMap" class="map"></div>
    <script type="text/javascript" src="../js/map.js"></script>

    <div class="dashboard-meas" style="width: 1365px; height: 320px">
        <div class="lineChart-container">
            <canvas style="margin-left: 20px" id="lineChartTemp" height="85" width="350"></canvas>
            <script type="text/javascript">
                let ctxLineTemp = document.getElementById("lineChartTemp");
                let dateArr = '<?php echo json_encode($dateMeas) ?>';
                let temperatureArr = '<?php echo json_encode($temperatureMeas)?>';
                let splittedArr = [];

                temperatureArr = JSON.parse(temperatureArr);
                dateArr = JSON.parse(dateArr);

                for (let i = 0; i < dateArr.length; i++){
                    splittedArr[i] = dateArr[i].split(" ");
                }

                let myLineChartTemp = new Chart(ctxLineTemp, {
                    type:"line",
                    data: {
                        labels:[splittedArr[0][1].slice(0, 5), splittedArr[1][1].slice(0, 5), splittedArr[2][1].slice(0, 5), splittedArr[3][1].slice(0, 5), splittedArr[4][1].slice(0, 5), splittedArr[5][1].slice(0, 5), splittedArr[6][1].slice(0, 5), splittedArr[7][1].slice(0, 5), splittedArr[8][1].slice(0, 5), splittedArr[9][1].slice(0, 5)],
                        datasets:[{
                            label:"Todays Temperature",
                            data:[temperatureArr[0], temperatureArr[1], temperatureArr[2], temperatureArr[3], temperatureArr[4], temperatureArr[5], temperatureArr[6], temperatureArr[7], temperatureArr[8], temperatureArr[9]],
                            fill:false,
                            borderColor:"rgb(8,217,214)",
                            lineTension:0.1
                        }]
                    },
                    options: {
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true
                                }
                            }]
                        }
                    }
                });
            </script>
        </div>
    </div>

    <div class="dashboard-meas" style="width: 1365px; height: 320px">
        <div class="lineChart-container">
            <canvas style="margin-left: 20px" id="lineChartPres" height="85" width="350"></canvas>
            <script type="text/javascript">
                let ctxLinePres = document.getElementById("lineChartPres");
                dateArr = '<?php echo json_encode($dateMeas) ?>';
                let pressureArr = '<?php echo json_encode($pressureMeas)?>';
                splittedArr = [];

                pressureArr = JSON.parse(pressureArr);
                dateArr = JSON.parse(dateArr);

                for (let i = 0; i < dateArr.length; i++){
                    splittedArr[i] = dateArr[i].split(" ");
                }

                let myLineChartPres = new Chart(ctxLinePres, {
                    type:"line",
                    data: {
                        labels:[splittedArr[0][1].slice(0, 5), splittedArr[1][1].slice(0, 5), splittedArr[2][1].slice(0, 5), splittedArr[3][1].slice(0, 5), splittedArr[4][1].slice(0, 5), splittedArr[5][1].slice(0, 5), splittedArr[6][1].slice(0, 5), splittedArr[7][1].slice(0, 5), splittedArr[8][1].slice(0, 5), splittedArr[9][1].slice(0, 5)],
                        datasets:[{
                            label:"Todays barometric Pressure",
                            data:[pressureArr[0], pressureArr[1], pressureArr[2], pressureArr[3], pressureArr[4], pressureArr[5], pressureArr[6], pressureArr[7], pressureArr[8], pressureArr[9]],
                            fill:false,
                            borderColor:"rgb(37,42,52)",
                            lineTension:0.1
                        }]
                    },
                    options: {
                        scales: {
                            yAxes: [{
                                ticks: {
                                    suggestedMin: 850,
                                    suggestedMax: 1100
                                }
                            }]
                        }
                    }
                });
            </script>
        </div>
    </div>

    <div class="dashboard-meas" style="width: 1365px; height: 320px">
        <div class="lineChart-container">
            <canvas style="margin-left: 20px" id="lineChartHum" height="85" width="350"></canvas>
            <script type="text/javascript">
                let ctxLineHum = document.getElementById("lineChartHum");
                dateArr = '<?php echo json_encode($dateMeas) ?>';
                let humidityArr = '<?php echo json_encode($humidityMeas)?>';
                splittedArr = [];

                humidityArr = JSON.parse(humidityArr);
                dateArr = JSON.parse(dateArr);

                for (let i = 0; i < dateArr.length; i++){
                    splittedArr[i] = dateArr[i].split(" ");
                }

                let myLineChartHum = new Chart(ctxLineHum, {
                    type:"line",
                    data: {
                        labels:[splittedArr[0][1].slice(0, 5), splittedArr[1][1].slice(0, 5), splittedArr[2][1].slice(0, 5), splittedArr[3][1].slice(0, 5), splittedArr[4][1].slice(0, 5), splittedArr[5][1].slice(0, 5), splittedArr[6][1].slice(0, 5), splittedArr[7][1].slice(0, 5), splittedArr[8][1].slice(0, 5), splittedArr[9][1].slice(0, 5)],
                        datasets:[{
                            label:"Todays humidity",
                            data:[humidityArr[0], humidityArr[1], humidityArr[2], humidityArr[3], humidityArr[4], humidityArr[5], humidityArr[6], humidityArr[7], humidityArr[8], humidityArr[9]],
                            fill:false,
                            borderColor:"rgb(255,46,99)",
                            lineTension:0.1
                        }]
                    },
                    options: {
                        scales: {
                            yAxes: [{
                                ticks: {
                                    suggestedMin: 30,
                                    suggestedMax: 90
                                }
                            }]
                        }
                    }
                });
            </script>
        </div>
    </div>
</body>
</html>