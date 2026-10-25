flowchart TB
    user([Usuario])

    subgraph app["Aplicativo de análisis de artículos"]
        direction TB
        frontend["«component»\nFrontend · React 19.0.0"]
        inertia["«component»\nIntegración Inertia.js\n@inertiajs/react 2.0.3 · inertiajs/inertia-laravel 2.0.28"]
        backend["«component»\nBackend · Laravel 12.70.0"]
        database[("MySQL\nanalisis_algoritmos\n127.0.0.1:3306 · versión no especificada")]

        frontend -. "depende de" .-> inertia
        inertia -. "integra" .-> backend
        backend -. "acceso SQL" .-> database
    end

    user --> frontend
