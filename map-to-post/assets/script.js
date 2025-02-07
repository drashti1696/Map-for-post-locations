// var map = L.map('map', {
//     center: [51.505, -0.09],
//     zoom: 13
// });

// function geocodeAddress(address) {
//     var url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`;

//     fetch(url)
//         .then(response => response.json())
//         .then(data => {
//             if (data.length > 0) {
//                 var lat = data[0].lat;
//                 var lon = data[0].lon;
//                 console.log(`Address: ${address}`);
//                 console.log(`Latitude: ${lat}, Longitude: ${lon}`);
//             } else {
//                 console.log("Address not found!");
//             }
//         })
//         .catch(error => console.error("Error fetching geocode:", error));
// }

