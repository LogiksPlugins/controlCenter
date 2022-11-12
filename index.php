<?php
if(!defined('ROOT')) exit('No direct script access allowed');

include_once __DIR__."/api.php";
loadNodeEnvironment();

loadModule("pages");

printPageComponent(false,[
		"toolbar"=>[
		    "refreshPage"=>["icon"=>"<i class='fa fa-refresh'></i>"],
		    ['type'=>"bar"],
		    
		    "clearOutput"=>["icon"=>"<i class='fa fa-trash'></i>"],
		    //"loadServerStats"=>["icon"=>"<i class='fa fa-tachometer-alt'></i>"],
		    //['type'=>"bar"],
		    "restartNodeServer"=>["icon"=>"<i class='fa fa-redo'></i>", "title"=> "Restart", "tips"=>"Restart Node Server", "align"=>"right"],
// 			"db"=>["title"=>"DB","align"=>"right","class"=>"active"],
// 			"fs"=>["title"=>"FS","align"=>"right"],
// 			"log"=>["title"=>"ERROR-LOG","align"=>"right"],
// 			"msg"=>["title"=>"MSG","align"=>"right"],
// 			"cache"=>["title"=>"CACHE","align"=>"right"],

			
			
			// "newCard"=>["icon"=>"<i class='fa fa-plus'></i>"],
			// "trash"=>["icon"=>"<i class='fa fa-trash'></i>"],
		],
		"sidebar"=> "pageSidebar",
		"contentArea"=>"pageContentArea"
	]);

echo _css(["controlCenter"]);
echo _js(["controlCenter", "chart"]);

function pageSidebar() {
    return "<ul id='script-list' class='list-group script-list'></ul>";
}

function pageContentArea() {
	return "<div class='container-fluid container-card' style='height: 100%;'>
	    <div class='row' style='height:100%;'>
	        <div class='col-md-8'>
	            <div class='panel panel-default'>
                  <div class='panel-heading hide hidden d-none'>Output Results</div>
                  <div class='panel-body output_results'>
                    <pre></pre>
                  </div>
                </div>
	        </div>
	        <div class='col-md-4' style='padding: 0px;'>
	            <div class='panel panel-default text-center'>
                    <div id='canvas-holder' style='width: 200px;height:200px;margin:auto;'><canvas id='disk_capacity' /></div>
                </div>
	            <div class='panel panel-default'>
                  <div class='panel-heading'>Server Stats <i class='fa fa-refresh pull-right reload_server_stats'></i></div>
                  <div class='panel-body' style='padding: 2px;height: 60%;overflow: auto;'>
                    <table class='table' style='margin-bottom: 0px;'>
                        <tbody id='server_stats'>
                        </tbody>
                    </table>
                  </div>
                </div>
	        </div>
	    </div>
	</div>";
}
?>
<style>
.pageCompContainer.withSidebar .pageCompContent {
    overflow: hidden;
}
.container-card {
    padding-left: 10px;
    padding-right: 10px;
}
.script-list li {
    cursor: pointer;
}
.card {
    
}
.output_results {
    padding: 2px;
    background: black;
    color: white;
    height: 100%;
    overflow:auto;
}
.output_results pre {
    padding: 2px;
    background: transparent;
    border: 0px;
    color: white;
}
.output_results pre hr {
    margin: 2px;
    padding: 0px;
    height: 0px;
    opacity: 0.4;
}
#server_stats td {
    word-break: break-all;
    word-wrap: break-word;
    overflow-wrap: break-word;
    -webkit-hyphens: auto;
    -moz-hyphens: auto;
    -ms-hyphens: auto;
    hyphens: auto;
}
</style>
<script>
window.chartColors = {
	red: 'rgb(255, 99, 132)',
	orange: 'rgb(255, 159, 64)',
	yellow: 'rgb(255, 205, 86)',
	green: 'rgb(75, 192, 192)',
	blue: 'rgb(54, 162, 235)',
	purple: 'rgb(153, 102, 255)',
	grey: 'rgb(201, 203, 207)'
};
var script_running = false;
var server_connected = false;
var server_stats = {};
$(function() {
    $(".script-list").delegate("li[data-src]", "click", function() {
        $(".script-list li.active").removeClass("active");
        $(this).addClass("active");
        var src = $(this).data("src");
        runControlScript(src, this);
    })
    
    $(".reload_server_stats").click(loadServerStats);
    
    runConnectionTest();
});
function refreshPage() {
    window.location.reload();
}

