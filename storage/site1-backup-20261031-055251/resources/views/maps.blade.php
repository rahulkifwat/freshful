@extends('layout.master')
@section('content')

<style>
#map { height: 450px; width: 100%; border-radius: 8px; }
.hub-card { cursor: pointer; transition: all 0.2s; }
.hub-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.12); border-color: #0066ee !important; }
</style>

<main class="py-5">
    <div class="container-fluid container-xl">
        <h2 class="fw-bold text-center mb-2">Find Our Stores / Hubs</h2>
        <p class="text-center text-muted mb-4">Locate our delivery hubs near you</p>

        <div class="row g-3 mb-4">
            <div class="col-md-8">
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-primary"></i></span>
                    <input type="text" id="searchInput" class="form-control border-start-0" placeholder="Search your location...">
                    <button class="btn btn-primary" onclick="getLocation()">
                        <i class="bi bi-crosshair"></i> Use My Location
                    </button>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div id="map"></div>
            </div>
            <div class="col-lg-4">
                <h5 class="fw-bold mb-3">Our Hubs</h5>
                <div id="hub-list" style="max-height:440px;overflow-y:auto;">
                    @forelse($hubs as $hub)
                    <div class="card hub-card border mb-3 p-3" onclick="panToHub({{ $hub->lat ?? 0 }}, {{ $hub->lng ?? 0 }})">
                        <div class="d-flex align-items-start gap-3">
                            <i class="bi bi-geo-alt-fill text-primary fs-4 mt-1"></i>
                            <div>
                                <h6 class="mb-1 fw-bold">{{ $hub->hub_name }}</h6>
                                @if($hub->lat && $hub->lng)
                                <p class="mb-0 fs-7 text-muted"><i class="bi bi-pin-map me-1"></i>{{ $hub->lat }}, {{ $hub->lng }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <p class="text-muted">No hubs found.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</main>

@push('scripts')
<script>
var map, markers = [];
var hubData = @json($hubs);

function initMap() {
    var defaultCenter = { lat: 26.8467, lng: 80.9462 }; // Lucknow default

    map = new google.maps.Map(document.getElementById('map'), {
        zoom: 11,
        center: defaultCenter
    });

    hubData.forEach(function(hub) {
        if (hub.lat && hub.lng) {
            var marker = new google.maps.Marker({
                position: { lat: parseFloat(hub.lat), lng: parseFloat(hub.lng) },
                map: map,
                title: hub.hub_name
            });

            var infowindow = new google.maps.InfoWindow({
                content: '<strong>' + hub.hub_name + '</strong><br>' + (hub.address || '')
            });

            marker.addListener('click', function() {
                infowindow.open(map, marker);
            });

            markers.push(marker);
        }
    });
}

function panToHub(lat, lng) {
    if (lat && lng) {
        map.panTo({ lat: parseFloat(lat), lng: parseFloat(lng) });
        map.setZoom(15);
    }
}

function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(pos) {
            var latlng = { lat: pos.coords.latitude, lng: pos.coords.longitude };
            map.panTo(latlng);
            map.setZoom(13);
            new google.maps.Marker({
                position: latlng,
                map: map,
                title: 'Your Location',
                icon: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png'
            });
        });
    } else {
        alert('Geolocation is not supported by your browser.');
    }
}
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAOZz0ZH-6fFQg8vixFsEtvrq006HXoZwA&callback=initMap" async defer></script>
@endpush

@endsection
