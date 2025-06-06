-- Primero, crear las nuevas tablas que no existen

-- Tabla periodos_academicos
CREATE TABLE IF NOT EXISTS periodos_academicos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    estado ENUM('activo', 'inactivo', 'finalizado') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla asignaturas
CREATE TABLE IF NOT EXISTS asignaturas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Modificar la tabla sedes para agregar tipo_ensenanza si no existe
ALTER TABLE sedes 
ADD COLUMN IF NOT EXISTS tipo_ensenanza ENUM('multigrado','unigrado') DEFAULT 'unigrado' AFTER estado;

-- Tabla para niveles educativos
CREATE TABLE IF NOT EXISTS niveles_educativos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sede_id INT NOT NULL,
    nombre ENUM('preescolar', 'primaria', 'secundaria') NOT NULL,
    estado ENUM('activo','inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sede_id) REFERENCES sedes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla para grados
CREATE TABLE IF NOT EXISTS grados (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nivel_id INT NOT NULL,
    nombre VARCHAR(50) NOT NULL,
    codigo VARCHAR(10) NOT NULL,
    orden INT NOT NULL,
    estado ENUM('activo','inactivo') DEFAULT 'activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (nivel_id) REFERENCES niveles_educativos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla grupos (modificada para incluir nuevos campos)
CREATE TABLE IF NOT EXISTS grupos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sede_id INT NOT NULL,
    nivel_id INT NOT NULL,
    tipo ENUM('unigrado','multigrado') NOT NULL,
    grado_id INT,
    grados_incluidos TEXT,
    nombre VARCHAR(50) NOT NULL,
    capacidad INT NOT NULL DEFAULT 30,
    año_escolar INT NOT NULL,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sede_id) REFERENCES sedes(id) ON DELETE CASCADE,
    FOREIGN KEY (nivel_id) REFERENCES niveles_educativos(id),
    FOREIGN KEY (grado_id) REFERENCES grados(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla asignaciones_profesor
CREATE TABLE IF NOT EXISTS asignaciones_profesor (
    id INT PRIMARY KEY AUTO_INCREMENT,
    profesor_id INT NOT NULL,
    grupo_id INT NOT NULL,
    asignatura_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (profesor_id) REFERENCES profesores(id),
    FOREIGN KEY (grupo_id) REFERENCES grupos(id),
    FOREIGN KEY (asignatura_id) REFERENCES asignaturas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla calificaciones
CREATE TABLE IF NOT EXISTS calificaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    matricula_id INT NOT NULL,
    asignatura_id INT NOT NULL,
    periodo_id INT NOT NULL,
    nota DECIMAL(3,1) NOT NULL,
    tipo_evaluacion VARCHAR(50) NOT NULL,
    fecha_evaluacion DATE NOT NULL,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (matricula_id) REFERENCES matriculas(id) ON DELETE CASCADE,
    FOREIGN KEY (asignatura_id) REFERENCES asignaturas(id) ON DELETE CASCADE,
    FOREIGN KEY (periodo_id) REFERENCES periodos_academicos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Crear tabla de asistencias si no existe
CREATE TABLE IF NOT EXISTS `asistencias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `matricula_id` int(11) NOT NULL,
  `asignatura_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `estado` enum('presente','ausente','tardanza','justificado') NOT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_asistencia` (`matricula_id`,`asignatura_id`,`fecha`),
  KEY `fk_asistencia_matricula` (`matricula_id`),
  KEY `fk_asistencia_asignatura` (`asignatura_id`),
  CONSTRAINT `fk_asistencia_matricula` FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_asistencia_asignatura` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabla de asistencia
CREATE TABLE IF NOT EXISTS asistencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estudiante_id INT NOT NULL,
    grado_id INT NOT NULL,
    fecha DATE NOT NULL,
    estado ENUM('presente', 'ausente', 'tardanza', 'justificado') NOT NULL DEFAULT 'presente',
    observacion TEXT,
    profesor_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    FOREIGN KEY (grado_id) REFERENCES grados(id) ON DELETE CASCADE,
    FOREIGN KEY (profesor_id) REFERENCES profesores(id) ON DELETE CASCADE,
    UNIQUE KEY (estudiante_id, fecha) -- Evita registros duplicados para un mismo estudiante en una fecha
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Crear índices para optimizar búsquedas
CREATE INDEX IF NOT EXISTS idx_estudiantes_documento ON estudiantes(documento_numero);
CREATE INDEX IF NOT EXISTS idx_profesores_documento ON profesores(documento_numero);
CREATE INDEX IF NOT EXISTS idx_calificaciones_fecha ON calificaciones(fecha_evaluacion);
CREATE INDEX IF NOT EXISTS idx_asistencias_fecha ON asistencias(fecha);
CREATE INDEX IF NOT EXISTS idx_matriculas_fecha ON matriculas(fecha_matricula);
CREATE INDEX IF NOT EXISTS idx_niveles_sede ON niveles_educativos(sede_id);
CREATE INDEX IF NOT EXISTS idx_grados_nivel ON grados(nivel_id);
CREATE INDEX IF NOT EXISTS idx_grupos_sede_nivel ON grupos(sede_id, nivel_id);

-- Crear tabla grupos si no existe
CREATE TABLE IF NOT EXISTS grupos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    grado_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    capacidad INT DEFAULT 40,
    estado VARCHAR(20) DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (grado_id) REFERENCES grados(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Crear índices
ALTER TABLE grupos ADD INDEX idx_grado_id (grado_id);