@extends('layout.master')
@section('content')

<main class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <h2 class="fw-bold mb-4">FSSAI Certificate</h2>
                <p class="text-muted mb-4">Freshful Digital Foods (OPC) Private Limited is certified by the Food Safety and Standards Authority of India (FSSAI) ensuring the highest standards of food safety and quality.</p>
                <div class="border rounded shadow-sm p-3">
                    <img src="{{ asset('uploads/images/certificates/fssai.jpg') }}" alt="FSSAI Certificate" class="img-fluid" style="max-width: 70%; margin: auto; display: block;">
                </div>
            </div>
        </div>
    </div>
</main>

@endsection
