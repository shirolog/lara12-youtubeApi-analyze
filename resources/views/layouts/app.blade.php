<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'YouTube分析ツール')</title>
    <!-- bootstrap cdn -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

        <!-- jquery cdn -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

        <style>
        .chart-title {
            text-align: center;
            font-weight: 600;
            margin-bottom: 30px;
            margin-top: 30px;
            background: linear-gradient(90deg, #FFD700, #1E90FF);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
</style>

</head>
<body>
    <header class="bg-dark text-white p-3 mb-4">
        <div class="container">
            <h1>YouTube分析ツール</h1>
        </div>
    </header>

    <main class="container">
        @yield('content')
    </main>

    <footer class="bg-light text-center p-3 mt-4">
        © 2025 Your Company
    </footer>


    

    <!-- bootstrap js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-3d"></script>


</body>
</html>