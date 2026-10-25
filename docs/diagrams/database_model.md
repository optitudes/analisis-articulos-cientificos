erDiagram
    %% Motor: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
    %% Siguiente valor AUTO_INCREMENT registrado: 21
    %% No se declaran claves foráneas ni índices adicionales.
    articles {
        bigint_unsigned id PK "Identificador único del artículo. BIGINT UNSIGNED NOT NULL AUTO_INCREMENT."
        varchar_255 name "Nombre del artículo. VARCHAR(255) NOT NULL."
        longtext abstract "Resumen o abstract del artículo. LONGTEXT NULL; puede no tener valor."
        text relative_path "Ruta relativa al archivo del artículo. TEXT NOT NULL."
        timestamp created_at "Fecha y hora de creación del registro. TIMESTAMP NULL DEFAULT NULL."
        timestamp updated_at "Fecha y hora de la última actualización. TIMESTAMP NULL DEFAULT NULL."
    }