function runConnectionTest() {
    server_connected = false;
    $(".output_results>pre").html("Connecting To Server ...");
    processAJAXQuery(_service("controlCenter", "test"), function(data) {
        if(data.Data) {
            $(".output_results>pre").append("\nServer Connected Successfully");
            loadServerStats();
            server_connected = true;
        } else {
            server_connected = false;
            $(".output_results>pre").append("\nFailed to connect to server");
            $("#server_stats").html("<h4 align=center>Server Not Connected</h4>");
        }
        loadScriptList();
    }, "json");
}
function restartNodeServer() {
    var ans = confirm("Are you Sure, This will restart NODEJS Server?");
    if(ans) {
        $(".output_results>pre").append("<hr>Restarting Server");
        processAJAXQuery(_service("controlCenter", "restart"), function(data) {
            $(".output_results>pre").append("\nChecking in 3 secs, Will auto connect after restart<hr>\n\n");
            
            setTimeout(runConnectionTest, 1000);
        });
    }
}
function loadServerStats() {
    $("#server_stats").html("<div class='ajaxloading ajaxloading3'></div>");
    processAJAXQuery(_service("controlCenter", "stats"), function(data) {
        if(data.Data.length<=0) {
            $("#server_stats").html("<h4 align=center>Server Not Connected</h4>");
        } else {
            $("#server_stats").html("");
            $.each(data.Data, function(k, v) {
                $("#server_stats").append(`<tr><th style='text-transform: uppercase;'>${k}</th><td align=left>${v}</td></tr>`);
            });
            server_stats = data.Data
            renderCharts();
        }
    }, "json");
}

function loadScriptList() {
    $("#script-list").html("<div class='ajaxloading ajaxloading3'></div>");
    processAJAXQuery(_service("controlCenter", "list_scripts"), function(data) {
        $("#script-list").html("");
        $.each(data.Data, function(k, scrpt) {
            var title= toTitle(scrpt);
            if(server_connected)
                $("#script-list").append(`<li data-src='${scrpt}' class='list-group-item'>${title}</li>`);
            else
                $("#script-list").append(`<li data-src='${scrpt}' class='list-group-item disabled'>${title}</li>`);
        });
    }, "json");
}
function runControlScript(src) {
    if(script_running) {
        lgksToast("You can only run one script at a time.");
        return;
    }
    $(".output_results>pre").append("<hr>Running Script : "+ src);
    
    processAJAXPostQuery(_service("controlCenter", "form_script"), "src="+src, function(data) {
        if(data.Data) {
            lgksToast("Script Forms Not Yet Supported");
            
            //To be removed after form rendering is ready
            script_running = true;
            processAJAXPostQuery(_service("controlCenter", "run_script"), "src="+src, function(dataRaw) {
                $(".output_results>pre").append("\n"+dataRaw);
                script_running = false;
                $(".output_results").scrollTop($(".output_results>pre").height());
            });
        } else {
            script_running = true;
            processAJAXPostQuery(_service("controlCenter", "run_script"), "src="+src, function(dataRaw) {
                $(".output_results>pre").append("\n"+dataRaw);
                script_running = false;
                $(".output_results").scrollTop($(".output_results>pre").height());
            });
        }
    }, "json");
}
function clearOutput() {
    $(".output_results>pre").html("");
}

function renderCharts() {
    renderChartDiskCapacity()
}

function renderChartDiskCapacity() {
    if(server_stats.DISK_CAPACITY==null) {
        $("#disk_capacity").closest(".panel").hide();
        return;
    }
    var v1 = parseInt(server_stats.DISK_CAPACITY);
    
    var data1 = {
            labels: [
                "Used Disk",
                "Available Disk",
                //"Not-Available",
            ],
            datasets: [{
                data: [
                    v1,
                    (100-v1),
                ],
                //borderColor: window.chartColors.red,
                backgroundColor: [
                    window.chartColors.red,
                    window.chartColors.orange,
                    window.chartColors.yellow,
                    window.chartColors.green,
                    window.chartColors.blue,
                ],
                fill: false,
                label: 'Disk Capacity'
            }],
        };
        
    var ctx = document.getElementById("disk_capacity").getContext("2d");
    window.myPie = new Chart(ctx, {
        type: 'pie',
        data: data1,
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true
                },
                legend: {
                    display: true,
                    position: 'right'
                }
            }
        }
    });
}

function renderChartsOld() {
    var data1 = {
            labels: [
                "Red",
                "Orange",
                "Yellow",
                "Green",
                "Blue"
            ],
            datasets: [{
                data: [
                    2,
                    3,
                    2,
                    4,
                    5,
                ],
                //borderColor: window.chartColors.red,
                backgroundColor: [
                    window.chartColors.red,
                    window.chartColors.orange,
                    window.chartColors.yellow,
                    window.chartColors.green,
                    window.chartColors.blue,
                ],
                fill: false,
                label: 'Dataset 1'
            }],
        };
    
    var ctx = document.getElementById("chart1").getContext("2d");
    window.myPie = new Chart(ctx, {
        type: 'pie',
        data: data1,
        options: {
            responsive: true,
            legend: {
                display: false
            }
        }
    });
    
    var ctx = document.getElementById("chart2").getContext("2d");
    window.myPie = new Chart(ctx, {
        type: 'bar',
        data: data1,
        options: {
            responsive: true,
            legend: false
        }
    });
    
    var ctx = document.getElementById("chart3").getContext("2d");
    window.myPie = new Chart(ctx, {
        type: 'line',
        data: data1,
        options: {
            responsive: true,
            legend: false
        }
    });
}
</script>