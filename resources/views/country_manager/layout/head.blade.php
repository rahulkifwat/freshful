<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Freshful — Country Manager</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <link rel="icon" href="{{ url('img/Fevi.png') }}" type="image/gif" sizes="16x16">
  <link rel="stylesheet" href="{{ url('assets/admin/css/font-awesome.min.css') }}">
  <link rel="stylesheet" href="{{ url('assets/admin/css/simple-line-icons.css') }}">
  <link rel="stylesheet" href="{{ url('assets/admin/css/linea-basic.css') }}">
  <link rel="stylesheet" href="{{ url('assets/admin/css/pe-icon-7-stroke.css') }}">
  <link rel="stylesheet" href="{{ url('assets/admin/css/countrySelect.min.css') }}">
  <link rel="stylesheet" href="{{ url('assets/admin/css/perfect-scrollbar.css') }}">
  <link rel="stylesheet" href="{{ url('assets/admin/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ url('assets/admin/css/owl.carousel.min.css') }}">
  <link rel="stylesheet" href="{{ url('assets/admin/css/presets.css') }}">
  <link rel="stylesheet" href="{{ url('assets/admin/css/style.css') }}">
  <link rel="stylesheet" href="{{ url('assets/admin/css/index-01.css') }}">
  <link rel="stylesheet" href="{{ url('assets/admin/css/responsive.css') }}">
  <link rel="stylesheet" href="{{ url('assets/admin/css/tables/tables.css') }}">

  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.0.1/css/buttons.dataTables.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" integrity="sha512-vKMx8UnXk60zUwyUnUPM3HbQo8QfmNx7+ltw8Pm5zLusl1XIfwcxo8DbWCqMGKaWeNxWA8yrx5v3SaVpMvR3CA==" crossorigin="anonymous" />
  <link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet" />
</head>
<style type="text/css">
  .full-wdt { float: left; width: 100%; }
  .select2-container { width: 100% !important; height: 97%; }
  .select2-container--default .select2-selection--single { height: 35px; padding: 4px; }
  .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable { background-color: #ec3f34; }
</style>
