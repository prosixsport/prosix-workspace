<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Browser Tab Title -->
    <title>Prosix Sports</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('assets/images/P LOGO BLACK.png') }}">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Dynamic Tab Title + Favicon -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {

            const originalTitle = "Prosix Sports";

            const titles = [
                "🚀 Prosix Sports",
                "🔥 Prosix Sports",
                "⚡ Prosix Sports",
                "💥 Prosix Sports"
            ];

            let index = 0;

            function animateTitle() {
                document.title = titles[index];
                index = (index + 1) % titles.length;
            }

            let interval = setInterval(animateTitle, 2000);

            document.addEventListener("visibilitychange", function () {

                if (document.hidden) {
                    interval = setInterval(animateTitle, 2000);
                } else {
                    clearInterval(interval);
                    document.title = originalTitle;
                }

            });

            const favicon = document.querySelector("link[rel='icon']");
            let toggle = false;

            setInterval(() => {
                if (document.hidden) {
                    favicon.href = toggle
                        ? "{{ asset('assets/images/P LOGO BLACK.png') }}"
                        : "{{ asset('assets/images/P LOGO WHITE.png') }}";
                    toggle = !toggle;
                } else {
                    favicon.href = "{{ asset('assets/images/P LOGO BLACK.png') }}";
                }
            }, 1000);

        });
    </script>

</head>

<body>
    <div id="app"></div>
</body>
</html>
