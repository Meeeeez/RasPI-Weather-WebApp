let map = L.map('stationMap').setView([46.642, 11.678], 15);
document.getElementsByClassName('leaflet-control-attribution')[0].style.display = 'none';

L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token={accessToken}', {
    attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
    id: 'mapbox/streets-v11',
    maxZoom: 18,
    tileSize: 512,
    zoomOffset: -1,
    accessToken: 'pk.eyJ1IjoieG1leiIsImEiOiJja2poMGJkemkwd3d0MnVudjU3ZHQ5Nmw2In0.1J6biorJxLMrGGUh6fYB9g'
}).addTo(map);

L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributor',
}).addTo(map);

let circle = L.circle([46.642, 11.678], {
    color: "#252a34",
    fillColor: 'rgb(37,42,52)',
    fillOpacity: 0.2,
    radius: 350,
}).addTo(map);
circle.bindPopup("The station is somewhere here");