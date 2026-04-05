-- phpMyAdmin SQL Dump adaptado para Docker
-- Estructura exacta del servidor de producción
-- Base de datos: sistema_devoluciones

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS sistema_devoluciones CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE sistema_devoluciones;

-- Estructura de tabla para la tabla `devoluciones`
CREATE TABLE `devoluciones` (
  `ID` int(11) NOT NULL,
  `distribuidor_codigo` int(11) DEFAULT NULL,
  `distribuidor_numero` int(11) DEFAULT NULL,
  `estado` int(11) DEFAULT NULL,
  `fecha_ingresa` date DEFAULT (curdate()),
  `usuario_ingresa` varchar(50) DEFAULT NULL,
  `fecha_mod` date DEFAULT NULL,
  `usuario_mod` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `devoluciones`
INSERT INTO `devoluciones` (`ID`, `distribuidor_codigo`, `distribuidor_numero`, `estado`, `fecha_ingresa`, `usuario_ingresa`, `fecha_mod`, `usuario_mod`) VALUES
(8, 4063, 1, 1, '2025-09-11', '1', NULL, NULL),
(10, 4073, 1, 1, '2025-09-11', '1', NULL, NULL),
(58, 4063, 2024001, 1, '2025-12-01', NULL, NULL, NULL),
(59, 4063, 2024002, 1, '2025-12-01', NULL, NULL, NULL);

-- Estructura de tabla para la tabla `devoluciones_detalle`
CREATE TABLE `devoluciones_detalle` (
  `cantidad` int(11) DEFAULT NULL,
  `devolucion` int(11) DEFAULT NULL,
  `ID` int(11) NOT NULL,
  `kg` int(11) DEFAULT NULL,
  `motivos_devolucion` int(11) DEFAULT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `producto_cod` int(11) DEFAULT NULL,
  `rechazo` tinyint(4) DEFAULT NULL,
  `vencimiento` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `devoluciones_detalle`
INSERT INTO `devoluciones_detalle` (`cantidad`, `devolucion`, `ID`, `kg`, `motivos_devolucion`, `observaciones`, `producto_cod`, `rechazo`, `vencimiento`) VALUES
(2, 8, 4, 0, 4, '', 56, NULL, '2025-09-16'),
(1, 10, 5, 1, 6, 'as', 59, NULL, '2025-09-17'),
(5, 58, 25, 2, 4, 'Test con producto real de BD', 69, 0, '2024-12-25'),
(5, 59, 26, 2, 4, 'Test con datos reales del sistema', 69, 0, '2024-12-25');

-- Estructura de tabla para la tabla `devoluciones_estados`
CREATE TABLE `devoluciones_estados` (
  `id` int(11) NOT NULL,
  `estado` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `devoluciones_estados`
INSERT INTO `devoluciones_estados` (`id`, `estado`) VALUES
(2, 'Envase dañado'),
(1, 'Enviada'),
(3, 'Malas condiciones de devolucion'),
(4, 'Normal'),
(5, 'Producto Inexistente'),
(6, 'Rechazada'),
(8, 'Rechazada parcialmente'),
(7, 'Vencimiento no visible');

-- Estructura de tabla para la tabla `devoluciones_motivos`
CREATE TABLE `devoluciones_motivos` (
  `id` int(11) NOT NULL,
  `motivos` varchar(100) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `devoluciones_motivos`
INSERT INTO `devoluciones_motivos` (`id`, `motivos`, `estado`) VALUES
(1, 'Abierto voluntariamente', NULL),
(2, 'Envase dañado', NULL),
(3, 'Malas condiciones de devolucion', NULL),
(4, 'Normal', NULL),
(5, 'Producto Inexistente', NULL),
(6, 'Próximo al vencimiento (normal)', NULL),
(7, 'Vencimiento no visible', NULL),
(8, 'Vencido', NULL);

-- Estructura de tabla para la tabla `devoluciones_rechazos`
CREATE TABLE `devoluciones_rechazos` (
  `cantidad` int(11) DEFAULT NULL,
  `devolucion_detalle` int(11) DEFAULT NULL,
  `ID` int(11) NOT NULL,
  `rechazo` tinyint(4) DEFAULT NULL,
  `rechazo_motivo` int(11) DEFAULT NULL,
  `rechazo_observacion` varchar(255) DEFAULT NULL,
  `producto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Estructura de tabla para la tabla `distribuidores`
CREATE TABLE `distribuidores` (
  `codigo` int(11) NOT NULL,
  `razon_social` varchar(100) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT NULL,
  `fecha_alta` date DEFAULT (curdate()),
  `usuario_mod` varchar(50) DEFAULT NULL,
  `fecha_mod` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `distribuidores`
INSERT INTO `distribuidores` (`codigo`, `razon_social`, `estado`, `fecha_alta`, `usuario_mod`, `fecha_mod`) VALUES
(3635, '2B2', 1, '2025-09-04', NULL, NULL),
(4022, 'Santillan Ariela', 1, '2025-09-04', NULL, NULL),
(4030, 'Saravia Juana', 1, '2025-09-04', NULL, NULL),
(4043, 'Juana Saravia', 1, '2025-09-04', NULL, NULL),
(4063, 'Nieva', 1, '2025-09-04', NULL, NULL),
(4064, 'Giron Jose Alberto', 1, '2025-09-04', NULL, NULL),
(4067, 'Eben Ezer Salta SRL', 1, '2025-09-04', NULL, NULL),
(4073, 'Fernandez Hector Manuel', 1, '2025-09-04', NULL, NULL),
(4083, 'Grandi Susana', 1, '2025-09-04', NULL, NULL),
(4084, 'Grandi Susana', 1, '2025-09-04', NULL, NULL),
(4085, 'Arraya Marcelo Samuel', 1, '2025-09-04', NULL, NULL),
(4088, 'Maria Emilia Fernandez', 1, '2025-09-04', NULL, NULL),
(4091, 'Mancorvo', 1, '2025-09-04', NULL, NULL),
(4092, 'Cruz Ignacio Adrian', 1, '2025-09-04', NULL, NULL),
(4094, 'Gallardo Daniela', 1, '2025-09-04', NULL, NULL),
(4095, 'Gaston Larraux', 1, '2025-09-04', NULL, NULL),
(4096, 'Ruiz Mauricio', 1, '2025-09-04', NULL, NULL),
(4097, 'Chanchi Sas', 1, '2025-09-04', NULL, NULL),
(4098, 'Ruiz', 1, '2025-09-04', NULL, NULL),
(4099, 'Cop. De Trab. Dist. del Alba', 1, '2025-09-04', NULL, NULL),
(4101, 'Caminos Distribuidores', 1, '2025-09-04', NULL, NULL),
(4102, 'San Silvestre', 1, '2025-09-04', NULL, NULL),
(5015, 'Juan Jose Corregidor', 1, '2025-09-04', NULL, NULL),
(5018, 'Carrales', 1, '2025-09-04', NULL, NULL),
(5020, 'Dylo Salta', 1, '2025-09-04', NULL, NULL),
(5021, 'Sema Distribuidora', 1, '2025-09-04', NULL, NULL),
(5022, 'Ensamblegrup', 1, '2025-09-04', NULL, NULL),
(7058, 'Manuso Estefania', 1, '2025-09-04', NULL, NULL),
(7062, 'Perreira Hector Adrian', 1, '2025-09-04', NULL, NULL),
(7063, 'Gabriel Perreira', 1, '2025-09-04', NULL, NULL),
(7064, 'Langone Pablo Daniel', 1, '2025-09-04', NULL, NULL),
(7071, 'Guillermo Rullo', 1, '2025-09-04', NULL, NULL),
(7076, 'Maina Alimentos SRL', 1, '2025-09-04', NULL, NULL),
(7080, 'Gallardo Daniela', 1, '2025-09-04', NULL, NULL),
(7082, 'Semesco Cristian', 1, '2025-09-04', NULL, NULL),
(7086, 'La perla sas', 1, '2025-09-04', NULL, NULL),
(7087, 'Gonzalez Joaquin Andres', 1, '2025-09-04', NULL, NULL),
(7090, 'Maria Emilia Fernandez', 1, '2025-09-04', NULL, NULL),
(7094, 'Los garcia', 1, '2025-09-04', NULL, NULL),
(7095, 'Plaza Vieja', 1, '2025-09-04', NULL, NULL),
(7099, 'PDev. Sas', 1, '2025-09-04', NULL, NULL),
(7100, 'Lacteos VP Sas', 1, '2025-09-04', NULL, NULL),
(7101, 'Claus Sas', 1, '2025-09-04', NULL, NULL),
(7102, 'Lacteos del norte Sas', 1, '2025-09-04', NULL, NULL),
(7103, 'Mario M Andino', 1, '2025-09-04', NULL, NULL),
(7568, 'Fenomenal', 1, '2025-09-04', NULL, NULL);

-- Estructura de tabla para la tabla `motivos_rechazos`
CREATE TABLE `motivos_rechazos` (
  `ID` int(11) NOT NULL,
  `motivo` varchar(50) NOT NULL,
  `estado` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `motivos_rechazos`
INSERT INTO `motivos_rechazos` (`ID`, `motivo`, `estado`) VALUES
(1, 'Abierto voluntariamente', 1),
(2, 'Envase dañado', 1),
(3, 'Malas condiciones de devolucion', 1),
(4, 'Normal', 1),
(5, 'Producto Inexistente', 1),
(6, 'Próximo al vencimiento (normal)', 1),
(7, 'Vencimiento no visible', 1),
(8, 'Vencido', 1);

-- Estructura de tabla para la tabla `productos`
CREATE TABLE `productos` (
  `iD` int(11) NOT NULL,
  `codigo` int(11) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT NULL,
  `fecha_creacion` date DEFAULT (curdate()),
  `fecha_mod` date DEFAULT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `sabor` int(11) DEFAULT NULL,
  `tipo` int(11) DEFAULT NULL,
  `usuario_creacion` varchar(100) DEFAULT (current_user()),
  `usuario_mod` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `productos` (productos utilizados en las devoluciones)
INSERT INTO `productos` (`iD`, `codigo`, `estado`, `fecha_creacion`, `fecha_mod`, `nombre`, `sabor`, `tipo`, `usuario_creacion`, `usuario_mod`) VALUES
(56, 1021, 1, '2025-09-04', NULL, 'Yogur Sachet Pulpa Durazno', 1, 2, 'root@localhost', NULL),
(59, 1027, 1, '2025-09-04', NULL, 'Yogur Sachet Bebible Frutilla', 2, 2, 'root@localhost', NULL),
(69, 2006, 1, '2025-09-04', NULL, 'Bandeja Quesera', NULL, 7, 'root@localhost', NULL);

-- Estructura de tabla para la tabla `sabores`
CREATE TABLE `sabores` (
  `estado` tinyint(4) NOT NULL,
  `ID` int(11) NOT NULL,
  `nombre` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `sabores`
INSERT INTO `sabores` (`estado`, `ID`, `nombre`) VALUES
(1, 1, 'Durazno'),
(1, 2, 'Frutilla'),
(1, 3, 'Mango'),
(1, 4, 'Vainilla'),
(1, 5, 'Banana'),
(1, 6, 'Multifruta');

-- Estructura de tabla para la tabla `tipos`
CREATE TABLE `tipos` (
  `ID` int(11) NOT NULL,
  `familia` varchar(100) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `tipos`
INSERT INTO `tipos` (`ID`, `familia`, `estado`) VALUES
(1, 'Leche', 1),
(2, 'Yogurt sachet', 1),
(3, 'Yogurt pote', 1),
(4, 'Crema', 1),
(5, 'Dulce de leche', 1),
(6, 'Queso', 1),
(7, 'Bandeja', 1);

-- Estructura de tabla para la tabla `roles`
CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `estado` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `roles`
INSERT INTO `roles` (`id`, `nombre`, `estado`) VALUES
(1, 'administracion', 1),
(2, 'laboratorio', 1),
(3, 'prueba', 1),
(4, 'distribuidor', 1);

-- Estructura de tabla para la tabla `usuarios`
CREATE TABLE `usuarios` (
  `ID` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `password` varchar(20) NOT NULL,
  `rol_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Volcado de datos para la tabla `usuarios`
INSERT INTO `usuarios` (`ID`, `nombre`, `password`, `rol_id`) VALUES
(1, 'RIVEROA', 'rivero0909', 1),
(2, 'CARRIONB', 'carrion0909', 2),
(3, 'LABORATORIO', 'labo0925', 2),
(4, 'admin', 'admin', 3);

-- Índices para tablas volcadas

-- Indices de la tabla `devoluciones`
ALTER TABLE `devoluciones`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `estado` (`estado`),
  ADD KEY `distribuidor_codigo` (`distribuidor_codigo`);

-- Indices de la tabla `devoluciones_detalle`
ALTER TABLE `devoluciones_detalle`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `devolucion` (`devolucion`),
  ADD KEY `producto_cod` (`producto_cod`),
  ADD KEY `motivos_devolucion` (`motivos_devolucion`);

-- Indices de la tabla `devoluciones_estados`
ALTER TABLE `devoluciones_estados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `estado` (`estado`);

-- Indices de la tabla `devoluciones_motivos`
ALTER TABLE `devoluciones_motivos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `motivos` (`motivos`);

-- Indices de la tabla `devoluciones_rechazos`
ALTER TABLE `devoluciones_rechazos`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `devolucion_detalle` (`devolucion_detalle`),
  ADD KEY `rechazo_motivo` (`rechazo_motivo`);

-- Indices de la tabla `distribuidores`
ALTER TABLE `distribuidores`
  ADD PRIMARY KEY (`codigo`);

-- Indices de la tabla `motivos_rechazos`
ALTER TABLE `motivos_rechazos`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `motivo` (`motivo`);

-- Indices de la tabla `productos`
ALTER TABLE `productos`
  ADD PRIMARY KEY (`iD`),
  ADD UNIQUE KEY `nombre` (`nombre`),
  ADD KEY `FK_sabor` (`sabor`),
  ADD KEY `FK_tipos` (`tipo`);

-- Indices de la tabla `sabores`
ALTER TABLE `sabores`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `nombre` (`nombre`);

-- Indices de la tabla `tipos`
ALTER TABLE `tipos`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `familia` (`familia`);

-- Indices de la tabla `roles`
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

-- Indices de la tabla `usuarios`
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `rol_id` (`rol_id`);

-- AUTO_INCREMENT de las tablas volcadas

-- AUTO_INCREMENT de la tabla `devoluciones`
ALTER TABLE `devoluciones`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

-- AUTO_INCREMENT de la tabla `devoluciones_detalle`
ALTER TABLE `devoluciones_detalle`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

-- AUTO_INCREMENT de la tabla `devoluciones_estados`
ALTER TABLE `devoluciones_estados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

-- AUTO_INCREMENT de la tabla `devoluciones_motivos`
ALTER TABLE `devoluciones_motivos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

-- AUTO_INCREMENT de la tabla `motivos_rechazos`
ALTER TABLE `motivos_rechazos`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

-- AUTO_INCREMENT de la tabla `devoluciones_rechazos`
ALTER TABLE `devoluciones_rechazos`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

-- AUTO_INCREMENT de la tabla `productos`
ALTER TABLE `productos`
  MODIFY `iD` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

-- AUTO_INCREMENT de la tabla `tipos`
ALTER TABLE `tipos`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

-- AUTO_INCREMENT de la tabla `roles`
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

-- AUTO_INCREMENT de la tabla `usuarios`
ALTER TABLE `usuarios`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

-- Restricciones para tablas volcadas

-- Filtros para la tabla `devoluciones`
ALTER TABLE `devoluciones`
  ADD CONSTRAINT `devoluciones_ibfk_1` FOREIGN KEY (`estado`) REFERENCES `devoluciones_estados` (`id`),
  ADD CONSTRAINT `devoluciones_ibfk_2` FOREIGN KEY (`distribuidor_codigo`) REFERENCES `distribuidores` (`codigo`);

-- Filtros para la tabla `devoluciones_detalle`
ALTER TABLE `devoluciones_detalle`
  ADD CONSTRAINT `devoluciones_detalle_ibfk_1` FOREIGN KEY (`devolucion`) REFERENCES `devoluciones` (`ID`),
  ADD CONSTRAINT `devoluciones_detalle_ibfk_2` FOREIGN KEY (`producto_cod`) REFERENCES `productos` (`iD`),
  ADD CONSTRAINT `devoluciones_detalle_ibfk_3` FOREIGN KEY (`motivos_devolucion`) REFERENCES `devoluciones_motivos` (`id`);

-- Filtros para la tabla `devoluciones_rechazos`
ALTER TABLE `devoluciones_rechazos`
  ADD CONSTRAINT `devoluciones_rechazos_ibfk_1` FOREIGN KEY (`devolucion_detalle`) REFERENCES `devoluciones_detalle` (`ID`),
  ADD CONSTRAINT `devoluciones_rechazos_ibfk_2` FOREIGN KEY (`rechazo_motivo`) REFERENCES `motivos_rechazos` (`ID`);

-- Filtros para la tabla `productos`
ALTER TABLE `productos`
  ADD CONSTRAINT `FK_sabor` FOREIGN KEY (`sabor`) REFERENCES `sabores` (`ID`),
  ADD CONSTRAINT `FK_tipos` FOREIGN KEY (`tipo`) REFERENCES `tipos` (`ID`);

-- Filtros para la tabla `usuarios`
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`);

COMMIT;