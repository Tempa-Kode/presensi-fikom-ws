<!doctype html>
<html lang="id" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta http-equiv="Content-Language" content="id" />
    <meta name="msapplication-TileColor" content="#2d89ef">
    <meta name="theme-color" content="#4188c9">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="HandheldFriendly" content="True">
    <meta name="MobileOptimized" content="320">
    <link rel="icon" href="{{ asset("favicon.ico") }}" type="image/x-icon" />
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset("favicon.ico") }}" />
    <title>@yield("title")</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,300i,400,400i,500,500i,600,600i,700,700i&amp;subset=latin-ext">

    <!-- Dashboard Core CSS -->
    <link href="{{ asset("assets/css/dashboard.css") }}" rel="stylesheet" />

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.4/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.dataTables.min.css" />

    @stack("styles")
    <style>
        /* Reset body dan html */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        /* Sticky Footer Styles dengan prioritas tinggi */
        .page.sticky-footer {
            display: flex !important;
            flex-direction: column !important;
            min-height: 100vh !important;
        }

        .page.sticky-footer .page-main {
            flex: 1 !important;
            display: flex !important;
            flex-direction: column !important;
        }

        .page.sticky-footer footer.footer {
            margin-top: auto !important;
            flex-shrink: 0 !important;
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
            padding: 1rem 0;
        }

        /* Override dashboard.css jika perlu */
        .page {
            position: relative !important;
        }

        /* Pastikan konten utama mengisi ruang */
        .my-3.my-md-5 {
            flex: 1;
        }
    </style>
</head>

