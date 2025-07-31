<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <!-- Meta Information -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="shortcut icon"
        href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAAAAXNSR0IArs4c6QAAAERlWElmTU0AKgAAAAgAAYdpAAQAAAABAAAAGgAAAAAAA6ABAAMAAAABAAEAAKACAAQAAAABAAAAIKADAAQAAAABAAAAIAAAAACshmLzAAADQ0lEQVRYCbVXXXLaMBCWaGfIW+kNdIPCS9v0BeUEhRMEThByAuAEDSconCDkBJiX/jxBTxDfoDwm04mVb72WbbBkTIGdEZJ3V7ufdlc/SFGRtF41hHjqCCHbaE0hjMJU8GLa4HeNFoK/FOJiHgQt4u0luU9D659KiDc3QkQ96FqH+6aRfAow4yC4DOnDR14AvOJ/Qzge+CZX48tREHwa+3SdAHjVcoFJyjfxQH6IaFy5olEAcAbnFqsTRM1KqT/SORXdFC1Ec5FC8S7YRyZOAXDOjwo7Cu5zH05uM/OFUQKCdhRTCkAIKrhjcm4oAqAo6fnL8auEeCZfMcUAOCy+apdrq1zeWwDeFOSnD2wqkgjIFFFeC5XbxxZqgTfZ5he+wiD4MicuV7qZFTQKDDkgVo1zL3r0UaR0Ve92ZAizGRNAbnUCmRJA9FALXZZFtxCEqTAbXJNviVD0oPw94++OKAWmuc2NHrDizjbP/+X3Yfpv4bztn0qSXefEq7VhVNMIhPAXj1uO7BOA1xrQwVFuYuXtH9lGDdDFcjDBKG3ZuD1q/etb3oLWv4eo9L8sN/fuRcQzmgAQ32r5+f8zpqrWNJFXbkY0rkCKdgGF6BSk2AiFvTI1km1YacIGWmElzQOUUISCDFeJArZdfcq5dXmQQ61/wE7t2l1wrjligwjI0CkqMCNUe+krR8E5itG1awrGLCMEgOiP/SrpAwBds5wOoJPRes9BREfx5dTljqv9+RGyKulzmQDP9AGArkavoTmUHvAmXAfBxzgCyQHTAR/zKOQF2oAzR9NoCq2E6u9rSV5nHi04omM6Wtl9DrAr5jmdw4xp8bsgvh8IjI+m5Bs1QGTuuC/9VYnU9h7li9gpL6yswLmWpLWC45RA3NhvRw+5XALsvUOWZ82hM0Havpa8qCeI0oAm5QDEtYDwCkWCM1KI8wRp4i2dpIAeEsQwV3AMhbMRbNPzPDtPUgDkkq/Vly6GUDw5weZLd/fqTlOQd4eKV8jOAjz0J6GQV158N2xFwLpilPE2QjEdTbBBOS86J8vOCORdcjTECKq4ZCoT6knMsOo7n2NraS8Aq5idgELjBPwA4wqyRiKHQ9rzZo22POTv+Suv7yMtDTWEQwAAAABJRU5ErkJggg==">

    {{ \IGE\ChannelLister\ChannelLister::css() }}

    <meta name="robots" content="noindex, nofollow">

    <title>ChannelLister{{ config('app.name') ? ' - ' . config('app.name') : '' }}</title>

    {{-- Include jQuery --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

    {{-- Include jQuery Validation --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/additional-methods.min.js"></script>

    {{-- Include jQuery Editable Select --}}
    <script src="https://cdn.jsdelivr.net/npm/jquery-editable-select@2.2.5/dist/jquery-editable-select.min.js"></script>
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/jquery-editable-select@2.2.5/dist/jquery-editable-select.min.css">

    {{-- Include Bootstrap --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
        integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous">
    </script>

    {{-- Include Bootstrap Select --}}
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>

    {{-- Include Bootstrap Maxlength --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-maxlength@2.0.0/dist/bootstrap-maxlength.min.js"></script>

    {{-- Include DataTables --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.dataTables.css" />
    <script src="https://cdn.datatables.net/2.3.2/js/dataTables.js"></script>
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        {{-- Navigation --}}
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <a class="navbar-brand" href="#">
                <img src="" width="30" height="30" class="d-inline-block align-top" alt="">
                Channel Lister
            </a>

            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
                aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Home</a>
                    </li>
                    <li class="nav-item">
                        <a @class(['nav-link', 'active' => request()->routeIs('channel-lister')]) class="nav-link" href="{{ route('channel-lister') }}">Channel
                            Lister</a>
                    </li>
                    <li class="nav-item">
                        <a @class([
                            'nav-link',
                            'active' => request()->routeIs('channel-lister-field.index'),
                        ]) class="nav-link"
                            href="{{ route('channel-lister-field.index') }}">Channel
                            Lister Fields</a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>

    </div>
</body>
@stack('footer-scripts')

</html>
