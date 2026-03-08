<!DOCTYPE html>
<html>
<head>
    <title>Mesas</title>

    <style>
        body{
            font-family:Arial;
            padding:40px;
            text-align:center;
            background:#f5f5f5;
        }

        .mesas{
            display:grid;
            grid-template-columns:repeat(4,1fr);
            gap:20px;
            margin-top:40px;
        }

        .mesa{
            padding:40px;
            border-radius:12px;
            font-size:22px;
            cursor:pointer;
            color:white;
            font-weight:bold;
        }

        .libre{
            background:#27ae60;
        }

        .ocupada{
            background:#e74c3c;
        }

        a{
            text-decoration:none;
        }
    </style>
</head>
<body>

    <h1>Mesas</h1>

    <div class="mesas">
        @foreach($mesas as $mesa)
            <a href="/pos/mesa/{{ $mesa }}">
                <div class="mesa {{ in_array($mesa, $ocupadas) ? 'ocupada' : 'libre' }}">
                    Mesa {{ $mesa }}
                </div>
            </a>
        @endforeach
    </div>

</body>
</html>