<body class="">
    <div class="page sticky-footer">
        <div class="page-main">
            <div class="header py-4">
                <div class="container">
                    <div class="d-flex">
                        <a class="header-brand" href="{{ route("dashboard") }}">
                            <img src="{{ asset("assets/images/logo-fikom.svg") }}" class="header-brand-img"
                                alt="Logo Fikom"> Dashboard Admin
                        </a>
                        <div class="d-flex order-lg-2 ml-auto">
                            <div class="dropdown">
                                <a href="#" class="nav-link pr-0 leading-none" data-toggle="dropdown">
                                    <span class="avatar"
                                        style="background-image: url({{ asset('demo/faces/female/25.jpg') }})"></span>
                                    <span class="ml-2 d-none d-lg-block">
                                        <span class="text-default">{{ Auth::user()->nama }}</span>
                                        <small class="text-muted d-block mt-1">{{ Auth::user()->email }}</small>
                                    </span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right dropdown-menu-arrow">
                                    <a class="dropdown-item" href="#">
                                        <i class="dropdown-icon fe fe-user"></i> Profile
                                    </a>
                                    <a class="dropdown-item" href="#">
                                        <i class="dropdown-icon fe fe-log-out"></i> Sign out
                                    </a>
                                </div>
                            </div>
                        </div>
                        <a href="#" class="header-toggler d-lg-none ml-3 ml-lg-0" data-toggle="collapse"
                            data-target="#headerMenuCollapse">
                            <span class="header-toggler-icon"></span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="header collapse d-lg-flex p-0" id="headerMenuCollapse">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg order-lg-first">
                            <ul class="nav nav-tabs border-0 flex-column flex-lg-row">
                                <li class="nav-item">
                                    <a href="{{ route("dashboard") }}"
                                        class="nav-link {{ Route::currentRouteName() == "dashboard" ? "text-primary" : "" }}"><i
                                            class="fe fe-home"></i> Home</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route("data.dosen") }}"
                                        class="nav-link {{ Route::currentRouteName() == "data.dosen" ? "text-primary" : "" }}"><i
                                            class="fe fe-box"></i> Data Dosen</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route("data.mahasiswa") }}"
                                        class="nav-link {{ Route::currentRouteName() == "data.mahasiswa" ? "text-primary" : "" }}"><i
                                            class="fe fe-box"></i> Data Mahasiswa</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route("data.prodi" )}}"
                                        class="nav-link {{ Route::currentRouteName() == "data.prodi" ? "text-primary" : "" }}"><i
                                            class="fe fe-box"></i> Data Prodi</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route("data.kelas" )}}"
                                        class="nav-link {{ Route::currentRouteName() == "data.kelas" ? "text-primary" : "" }}"><i
                                            class="fe fe-box"></i> Data Kelas</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route("data.tahun_akademik" )}}"
                                        class="nav-link {{ Route::currentRouteName() == "data.tahun_akademik" ? "text-primary" : "" }}"><i
                                            class="fe fe-box"></i> Data TA</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route("data.matakuliah" )}}"
                                        class="nav-link {{ Route::currentRouteName() == "data.matakuliah" ? "text-primary" : "" }}"><i
                                            class="fe fe-box"></i> Data Matakuliah</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route("data.ruangan" )}}"
                                        class="nav-link {{ Route::currentRouteName() == "data.ruangan" ? "text-primary" : "" }}"><i
                                            class="fe fe-box"></i> Data Ruangan</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route("data.jam" )}}"
                                        class="nav-link {{ Route::currentRouteName() == "data.jam" ? "text-primary" : "" }}"><i
                                            class="fe fe-box"></i> Data Jam</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="my-3 my-md-5">
                @yield("body")
            </div>
        </div>
        <footer class="footer">
            <div class="container">
                <div class="row align-items-center flex-row-reverse">
                    <div class="col-12 col-lg-auto mt-3 mt-lg-0 text-center">
                        Copyright © 2025 <a href=".">Jodie Arya</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

    <!-- Bootstrap JS (jika diperlukan) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/2.3.4/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.min.js"></script>

    <!-- Dashboard Core JS (tanpa RequireJS) -->
    <script src="{{ asset("assets/js/dashboard.js") }}"></script>
    @stack("scripts")
    <script type="text/javascript">
        $(document).ready(function() {
            // Debug info
            console.log('=== Library Check ===');
            console.log('jQuery version:', typeof jQuery !== 'undefined' ? jQuery.fn.jquery : 'Not loaded');
            console.log('DataTables available:', typeof $.fn.DataTable !== 'undefined' ? 'Yes' : 'No');
            console.log('Elements with class datatable:', $('.datatable').length);

            // Pastikan jQuery dan DataTables tersedia
            if (typeof jQuery !== 'undefined' && typeof $.fn.DataTable !== 'undefined') {
                // Initialize DataTables untuk semua tabel dengan class datatable
                $('.datatable').each(function(index) {
                    var tableId = $(this).attr('id') || 'table-' + index;
                    console.log('Initializing DataTable for:', tableId);

                    if (!$.fn.DataTable.isDataTable(this)) {
                        $(this).DataTable({
                            responsive: true,
                            pageLength: 10,
                            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
                            language: {
                                "sProcessing": "Sedang memproses...",
                                "sLengthMenu": "Tampilkan _MENU_ entri",
                                "sZeroRecords": "Tidak ditemukan data yang sesuai",
                                "sInfo": "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                                "sInfoEmpty": "Menampilkan 0 sampai 0 dari 0 entri",
                                "sInfoFiltered": "(disaring dari _MAX_ entri keseluruhan)",
                                "sInfoPostFix": "",
                                "sSearch": "Cari:",
                                "sUrl": "",
                                "oPaginate": {
                                    "sFirst": "Pertama",
                                    "sPrevious": "Sebelumnya",
                                    "sNext": "Selanjutnya",
                                    "sLast": "Terakhir"
                                }
                            },
                            initComplete: function () {
                                console.log('✓ DataTable initialized successfully for:', tableId);
                            },
                            error: function(xhr, status, error) {
                                console.error('DataTable error for ' + tableId + ':', error);
                            }
                        });
                    } else {
                        console.log('DataTable already initialized for:', tableId);
                    }
                });
            } else {
                console.error('Required libraries not loaded - jQuery:', typeof jQuery !== 'undefined', 'DataTables:', typeof $.fn.DataTable !== 'undefined');
            }
        });
    </script>
</body>
</html>
