jQuery(document).ready(function () {

    $.getJSON("epaLogs.php", function (data) {
        console.info("success");
    })
    .done(function (data) {
        drawRequestPerMinute(data.per_minute);
        drawRequestMethods(data.methods);
        drawResponseCodes(data.codes);
        drawAvgSize(data.avg_size);
    })
    .fail(function (error) {
        console.log("error", error);
    });


});

function drawAvgSize(data) {
    const line = Object.keys(data).map(function(indx) {
        const tms = data[indx];
        return {
            x: new Date(indx * 1000),
            y: tms.average,
            cnt: tms.count
        };
    });
    var chart = new CanvasJS.Chart("chartAvgSize", {
        theme: "light2", // "light1", "light2", "dark1", "dark2"
        title: {
            text: "Average request size per hour"
        },
        axisX: {
            valueFormatString: "YYYY MM DD HH:mm:ss"
        },
        toolTip: {
            shared: true
        },
        data: [
            {
                type: "line",
                name: 'Average',
                showInLegend: true,
                toolTipContent: "Based on : {cnt} requests. <br> total: {y}b",
                dataPoints: line
            }
        ]
    });
    chart.render();
}

function drawRequestMethods(data) {

    const columns = Object.keys(data).map(function(method) {
        return {
            label: method,
            y: data[method]
        };
    });
    var chart = new CanvasJS.Chart("chartMethods", {
        animationEnabled: true,
        theme: "light2", // "light1", "light2", "dark1", "dark2"
        title:{
            text: "HTTP Methods"
        },
        axisY: {
            title: "Method"
        },
        data: [{
            type: "column",
            showInLegend: true,
            legendMarkerColor: "grey",
            legendText: "Total method requests",
            dataPoints: columns
        }]
    });
    chart.render();
}

function drawResponseCodes(data) {

    const columns = Object.keys(data).map(function(code) {
        return {
            label: code,
            y: data[code]
        };
    });
    var chart = new CanvasJS.Chart("chartCodes", {
        animationEnabled: true,
        theme: "light2", // "light1", "light2", "dark1", "dark2"
        title:{
            text: "Response Codes"
        },
        axisY: {
            title: "Method"
        },
        data: [{
            type: "column",
            showInLegend: true,
            legendMarkerColor: "grey",
            legendText: "Total request response codes",
            dataPoints: columns
        }]
    });
    chart.render();
}


function drawRequestPerMinute(data) {

    const lines = Object.keys(data).map(function(method) {
        return {
            type: "line",
            name: 'Method: ' + method,
            showInLegend: true,
            toolTipContent: method + ": {label}: <br> Value: {y}",
            dataPoints: Object.keys(data[method]).map(function(indx) {
                const tms = new Date(indx * 1000);
                return {
                    x: tms,
                    label: tms.toLocaleDateString() + ' ' + tms.toLocaleTimeString(),
                    y: data[method][indx]
                };
            })
        }
    });

    var chart = new CanvasJS.Chart("chartPerMinute", {
        theme: "light2", // "light1", "light2", "dark1", "dark2"
        title: {
            text: "Request per minute"
        },
        axisX: {
            valueFormatString: "YYYY MM DD HH:mm:ss"
        },
        axisY2: {
            title: "Requests",
            prefix: "",
            suffix: "u"
        },
        toolTip: {
            shared: true
        },
        legend: {
            cursor: "pointer",
            verticalAlign: "top",
            horizontalAlign: "center",
            dockInsidePlotArea: true,
            itemclick: toogleDataSeries
        },
        data: lines
    });
    chart.render();

    function toogleDataSeries(e){
        if (typeof(e.dataSeries.visible) === "undefined" || e.dataSeries.visible) {
            e.dataSeries.visible = false;
        } else{
            e.dataSeries.visible = true;
        }
        chart.render();
    }
}

