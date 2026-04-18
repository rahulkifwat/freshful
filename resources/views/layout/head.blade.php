 <head>
   <meta charset="utf-8">
   <meta name="viewport" content="width=device-width, initial-scale=1">


   <title>{{ config('app.name', 'Freshful') }}</title>
   <meta name="description" content="">
   <meta name="keywords" content="">

   <!-- Favicons -->
   <link
     rel="icon"
     type="image/png"
     href="{{ asset('assets/image/Fevi.png')}}"
     sizes="32x32" />
   <!-- 

   <link href="{{ url('assets/img/apple-touch-icon.png')}}" rel="apple-touch-icon"> -->



   <!-- Main CSS File -->
   <!-- <link href="{{ url('assets/css/main.css')}}" rel="stylesheet"> -->






   <!-- Fonts -->
   <link rel="preconnect" href="https://fonts.bunny.net">
   <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

   <!-- swiper slider -->
   <link
     rel="stylesheet"
     href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css" />

   <script src="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
   <!-- Styles / Scripts -->
   @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
   @vite(['resources/css/app.css', 'resources/js/app.js','resources/css/custom-bootstrap.css'])
   @else
   <style>



   </style>
   @endif
 </head>