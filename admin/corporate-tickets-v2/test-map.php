<!DOCTYPE html>
<html>
<head>
<title>Google Map Draw Area</title>

<style>
#map{
height:500px;
width:100%;
}
</style>

</head>

<body>

<h2>Draw Service Area</h2>

<div id="map"></div>

<script>

function initMap(){

    const map = new google.maps.Map(document.getElementById("map"), {
        zoom: 12,
        center: {lat:28.6139, lng:77.2090}
    });

    const drawingManager = new google.maps.drawing.DrawingManager({
        drawingMode: google.maps.drawing.OverlayType.POLYGON,
        drawingControl: true,
        drawingControlOptions: {
            drawingModes: ['polygon']
        }
    });

    drawingManager.setMap(map);

}

</script>

<script async defer
src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDU0suOSG-X34RvDzawjDGHbX1C5JrHHsw&libraries=drawing&callback=initMap">
</script>

</body>
</html>