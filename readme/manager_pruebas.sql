-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 17-11-2025 a las 09:20:40
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `manager_pruebas`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `albaranes_venta`
--

CREATE TABLE `albaranes_venta` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `salida_id` bigint(20) UNSIGNED NOT NULL,
  `cliente_id` bigint(20) UNSIGNED NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `fecha` date NOT NULL DEFAULT curdate(),
  `estado` enum('pendiente','servido','cancelado') DEFAULT 'pendiente',
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `albaranes_venta_lineas`
--

CREATE TABLE `albaranes_venta_lineas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `albaran_id` bigint(20) UNSIGNED NOT NULL,
  `pedido_linea_id` bigint(20) UNSIGNED NOT NULL,
  `producto_base_id` bigint(20) UNSIGNED NOT NULL,
  `cantidad_kg` decimal(12,2) NOT NULL DEFAULT 0.00,
  `precio_unitario` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `albaranes_venta_productos`
--

CREATE TABLE `albaranes_venta_productos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `salida_almacen_id` bigint(20) UNSIGNED DEFAULT NULL,
  `albaran_linea_id` bigint(20) UNSIGNED NOT NULL,
  `producto_id` bigint(20) UNSIGNED NOT NULL,
  `peso_kg` decimal(10,2) DEFAULT NULL,
  `cantidad` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alertas`
--

CREATE TABLE `alertas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id_1` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id_2` bigint(20) UNSIGNED DEFAULT NULL,
  `destino` varchar(100) DEFAULT NULL,
  `destinatario` varchar(100) DEFAULT NULL,
  `destinatario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `mensaje` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `alertas_users`
--

CREATE TABLE `alertas_users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `alerta_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `leida_en` timestamp NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignaciones_turnos`
--

CREATE TABLE `asignaciones_turnos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `turno_id` bigint(20) UNSIGNED DEFAULT NULL,
  `estado` varchar(50) DEFAULT 'activo',
  `maquina_id` bigint(20) UNSIGNED DEFAULT NULL,
  `obra_id` bigint(20) UNSIGNED DEFAULT NULL,
  `entrada` time DEFAULT NULL,
  `salida` time DEFAULT NULL,
  `fecha` date NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `camiones`
--

CREATE TABLE `camiones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa_id` bigint(20) UNSIGNED NOT NULL,
  `capacidad` decimal(10,2) NOT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` bigint(20) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `chat_consultas_sql`
--

CREATE TABLE `chat_consultas_sql` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `mensaje_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `consulta_sql` text NOT NULL,
  `consulta_natural` text NOT NULL COMMENT 'La pregunta del usuario',
  `resultados` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`resultados`)),
  `filas_afectadas` int(11) NOT NULL DEFAULT 0,
  `exitosa` tinyint(1) NOT NULL DEFAULT 1,
  `error` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `chat_conversaciones`
--

CREATE TABLE `chat_conversaciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `ultima_actividad` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `chat_mensajes`
--

CREATE TABLE `chat_mensajes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversacion_id` bigint(20) UNSIGNED NOT NULL,
  `role` enum('user','assistant','system') NOT NULL DEFAULT 'user',
  `contenido` text NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Para guardar consultas SQL, resultados, etc.' CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `empresa` varchar(255) NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `contacto1_nombre` varchar(255) DEFAULT NULL,
  `contacto1_telefono` varchar(20) DEFAULT NULL,
  `contacto1_email` varchar(255) DEFAULT NULL,
  `contacto2_nombre` varchar(255) DEFAULT NULL,
  `contacto2_telefono` varchar(20) DEFAULT NULL,
  `contacto2_email` varchar(255) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `provincia` varchar(100) DEFAULT NULL,
  `pais` varchar(100) DEFAULT NULL,
  `cif_nif` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes_almacen`
--

CREATE TABLE `clientes_almacen` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `cif` varchar(20) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `localidad` varchar(100) DEFAULT NULL,
  `provincia` varchar(100) DEFAULT NULL,
  `pais` varchar(100) DEFAULT 'España',
  `notas` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `convenio`
--

CREATE TABLE `convenio` (
  `id` bigint(20) NOT NULL,
  `categoria_id` bigint(20) DEFAULT NULL,
  `salario_base` decimal(10,2) NOT NULL,
  `liquido_minimo_pactado` decimal(8,2) DEFAULT NULL,
  `plus_asistencia` decimal(10,2) NOT NULL,
  `plus_turnicidad` int(11) DEFAULT NULL,
  `plus_productividad` decimal(10,2) NOT NULL,
  `plus_transporte` decimal(10,2) NOT NULL,
  `prorrateo_pagasextras` decimal(10,2) NOT NULL,
  `plus_dieta` int(11) NOT NULL DEFAULT 1200,
  `plus_actividad` int(11) NOT NULL DEFAULT 1200
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `departamentos`
--

CREATE TABLE `departamentos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `departamento_seccion`
--

CREATE TABLE `departamento_seccion` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `departamento_id` bigint(20) UNSIGNED NOT NULL,
  `seccion_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `departamento_user`
--

CREATE TABLE `departamento_user` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `departamento_id` bigint(20) UNSIGNED NOT NULL,
  `rol_departamental` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `distribuidores`
--

CREATE TABLE `distribuidores` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `nif` varchar(20) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `elementos`
--

CREATE TABLE `elementos` (
  `id` bigint(20) NOT NULL,
  `codigo` varchar(30) DEFAULT NULL,
  `users_id` bigint(20) UNSIGNED DEFAULT NULL,
  `users_id_2` bigint(20) UNSIGNED DEFAULT NULL,
  `planilla_id` bigint(20) NOT NULL,
  `elaborado` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0=sin elaboración (barra estándar), 1=requiere elaboración',
  `orden_planilla_id` bigint(20) UNSIGNED DEFAULT NULL,
  `etiqueta_id` bigint(20) DEFAULT NULL,
  `etiqueta_sub_id` varchar(20) DEFAULT NULL,
  `maquina_id` bigint(20) UNSIGNED DEFAULT NULL,
  `maquina_id_2` bigint(20) UNSIGNED DEFAULT NULL,
  `maquina_id_3` bigint(20) UNSIGNED DEFAULT NULL,
  `producto_id` bigint(20) UNSIGNED DEFAULT NULL,
  `producto_id_2` bigint(20) UNSIGNED DEFAULT NULL,
  `producto_id_3` bigint(20) UNSIGNED DEFAULT NULL,
  `paquete_id` bigint(20) UNSIGNED DEFAULT NULL,
  `figura` varchar(50) DEFAULT NULL,
  `fila` int(4) DEFAULT NULL,
  `marca` varchar(255) DEFAULT NULL,
  `etiqueta` varchar(50) DEFAULT NULL,
  `diametro` decimal(10,2) NOT NULL,
  `longitud` decimal(10,2) NOT NULL,
  `barras` int(5) DEFAULT NULL,
  `dobles_barra` int(11) NOT NULL,
  `peso` decimal(10,2) NOT NULL,
  `dimensiones` text DEFAULT NULL,
  `tiempo_fabricacion` decimal(10,2) DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'pendiente',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresas`
--

CREATE TABLE `empresas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `localidad` varchar(100) DEFAULT NULL,
  `provincia` varchar(100) DEFAULT NULL,
  `codigo_postal` varchar(10) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `nif` varchar(20) DEFAULT NULL,
  `numero_ss` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresas_transporte`
--

CREATE TABLE `empresas_transporte` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entradas`
--

CREATE TABLE `entradas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `albaran` varchar(255) NOT NULL,
  `codigo_sage` varchar(50) DEFAULT NULL,
  `nave_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pedido_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pedido_producto_id` bigint(20) UNSIGNED DEFAULT NULL,
  `usuario_id` bigint(20) UNSIGNED DEFAULT NULL,
  `peso_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` varchar(20) NOT NULL DEFAULT 'abierto',
  `pdf_albaran` varchar(255) DEFAULT NULL,
  `otros` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entrada_producto`
--

CREATE TABLE `entrada_producto` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `entrada_id` bigint(20) UNSIGNED NOT NULL,
  `producto_id` bigint(20) UNSIGNED NOT NULL,
  `ubicacion_id` bigint(20) UNSIGNED DEFAULT NULL,
  `users_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `etiquetas`
--

CREATE TABLE `etiquetas` (
  `id` bigint(20) NOT NULL,
  `codigo` varchar(255) DEFAULT NULL,
  `etiqueta_sub_id` varchar(64) DEFAULT NULL,
  `planilla_id` bigint(20) NOT NULL,
  `paquete_id` bigint(20) UNSIGNED DEFAULT NULL,
  `producto_id` bigint(20) UNSIGNED DEFAULT NULL,
  `producto_id_2` bigint(20) UNSIGNED DEFAULT NULL,
  `ubicacion_id` bigint(20) UNSIGNED DEFAULT NULL,
  `operario1_id` bigint(20) UNSIGNED DEFAULT NULL,
  `operario2_id` bigint(20) UNSIGNED DEFAULT NULL,
  `soldador1_id` bigint(20) UNSIGNED DEFAULT NULL,
  `soldador2_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ensamblador1_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ensamblador2_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nombre` varchar(255) NOT NULL,
  `marca` varchar(255) DEFAULT NULL,
  `numero_etiqueta` int(11) DEFAULT NULL,
  `peso` decimal(10,2) NOT NULL DEFAULT 0.00,
  `fecha_inicio` datetime DEFAULT NULL,
  `fecha_finalizacion` datetime DEFAULT NULL,
  `fecha_inicio_ensamblado` datetime DEFAULT NULL,
  `fecha_finalizacion_ensamblado` datetime DEFAULT NULL,
  `fecha_inicio_soldadura` datetime DEFAULT NULL,
  `fecha_finalizacion_soldadura` datetime DEFAULT NULL,
  `estado` varchar(255) DEFAULT 'pendiente',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `subido` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fabricantes`
--

CREATE TABLE `fabricantes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `festivos`
--

CREATE TABLE `festivos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `fecha` date NOT NULL,
  `editable` tinyint(1) NOT NULL DEFAULT 1,
  `anio` smallint(5) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `irpf_tramos`
--

CREATE TABLE `irpf_tramos` (
  `id` bigint(20) NOT NULL,
  `tramo_inicial` decimal(10,2) NOT NULL,
  `tramo_final` decimal(10,2) NOT NULL,
  `porcentaje` decimal(5,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `localizaciones`
--

CREATE TABLE `localizaciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `x1` int(11) NOT NULL,
  `y1` int(11) NOT NULL,
  `x2` int(11) NOT NULL,
  `y2` int(11) NOT NULL,
  `tipo` varchar(20) NOT NULL,
  `maquina_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `nave_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `localizaciones_paquetes`
--

CREATE TABLE `localizaciones_paquetes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `paquete_id` bigint(20) UNSIGNED NOT NULL,
  `x1` int(11) NOT NULL,
  `y1` int(11) NOT NULL,
  `x2` int(11) NOT NULL,
  `y2` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `maquinas`
--

CREATE TABLE `maquinas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `codigo` varchar(255) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `estado` varchar(255) DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `tiene_carro` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = la máquina tiene carro',
  `obra_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tipo_material` varchar(20) DEFAULT NULL,
  `diametro_min` int(11) DEFAULT NULL,
  `diametro_max` int(11) DEFAULT NULL,
  `peso_min` int(11) DEFAULT NULL,
  `peso_max` int(11) DEFAULT NULL,
  `ancho_m` decimal(6,2) DEFAULT NULL,
  `largo_m` decimal(6,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modelo_145`
--

CREATE TABLE `modelo_145` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `edad` int(11) DEFAULT NULL,
  `estado_civil` varchar(20) DEFAULT NULL,
  `hijos_a_cargo` int(11) DEFAULT 0,
  `hijos_menores_3` int(11) DEFAULT 0,
  `ascendientes_mayores_65` tinyint(1) DEFAULT 0,
  `ascendientes_mayores_75` tinyint(1) DEFAULT 0,
  `discapacidad_porcentaje` int(11) DEFAULT 0,
  `discapacidad_familiares` tinyint(1) DEFAULT 0,
  `contrato_indefinido` tinyint(1) DEFAULT 1,
  `fecha_declaracion` date DEFAULT NULL,
  `es_simulacion` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos`
--

CREATE TABLE `movimientos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tipo` varchar(70) DEFAULT NULL,
  `producto_id` bigint(20) UNSIGNED DEFAULT NULL,
  `producto_base_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ubicacion_origen` bigint(20) UNSIGNED DEFAULT NULL,
  `ubicacion_destino` bigint(20) UNSIGNED DEFAULT NULL,
  `maquina_origen` bigint(20) UNSIGNED DEFAULT NULL,
  `maquina_destino` bigint(20) UNSIGNED DEFAULT NULL,
  `paquete_id` bigint(20) UNSIGNED DEFAULT NULL,
  `estado` varchar(50) DEFAULT 'pendiente',
  `prioridad` tinyint(4) DEFAULT 1,
  `descripcion` text DEFAULT NULL,
  `fecha_solicitud` datetime DEFAULT NULL,
  `fecha_ejecucion` datetime DEFAULT NULL,
  `solicitado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `ejecutado_por` bigint(20) UNSIGNED DEFAULT NULL,
  `nave_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `pedido_id` bigint(20) UNSIGNED DEFAULT NULL,
  `pedido_producto_id` bigint(20) UNSIGNED DEFAULT NULL,
  `salida_id` bigint(20) DEFAULT NULL,
  `salida_almacen_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nominas`
--

CREATE TABLE `nominas` (
  `id` int(11) NOT NULL,
  `empleado_id` bigint(20) UNSIGNED DEFAULT NULL,
  `categoria_id` bigint(20) DEFAULT NULL,
  `dias_trabajados` decimal(5,2) DEFAULT NULL,
  `salario_base` decimal(10,2) DEFAULT NULL,
  `plus_ajustado` tinyint(1) DEFAULT 0,
  `plus_actividad` decimal(10,2) DEFAULT NULL,
  `plus_asistencia` decimal(8,2) DEFAULT NULL,
  `plus_transporte` decimal(8,2) DEFAULT NULL,
  `plus_dieta` decimal(8,2) DEFAULT NULL,
  `plus_turnicidad` decimal(10,2) DEFAULT NULL,
  `plus_productividad` decimal(8,2) DEFAULT NULL,
  `prorrateo` decimal(10,2) DEFAULT NULL,
  `horas_extra` decimal(10,2) DEFAULT NULL,
  `valor_hora_extra` decimal(10,2) DEFAULT NULL,
  `total_devengado` decimal(10,2) DEFAULT NULL,
  `total_deducciones_ss` decimal(10,2) DEFAULT NULL,
  `irpf_mensual` decimal(10,2) DEFAULT NULL,
  `irpf_porcentaje` decimal(5,2) DEFAULT NULL,
  `liquido` decimal(10,2) DEFAULT NULL,
  `coste_empresa` decimal(10,2) DEFAULT NULL,
  `bruto_anual_estimado` decimal(12,2) DEFAULT NULL,
  `base_irpf_previa` decimal(12,2) DEFAULT NULL,
  `cuota_irpf_anual_sin_minimo` decimal(12,2) DEFAULT NULL,
  `cuota_minimo_personal` decimal(12,2) DEFAULT NULL,
  `cuota_irpf_anual` decimal(12,2) DEFAULT NULL,
  `fecha` date DEFAULT curdate(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `obras`
--

CREATE TABLE `obras` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `obra` varchar(255) NOT NULL,
  `cod_obra` varchar(50) NOT NULL,
  `cliente_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ciudad` varchar(255) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `estado` varchar(255) DEFAULT NULL,
  `tipo` enum('montaje','suministro') DEFAULT 'suministro',
  `latitud` decimal(17,15) DEFAULT NULL,
  `longitud` decimal(17,15) DEFAULT NULL,
  `distancia` int(10) UNSIGNED DEFAULT NULL COMMENT 'Distancia en metros',
  `ancho_m` int(11) DEFAULT NULL,
  `largo_m` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orden_planillas`
--

CREATE TABLE `orden_planillas` (
  `id` int(10) UNSIGNED NOT NULL,
  `planilla_id` bigint(20) NOT NULL,
  `maquina_id` bigint(20) UNSIGNED NOT NULL,
  `posicion` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paquetes`
--

CREATE TABLE `paquetes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `nave_id` bigint(20) UNSIGNED DEFAULT NULL,
  `planilla_id` bigint(20) DEFAULT NULL,
  `ubicacion_id` bigint(20) UNSIGNED DEFAULT NULL,
  `peso` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `subido` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `pedido_global_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fabricante_id` bigint(20) UNSIGNED DEFAULT NULL,
  `distribuidor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `peso_total` decimal(10,2) DEFAULT NULL,
  `fecha_pedido` date DEFAULT NULL,
  `fecha_entrega` date DEFAULT NULL,
  `estado` varchar(50) NOT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos_almacen_venta`
--

CREATE TABLE `pedidos_almacen_venta` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cliente_id` bigint(20) UNSIGNED NOT NULL,
  `codigo` varchar(30) NOT NULL,
  `estado` enum('borrador','pendiente','parcial','completado','facturado') DEFAULT 'borrador',
  `fecha` date DEFAULT curdate(),
  `observaciones` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos_almacen_venta_lineas`
--

CREATE TABLE `pedidos_almacen_venta_lineas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pedido_almacen_venta_id` bigint(20) UNSIGNED NOT NULL,
  `producto_base_id` bigint(20) UNSIGNED NOT NULL,
  `unidad_medida` varchar(10) DEFAULT 'kg',
  `cantidad_solicitada` decimal(10,2) DEFAULT 0.00,
  `cantidad_servida` decimal(10,2) DEFAULT 0.00,
  `cantidad_pendiente` decimal(10,2) DEFAULT 0.00,
  `kg_por_bulto_override` decimal(8,2) DEFAULT NULL,
  `precio_unitario` decimal(10,2) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos_globales`
--

CREATE TABLE `pedidos_globales` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `cantidad_total` decimal(12,2) NOT NULL,
  `precio_referencia` decimal(12,4) DEFAULT NULL,
  `fabricante_id` bigint(20) UNSIGNED DEFAULT NULL,
  `distribuidor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `estado` varchar(50) NOT NULL DEFAULT 'pendiente',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedido_productos`
--

CREATE TABLE `pedido_productos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pedido_id` bigint(20) UNSIGNED NOT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `pedido_global_id` bigint(20) UNSIGNED DEFAULT NULL,
  `obra_id` bigint(20) UNSIGNED DEFAULT NULL,
  `obra_manual` varchar(255) DEFAULT NULL,
  `producto_base_id` bigint(20) UNSIGNED NOT NULL,
  `cantidad_recepcionada` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cantidad` decimal(10,2) NOT NULL,
  `fecha_estimada_entrega` date DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `estado` varchar(20) DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos_acceso`
--

CREATE TABLE `permisos_acceso` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `departamento_id` bigint(20) UNSIGNED NOT NULL,
  `seccion_id` int(10) UNSIGNED NOT NULL,
  `puede_ver` tinyint(1) DEFAULT 0,
  `puede_editar` tinyint(1) DEFAULT 0,
  `puede_crear` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `planillas`
--

CREATE TABLE `planillas` (
  `id` bigint(20) NOT NULL,
  `codigo` varchar(255) DEFAULT NULL,
  `users_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cliente_id` bigint(20) UNSIGNED DEFAULT NULL,
  `obra_id` bigint(20) UNSIGNED DEFAULT NULL,
  `seccion` varchar(255) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `ensamblado` varchar(255) DEFAULT NULL,
  `comentario` varchar(255) DEFAULT NULL,
  `revisada` tinyint(1) NOT NULL DEFAULT 0,
  `revisada_por_id` bigint(20) UNSIGNED DEFAULT NULL,
  `revisada_at` datetime DEFAULT NULL,
  `peso_total` decimal(10,2) NOT NULL,
  `estado` varchar(50) DEFAULT 'pendiente',
  `fecha_inicio` datetime DEFAULT NULL,
  `fecha_finalizacion` datetime DEFAULT NULL,
  `tiempo_fabricacion` decimal(10,2) DEFAULT NULL,
  `fecha_estimada_entrega` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `producto_base_id` bigint(20) UNSIGNED DEFAULT NULL,
  `fabricante_id` bigint(20) UNSIGNED DEFAULT NULL,
  `distribuidor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `obra_id` bigint(20) UNSIGNED DEFAULT NULL,
  `n_colada` varchar(255) DEFAULT NULL,
  `n_paquete` varchar(255) DEFAULT NULL,
  `peso_inicial` decimal(10,2) NOT NULL,
  `peso_stock` decimal(10,2) NOT NULL,
  `estado` varchar(50) DEFAULT NULL,
  `fecha_consumido` datetime DEFAULT NULL,
  `consumido_by` bigint(20) UNSIGNED DEFAULT NULL,
  `otros` text DEFAULT NULL,
  `ubicacion_id` bigint(20) UNSIGNED DEFAULT NULL,
  `maquina_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `entrada_id` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos_base`
--

CREATE TABLE `productos_base` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tipo` enum('barra','encarretado') NOT NULL,
  `diametro` int(11) NOT NULL,
  `longitud` int(11) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos_codigos`
--

CREATE TABLE `productos_codigos` (
  `id` int(10) UNSIGNED NOT NULL,
  `tipo` varchar(10) NOT NULL,
  `anio` char(2) NOT NULL,
  `mes` varchar(2) DEFAULT NULL,
  `ultimo_numero` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `salidas`
--

CREATE TABLE `salidas` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `codigo_salida` varchar(10) DEFAULT NULL,
  `codigo_sage` varchar(50) DEFAULT NULL,
  `empresa_id` bigint(20) UNSIGNED DEFAULT NULL,
  `camion_id` bigint(20) UNSIGNED DEFAULT NULL,
  `importe` decimal(10,2) DEFAULT NULL,
  `importe_paralizacion` decimal(10,2) DEFAULT NULL,
  `horas_grua` decimal(10,2) DEFAULT NULL,
  `importe_grua` decimal(10,2) DEFAULT NULL,
  `horas_paralizacion` decimal(10,2) DEFAULT NULL,
  `horas_almacen` decimal(5,2) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `fecha_salida` timestamp NULL DEFAULT current_timestamp(),
  `estado` enum('pendiente','en tránsito','completada') DEFAULT 'pendiente',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `salidas_almacen`
--

CREATE TABLE `salidas_almacen` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `fecha` date NOT NULL DEFAULT curdate(),
  `estado` enum('pendiente','en_ruta','completada','activa','cancelada') DEFAULT 'pendiente',
  `camionero_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `salidas_paquetes`
--

CREATE TABLE `salidas_paquetes` (
  `id` bigint(20) NOT NULL,
  `salida_id` bigint(20) NOT NULL,
  `paquete_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `salida_cliente`
--

CREATE TABLE `salida_cliente` (
  `id` bigint(20) NOT NULL,
  `salida_id` bigint(20) NOT NULL,
  `cliente_id` bigint(20) UNSIGNED NOT NULL,
  `obra_id` bigint(20) UNSIGNED NOT NULL,
  `horas_paralizacion` decimal(10,2) DEFAULT 0.00,
  `importe_paralizacion` decimal(10,2) DEFAULT 0.00,
  `horas_grua` decimal(10,2) DEFAULT 0.00,
  `importe_grua` decimal(10,2) DEFAULT 0.00,
  `horas_almacen` decimal(5,2) DEFAULT 0.00,
  `importe` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `secciones`
--

CREATE TABLE `secciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `ruta` varchar(150) NOT NULL,
  `icono` varchar(255) DEFAULT NULL,
  `mostrar_en_dashboard` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_vacaciones`
--

CREATE TABLE `solicitudes_vacaciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `estado` enum('pendiente','aprobada','denegada') DEFAULT 'pendiente',
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ss_config`
--

CREATE TABLE `ss_config` (
  `id` bigint(20) NOT NULL,
  `concepto` varchar(100) NOT NULL,
  `porcentaje` decimal(5,2) NOT NULL,
  `aplica` enum('trabajador','empresa') NOT NULL DEFAULT 'trabajador'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `subpaquetes`
--

CREATE TABLE `subpaquetes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `elemento_id` bigint(20) NOT NULL,
  `planilla_id` bigint(20) NOT NULL,
  `paquete_id` bigint(20) UNSIGNED DEFAULT NULL,
  `peso` decimal(10,2) DEFAULT NULL,
  `dimensiones` varchar(255) DEFAULT NULL,
  `cantidad` int(11) DEFAULT 1,
  `descripcion` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tasas_irpf`
--

CREATE TABLE `tasas_irpf` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `anio` int(11) NOT NULL,
  `base_desde` decimal(15,2) NOT NULL COMMENT 'Límite inferior de la base imponible',
  `base_hasta` decimal(15,2) DEFAULT NULL COMMENT 'Límite superior de la base imponible (NULL = sin límite)',
  `porcentaje` decimal(5,4) NOT NULL COMMENT 'Tipo de retención en porcentaje',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tasas_seguridad_social`
--

CREATE TABLE `tasas_seguridad_social` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `destinatario` enum('trabajador','empresa') NOT NULL,
  `tipo_aportacion` varchar(255) NOT NULL COMMENT 'Contingencias comunes, desempleo, formación, etc.',
  `porcentaje` decimal(5,4) NOT NULL COMMENT 'Tipo de cotización en porcentaje',
  `fecha_inicio` date NOT NULL COMMENT 'Fecha de inicio de vigencia',
  `fecha_fin` date DEFAULT NULL COMMENT 'Fecha de fin de vigencia (NULL = vigente)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turnos`
--

CREATE TABLE `turnos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `hora_entrada` time DEFAULT NULL,
  `entrada_offset` tinyint(4) NOT NULL DEFAULT 0,
  `hora_salida` time DEFAULT NULL,
  `salida_offset` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ubicaciones`
--

CREATE TABLE `ubicaciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `almacen` varchar(2) DEFAULT NULL,
  `sector` varchar(2) DEFAULT NULL,
  `ubicacion` varchar(60) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `primer_apellido` varchar(100) DEFAULT NULL,
  `segundo_apellido` varchar(100) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `puede_usar_asistente` tinyint(1) NOT NULL DEFAULT 1,
  `puede_modificar_bd` tinyint(1) NOT NULL DEFAULT 0,
  `imagen` varchar(255) DEFAULT NULL,
  `movil_personal` varchar(20) DEFAULT NULL,
  `movil_empresa` varchar(20) DEFAULT NULL,
  `numero_corto` char(4) DEFAULT NULL CHECK (`numero_corto` regexp '^[0-9]{4}$'),
  `dni` varchar(9) DEFAULT NULL,
  `empresa_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rol` varchar(50) DEFAULT NULL,
  `maquina_id` bigint(20) UNSIGNED DEFAULT NULL,
  `turno` varchar(50) DEFAULT NULL,
  `vacaciones_totales` tinyint(3) UNSIGNED NOT NULL DEFAULT 22,
  `password` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `acepta_politica_privacidad` tinyint(1) DEFAULT 0,
  `acepta_politica_cookies` tinyint(1) DEFAULT 0,
  `fecha_aceptacion_politicas` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `categoria_id` bigint(20) DEFAULT NULL,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `estado` enum('activo','despedido') NOT NULL DEFAULT 'activo' COMMENT 'Estado laboral del trabajador',
  `fecha_baja` datetime DEFAULT NULL COMMENT 'Fecha en la que el usuario fue despedido o dado de baja'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `albaranes_venta`
--
ALTER TABLE `albaranes_venta`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `albaranes_venta_salida_id_foreign` (`salida_id`),
  ADD KEY `albaranes_venta_cliente_id_foreign` (`cliente_id`),
  ADD KEY `albaranes_venta_created_by_foreign` (`created_by`),
  ADD KEY `albaranes_venta_updated_by_foreign` (`updated_by`);

--
-- Indices de la tabla `albaranes_venta_lineas`
--
ALTER TABLE `albaranes_venta_lineas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `albaranes_venta_lineas_albaran_id_foreign` (`albaran_id`),
  ADD KEY `albaranes_venta_lineas_pedido_linea_id_foreign` (`pedido_linea_id`),
  ADD KEY `albaranes_venta_lineas_producto_base_id_foreign` (`producto_base_id`);

--
-- Indices de la tabla `albaranes_venta_productos`
--
ALTER TABLE `albaranes_venta_productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_salida_producto` (`salida_almacen_id`,`producto_id`),
  ADD KEY `fk_avp_linea` (`albaran_linea_id`),
  ADD KEY `fk_avp_producto` (`producto_id`);

--
-- Indices de la tabla `alertas`
--
ALTER TABLE `alertas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id_1` (`user_id_1`),
  ADD KEY `user_id_2` (`user_id_2`),
  ADD KEY `fk_destinatario_id` (`destinatario_id`),
  ADD KEY `alertas_parent_id_index` (`parent_id`);

--
-- Indices de la tabla `alertas_users`
--
ALTER TABLE `alertas_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_alerta_user` (`alerta_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `asignaciones_turnos`
--
ALTER TABLE `asignaciones_turnos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `turno_id` (`turno_id`),
  ADD KEY `fk_asignaciones_turnos_maquina` (`maquina_id`),
  ADD KEY `fk_asignaciones_turnos_obra` (`obra_id`);

--
-- Indices de la tabla `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indices de la tabla `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indices de la tabla `camiones`
--
ALTER TABLE `camiones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `empresa_id` (`empresa_id`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `chat_consultas_sql`
--
ALTER TABLE `chat_consultas_sql`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chat_consultas_sql_mensaje_id_foreign` (`mensaje_id`),
  ADD KEY `chat_consultas_sql_user_id_foreign` (`user_id`),
  ADD KEY `chat_consultas_sql_user_id_created_at_index` (`user_id`,`created_at`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_auditoria` (`user_id`,`exitosa`,`created_at`);
ALTER TABLE `chat_consultas_sql` ADD FULLTEXT KEY `idx_consulta` (`consulta_sql`);

--
-- Indices de la tabla `chat_conversaciones`
--
ALTER TABLE `chat_conversaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chat_conversaciones_user_id_foreign` (`user_id`),
  ADD KEY `chat_conversaciones_user_id_ultima_actividad_index` (`user_id`,`ultima_actividad`),
  ADD KEY `idx_user_actividad` (`user_id`,`ultima_actividad`);

--
-- Indices de la tabla `chat_mensajes`
--
ALTER TABLE `chat_mensajes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `chat_mensajes_conversacion_id_foreign` (`conversacion_id`),
  ADD KEY `chat_mensajes_conversacion_id_created_at_index` (`conversacion_id`,`created_at`),
  ADD KEY `idx_conversacion` (`conversacion_id`),
  ADD KEY `idx_conversacion_role` (`conversacion_id`,`role`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD UNIQUE KEY `cif_nif` (`cif_nif`);

--
-- Indices de la tabla `clientes_almacen`
--
ALTER TABLE `clientes_almacen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre_unico` (`nombre`);

--
-- Indices de la tabla `convenio`
--
ALTER TABLE `convenio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_convenio_categoria` (`categoria_id`);

--
-- Indices de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `departamento_seccion`
--
ALTER TABLE `departamento_seccion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_departamento_seccion` (`departamento_id`,`seccion_id`),
  ADD KEY `fk_seccion` (`seccion_id`);

--
-- Indices de la tabla `departamento_user`
--
ALTER TABLE `departamento_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_departamento_unique` (`user_id`,`departamento_id`),
  ADD KEY `fk_du_departamento` (`departamento_id`);

--
-- Indices de la tabla `distribuidores`
--
ALTER TABLE `distribuidores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `elementos`
--
ALTER TABLE `elementos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `maquina_id` (`maquina_id`),
  ADD KEY `fk_elementos_users_id` (`users_id`),
  ADD KEY `fk_elementos_planilla` (`planilla_id`),
  ADD KEY `fk_elementos_etiqueta` (`etiqueta_id`),
  ADD KEY `fk_producto` (`producto_id`),
  ADD KEY `fk_users_2` (`users_id_2`),
  ADD KEY `fk_elementos_producto2` (`producto_id_2`),
  ADD KEY `fk_elementos_paquete` (`paquete_id`),
  ADD KEY `fk_elementos_maquina2` (`maquina_id_2`),
  ADD KEY `fk_elementos_maquina3` (`maquina_id_3`),
  ADD KEY `fk_elementos_producto_3` (`producto_id_3`);

--
-- Indices de la tabla `empresas`
--
ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `empresas_transporte`
--
ALTER TABLE `empresas_transporte`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `entradas`
--
ALTER TABLE `entradas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_entradas_usuario` (`usuario_id`),
  ADD KEY `entradas_pedido_id_foreign` (`pedido_id`),
  ADD KEY `fk_entrada_linea` (`pedido_producto_id`),
  ADD KEY `fk_entradas_nave` (`nave_id`);

--
-- Indices de la tabla `entrada_producto`
--
ALTER TABLE `entrada_producto`
  ADD PRIMARY KEY (`id`),
  ADD KEY `entrada_id` (`entrada_id`),
  ADD KEY `producto_id` (`producto_id`),
  ADD KEY `ubicacion_id` (`ubicacion_id`),
  ADD KEY `users_id` (`users_id`);

--
-- Indices de la tabla `etiquetas`
--
ALTER TABLE `etiquetas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `etiquetas_etiqueta_sub_id_unique` (`etiqueta_sub_id`),
  ADD KEY `planilla_id` (`planilla_id`),
  ADD KEY `fk_etiquetas_producto_id` (`producto_id`),
  ADD KEY `fk_etiquetas_producto_id_2` (`producto_id_2`),
  ADD KEY `etiquetas_ubicacion_id_foreign` (`ubicacion_id`),
  ADD KEY `etiquetas_soldador1_foreign` (`soldador1_id`),
  ADD KEY `etiquetas_soldador2_foreign` (`soldador2_id`),
  ADD KEY `etiquetas_ensamblador1_foreign` (`ensamblador1_id`),
  ADD KEY `etiquetas_ensamblador2_foreign` (`ensamblador2_id`),
  ADD KEY `fk_etiquetas_operario1` (`operario1_id`),
  ADD KEY `fk_etiquetas_operario2` (`operario2_id`),
  ADD KEY `etiquetas_paquete_id_foreign` (`paquete_id`);

--
-- Indices de la tabla `fabricantes`
--
ALTER TABLE `fabricantes`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indices de la tabla `festivos`
--
ALTER TABLE `festivos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_fecha_anio` (`fecha`,`anio`);

--
-- Indices de la tabla `irpf_tramos`
--
ALTER TABLE `irpf_tramos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indices de la tabla `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `localizaciones`
--
ALTER TABLE `localizaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_unico_maquina_por_nave` (`nave_id`,`maquina_id`),
  ADD KEY `fk_localizaciones_maquina` (`maquina_id`);

--
-- Indices de la tabla `localizaciones_paquetes`
--
ALTER TABLE `localizaciones_paquetes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_localizaciones_paquetes_paquete` (`paquete_id`);

--
-- Indices de la tabla `maquinas`
--
ALTER TABLE `maquinas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `maquinas_obra_id_foreign` (`obra_id`);

--
-- Indices de la tabla `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `modelo_145`
--
ALTER TABLE `modelo_145`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `movimientos`
--
ALTER TABLE `movimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `producto_id` (`producto_id`),
  ADD KEY `ubicacion_origen` (`ubicacion_origen`),
  ADD KEY `ubicacion_destino` (`ubicacion_destino`),
  ADD KEY `maquina_origen` (`maquina_origen`),
  ADD KEY `maquina_id` (`maquina_destino`),
  ADD KEY `fk_paquete_id` (`paquete_id`),
  ADD KEY `solicitado_por` (`solicitado_por`),
  ADD KEY `ejecutado_por` (`ejecutado_por`),
  ADD KEY `movimientos_producto_base_id_foreign` (`producto_base_id`),
  ADD KEY `movimientos_pedido_id_foreign` (`pedido_id`),
  ADD KEY `movimientos_salida_id_foreign` (`salida_id`),
  ADD KEY `fk_movimientos_pedido_producto` (`pedido_producto_id`),
  ADD KEY `movimientos_nave_id_foreign` (`nave_id`),
  ADD KEY `movimientos_salida_almacen_id_foreign` (`salida_almacen_id`);

--
-- Indices de la tabla `nominas`
--
ALTER TABLE `nominas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_nominas_empleado` (`empleado_id`),
  ADD KEY `fk_nominas_categoria` (`categoria_id`);

--
-- Indices de la tabla `obras`
--
ALTER TABLE `obras`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cod_obra` (`cod_obra`),
  ADD KEY `fk_obras_clientes` (`cliente_id`);

--
-- Indices de la tabla `orden_planillas`
--
ALTER TABLE `orden_planillas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orden_planilla_maquina` (`maquina_id`),
  ADD KEY `idx_orden_planillas_planilla` (`planilla_id`),
  ADD KEY `idx_orden_planillas_planilla_maquina` (`planilla_id`,`maquina_id`);

--
-- Indices de la tabla `paquetes`
--
ALTER TABLE `paquetes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `ubicacion_id` (`ubicacion_id`),
  ADD KEY `paquetes_planilla_id_foreign` (`planilla_id`),
  ADD KEY `fk_paquetes_nave` (`nave_id`);

--
-- Indices de la tabla `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_unico` (`codigo`),
  ADD KEY `fk_pedidos_compras_pedido_global` (`pedido_global_id`),
  ADD KEY `pedidos_distribuidor_id_foreign` (`distribuidor_id`),
  ADD KEY `fk_pedidos_created_by` (`created_by`),
  ADD KEY `fk_pedidos_updated_by` (`updated_by`),
  ADD KEY `pedidos_fabricante_id_foreign` (`fabricante_id`);

--
-- Indices de la tabla `pedidos_almacen_venta`
--
ALTER TABLE `pedidos_almacen_venta`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `pedidos_almacen_venta_cliente_id_foreign` (`cliente_id`);

--
-- Indices de la tabla `pedidos_almacen_venta_lineas`
--
ALTER TABLE `pedidos_almacen_venta_lineas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_almacen_venta_id` (`pedido_almacen_venta_id`),
  ADD KEY `producto_base_id` (`producto_base_id`);

--
-- Indices de la tabla `pedidos_globales`
--
ALTER TABLE `pedidos_globales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `fk_pedidos_globales_distribuidor` (`distribuidor_id`),
  ADD KEY `pedidos_globales_fabricante_id_foreign` (`fabricante_id`);

--
-- Indices de la tabla `pedido_productos`
--
ALTER TABLE `pedido_productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `producto_base_id` (`producto_base_id`),
  ADD KEY `fk_pedido_productos_pedido_global` (`pedido_global_id`),
  ADD KEY `idx_pedido_producto_obra_id` (`obra_id`);

--
-- Indices de la tabla `permisos_acceso`
--
ALTER TABLE `permisos_acceso`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`departamento_id`,`seccion_id`),
  ADD KEY `departamento_id` (`departamento_id`),
  ADD KEY `seccion_id` (`seccion_id`);

--
-- Indices de la tabla `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indices de la tabla `planillas`
--
ALTER TABLE `planillas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `fk_planillas_users_id` (`users_id`),
  ADD KEY `fk_planillas_obra_id` (`obra_id`),
  ADD KEY `fk_cliente_id` (`cliente_id`),
  ADD KEY `planillas_revisada_por_id_fk` (`revisada_por_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `ubicacion_id` (`ubicacion_id`),
  ADD KEY `maquina_id` (`maquina_id`),
  ADD KEY `fk_productos_productos_base` (`producto_base_id`),
  ADD KEY `productos_distribuidor_id_foreign` (`distribuidor_id`),
  ADD KEY `fk_productos_entrada_id` (`entrada_id`),
  ADD KEY `fk_updated_by_productos` (`updated_by`),
  ADD KEY `consumido_by` (`consumido_by`),
  ADD KEY `fk_productos_obra` (`obra_id`),
  ADD KEY `productos_fabricante_id_foreign` (`fabricante_id`);

--
-- Indices de la tabla `productos_base`
--
ALTER TABLE `productos_base`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `productos_codigos`
--
ALTER TABLE `productos_codigos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigos_unicos` (`tipo`,`anio`,`mes`);

--
-- Indices de la tabla `salidas`
--
ALTER TABLE `salidas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `empresa_id` (`empresa_id`),
  ADD KEY `camion_id` (`camion_id`),
  ADD KEY `fk_salidas_user` (`user_id`);

--
-- Indices de la tabla `salidas_almacen`
--
ALTER TABLE `salidas_almacen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `salidas_almacen_camionero_id_foreign` (`camionero_id`),
  ADD KEY `salidas_almacen_created_by_foreign` (`created_by`),
  ADD KEY `salidas_almacen_updated_by_foreign` (`updated_by`);

--
-- Indices de la tabla `salidas_paquetes`
--
ALTER TABLE `salidas_paquetes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paquete_id` (`paquete_id`),
  ADD KEY `fk_salidas_paquetes_salida` (`salida_id`);

--
-- Indices de la tabla `salida_cliente`
--
ALTER TABLE `salida_cliente`
  ADD PRIMARY KEY (`id`),
  ADD KEY `salida_id` (`salida_id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `obra_id` (`obra_id`);

--
-- Indices de la tabla `secciones`
--
ALTER TABLE `secciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ruta` (`ruta`);

--
-- Indices de la tabla `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indices de la tabla `solicitudes_vacaciones`
--
ALTER TABLE `solicitudes_vacaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `ss_config`
--
ALTER TABLE `ss_config`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `subpaquetes`
--
ALTER TABLE `subpaquetes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `elemento_id` (`elemento_id`),
  ADD KEY `planilla_id` (`planilla_id`),
  ADD KEY `subpaquetes_paquete_id_foreign` (`paquete_id`);

--
-- Indices de la tabla `tasas_irpf`
--
ALTER TABLE `tasas_irpf`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anio` (`anio`);

--
-- Indices de la tabla `tasas_seguridad_social`
--
ALTER TABLE `tasas_seguridad_social`
  ADD PRIMARY KEY (`id`),
  ADD KEY `destinatario` (`destinatario`),
  ADD KEY `fecha_inicio` (`fecha_inicio`),
  ADD KEY `fecha_fin` (`fecha_fin`);

--
-- Indices de la tabla `turnos`
--
ALTER TABLE `turnos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `ubicaciones`
--
ALTER TABLE `ubicaciones`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `dni` (`dni`),
  ADD KEY `fk_categoria` (`categoria_id`),
  ADD KEY `fk_users_empresa` (`empresa_id`),
  ADD KEY `fk_users_especialidad` (`maquina_id`),
  ADD KEY `fk_updated_by_users` (`updated_by`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `albaranes_venta`
--
ALTER TABLE `albaranes_venta`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `albaranes_venta_lineas`
--
ALTER TABLE `albaranes_venta_lineas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `albaranes_venta_productos`
--
ALTER TABLE `albaranes_venta_productos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `alertas`
--
ALTER TABLE `alertas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `alertas_users`
--
ALTER TABLE `alertas_users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `asignaciones_turnos`
--
ALTER TABLE `asignaciones_turnos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `camiones`
--
ALTER TABLE `camiones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `chat_consultas_sql`
--
ALTER TABLE `chat_consultas_sql`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `chat_conversaciones`
--
ALTER TABLE `chat_conversaciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `chat_mensajes`
--
ALTER TABLE `chat_mensajes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `clientes_almacen`
--
ALTER TABLE `clientes_almacen`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `convenio`
--
ALTER TABLE `convenio`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `departamentos`
--
ALTER TABLE `departamentos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `departamento_seccion`
--
ALTER TABLE `departamento_seccion`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `departamento_user`
--
ALTER TABLE `departamento_user`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `distribuidores`
--
ALTER TABLE `distribuidores`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `elementos`
--
ALTER TABLE `elementos`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `empresas_transporte`
--
ALTER TABLE `empresas_transporte`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `entradas`
--
ALTER TABLE `entradas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `entrada_producto`
--
ALTER TABLE `entrada_producto`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `etiquetas`
--
ALTER TABLE `etiquetas`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `fabricantes`
--
ALTER TABLE `fabricantes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `festivos`
--
ALTER TABLE `festivos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `irpf_tramos`
--
ALTER TABLE `irpf_tramos`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `localizaciones`
--
ALTER TABLE `localizaciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `localizaciones_paquetes`
--
ALTER TABLE `localizaciones_paquetes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `maquinas`
--
ALTER TABLE `maquinas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `modelo_145`
--
ALTER TABLE `modelo_145`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `movimientos`
--
ALTER TABLE `movimientos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `nominas`
--
ALTER TABLE `nominas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `obras`
--
ALTER TABLE `obras`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `orden_planillas`
--
ALTER TABLE `orden_planillas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `paquetes`
--
ALTER TABLE `paquetes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedidos_almacen_venta`
--
ALTER TABLE `pedidos_almacen_venta`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedidos_almacen_venta_lineas`
--
ALTER TABLE `pedidos_almacen_venta_lineas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedidos_globales`
--
ALTER TABLE `pedidos_globales`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedido_productos`
--
ALTER TABLE `pedido_productos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `permisos_acceso`
--
ALTER TABLE `permisos_acceso`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `planillas`
--
ALTER TABLE `planillas`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `productos_base`
--
ALTER TABLE `productos_base`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `productos_codigos`
--
ALTER TABLE `productos_codigos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `salidas`
--
ALTER TABLE `salidas`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `salidas_almacen`
--
ALTER TABLE `salidas_almacen`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `salidas_paquetes`
--
ALTER TABLE `salidas_paquetes`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `salida_cliente`
--
ALTER TABLE `salida_cliente`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `secciones`
--
ALTER TABLE `secciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `solicitudes_vacaciones`
--
ALTER TABLE `solicitudes_vacaciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ss_config`
--
ALTER TABLE `ss_config`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `subpaquetes`
--
ALTER TABLE `subpaquetes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tasas_irpf`
--
ALTER TABLE `tasas_irpf`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tasas_seguridad_social`
--
ALTER TABLE `tasas_seguridad_social`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `turnos`
--
ALTER TABLE `turnos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ubicaciones`
--
ALTER TABLE `ubicaciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `albaranes_venta`
--
ALTER TABLE `albaranes_venta`
  ADD CONSTRAINT `albaranes_venta_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clientes_almacen` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `albaranes_venta_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `albaranes_venta_salida_id_foreign` FOREIGN KEY (`salida_id`) REFERENCES `salidas_almacen` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `albaranes_venta_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `albaranes_venta_lineas`
--
ALTER TABLE `albaranes_venta_lineas`
  ADD CONSTRAINT `albaranes_venta_lineas_albaran_id_foreign` FOREIGN KEY (`albaran_id`) REFERENCES `albaranes_venta` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `albaranes_venta_lineas_pedido_linea_id_foreign` FOREIGN KEY (`pedido_linea_id`) REFERENCES `pedidos_almacen_venta_lineas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `albaranes_venta_lineas_producto_base_id_foreign` FOREIGN KEY (`producto_base_id`) REFERENCES `productos_base` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `albaranes_venta_productos`
--
ALTER TABLE `albaranes_venta_productos`
  ADD CONSTRAINT `fk_avp_linea` FOREIGN KEY (`albaran_linea_id`) REFERENCES `albaranes_venta_lineas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_avp_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_avp_salida` FOREIGN KEY (`salida_almacen_id`) REFERENCES `salidas_almacen` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `alertas`
--
ALTER TABLE `alertas`
  ADD CONSTRAINT `alertas_ibfk_1` FOREIGN KEY (`user_id_1`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `alertas_ibfk_2` FOREIGN KEY (`user_id_2`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `alertas_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `alertas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_destinatario_id` FOREIGN KEY (`destinatario_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `alertas_users`
--
ALTER TABLE `alertas_users`
  ADD CONSTRAINT `alertas_users_ibfk_1` FOREIGN KEY (`alerta_id`) REFERENCES `alertas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alertas_users_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `asignaciones_turnos`
--
ALTER TABLE `asignaciones_turnos`
  ADD CONSTRAINT `asignaciones_turnos_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asignaciones_turnos_ibfk_2` FOREIGN KEY (`turno_id`) REFERENCES `turnos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_asignaciones_turnos_maquina` FOREIGN KEY (`maquina_id`) REFERENCES `maquinas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_asignaciones_turnos_obra` FOREIGN KEY (`obra_id`) REFERENCES `obras` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `camiones`
--
ALTER TABLE `camiones`
  ADD CONSTRAINT `camiones_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas_transporte` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `chat_consultas_sql`
--
ALTER TABLE `chat_consultas_sql`
  ADD CONSTRAINT `chat_consultas_sql_mensaje_id_foreign` FOREIGN KEY (`mensaje_id`) REFERENCES `chat_mensajes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_consultas_sql_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `chat_conversaciones`
--
ALTER TABLE `chat_conversaciones`
  ADD CONSTRAINT `chat_conversaciones_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `chat_mensajes`
--
ALTER TABLE `chat_mensajes`
  ADD CONSTRAINT `chat_mensajes_conversacion_id_foreign` FOREIGN KEY (`conversacion_id`) REFERENCES `chat_conversaciones` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `convenio`
--
ALTER TABLE `convenio`
  ADD CONSTRAINT `fk_convenio_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `departamento_seccion`
--
ALTER TABLE `departamento_seccion`
  ADD CONSTRAINT `fk_departamento` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_seccion` FOREIGN KEY (`seccion_id`) REFERENCES `secciones` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `departamento_user`
--
ALTER TABLE `departamento_user`
  ADD CONSTRAINT `fk_du_departamento` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_du_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `elementos`
--
ALTER TABLE `elementos`
  ADD CONSTRAINT `elementos_ibfk_2` FOREIGN KEY (`maquina_id`) REFERENCES `maquinas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_elementos_etiqueta` FOREIGN KEY (`etiqueta_id`) REFERENCES `etiquetas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_elementos_maquina2` FOREIGN KEY (`maquina_id_2`) REFERENCES `maquinas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_elementos_maquina3` FOREIGN KEY (`maquina_id_3`) REFERENCES `maquinas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_elementos_paquete` FOREIGN KEY (`paquete_id`) REFERENCES `paquetes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_elementos_planilla` FOREIGN KEY (`planilla_id`) REFERENCES `planillas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_elementos_producto2` FOREIGN KEY (`producto_id_2`) REFERENCES `productos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_elementos_producto_3` FOREIGN KEY (`producto_id_3`) REFERENCES `productos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_elementos_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_users_2` FOREIGN KEY (`users_id_2`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `entradas`
--
ALTER TABLE `entradas`
  ADD CONSTRAINT `entradas_pedido_id_foreign` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_entrada_linea` FOREIGN KEY (`pedido_producto_id`) REFERENCES `pedido_productos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_entradas_nave` FOREIGN KEY (`nave_id`) REFERENCES `obras` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_entradas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `entrada_producto`
--
ALTER TABLE `entrada_producto`
  ADD CONSTRAINT `entrada_producto_ibfk_1` FOREIGN KEY (`entrada_id`) REFERENCES `entradas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `entrada_producto_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `entrada_producto_ibfk_3` FOREIGN KEY (`ubicacion_id`) REFERENCES `ubicaciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `entrada_producto_ibfk_4` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `etiquetas`
--
ALTER TABLE `etiquetas`
  ADD CONSTRAINT `etiquetas_ensamblador1_foreign` FOREIGN KEY (`ensamblador1_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `etiquetas_ensamblador2_foreign` FOREIGN KEY (`ensamblador2_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `etiquetas_ibfk_1` FOREIGN KEY (`planilla_id`) REFERENCES `planillas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `etiquetas_paquete_id_foreign` FOREIGN KEY (`paquete_id`) REFERENCES `paquetes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `etiquetas_soldador1_foreign` FOREIGN KEY (`soldador1_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `etiquetas_soldador2_foreign` FOREIGN KEY (`soldador2_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `etiquetas_ubicacion_id_foreign` FOREIGN KEY (`ubicacion_id`) REFERENCES `ubicaciones` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_etiquetas_operario1` FOREIGN KEY (`operario1_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_etiquetas_operario2` FOREIGN KEY (`operario2_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_etiquetas_producto_id` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_etiquetas_producto_id_2` FOREIGN KEY (`producto_id_2`) REFERENCES `productos` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `localizaciones`
--
ALTER TABLE `localizaciones`
  ADD CONSTRAINT `fk_localizaciones_maquina` FOREIGN KEY (`maquina_id`) REFERENCES `maquinas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_localizaciones_nave` FOREIGN KEY (`nave_id`) REFERENCES `obras` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `localizaciones_paquetes`
--
ALTER TABLE `localizaciones_paquetes`
  ADD CONSTRAINT `fk_localizaciones_paquetes_paquete` FOREIGN KEY (`paquete_id`) REFERENCES `paquetes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `maquinas`
--
ALTER TABLE `maquinas`
  ADD CONSTRAINT `maquinas_obra_id_foreign` FOREIGN KEY (`obra_id`) REFERENCES `obras` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `modelo_145`
--
ALTER TABLE `modelo_145`
  ADD CONSTRAINT `modelo_145_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `movimientos`
--
ALTER TABLE `movimientos`
  ADD CONSTRAINT `fk_movimientos_pedido_producto` FOREIGN KEY (`pedido_producto_id`) REFERENCES `pedido_productos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_paquete_id` FOREIGN KEY (`paquete_id`) REFERENCES `paquetes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `movimientos_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `movimientos_ibfk_2` FOREIGN KEY (`ubicacion_origen`) REFERENCES `ubicaciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `movimientos_ibfk_3` FOREIGN KEY (`ubicacion_destino`) REFERENCES `ubicaciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `movimientos_ibfk_4` FOREIGN KEY (`maquina_origen`) REFERENCES `maquinas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `movimientos_ibfk_7` FOREIGN KEY (`solicitado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `movimientos_ibfk_8` FOREIGN KEY (`ejecutado_por`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `movimientos_maquina_destino_foreign` FOREIGN KEY (`maquina_destino`) REFERENCES `maquinas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `movimientos_nave_id_foreign` FOREIGN KEY (`nave_id`) REFERENCES `obras` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `movimientos_pedido_id_foreign` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `movimientos_producto_base_id_foreign` FOREIGN KEY (`producto_base_id`) REFERENCES `productos_base` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `movimientos_salida_almacen_id_foreign` FOREIGN KEY (`salida_almacen_id`) REFERENCES `salidas_almacen` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `movimientos_salida_id_foreign` FOREIGN KEY (`salida_id`) REFERENCES `salidas` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `nominas`
--
ALTER TABLE `nominas`
  ADD CONSTRAINT `fk_nominas_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_nominas_empleado` FOREIGN KEY (`empleado_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `obras`
--
ALTER TABLE `obras`
  ADD CONSTRAINT `fk_obras_clientes` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `orden_planillas`
--
ALTER TABLE `orden_planillas`
  ADD CONSTRAINT `FK_ORDEN_PLANILLA_PLANILLA` FOREIGN KEY (`planilla_id`) REFERENCES `planillas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_orden_planilla_maquina` FOREIGN KEY (`maquina_id`) REFERENCES `maquinas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `paquetes`
--
ALTER TABLE `paquetes`
  ADD CONSTRAINT `fk_paquetes_nave` FOREIGN KEY (`nave_id`) REFERENCES `obras` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `paquetes_ibfk_1` FOREIGN KEY (`ubicacion_id`) REFERENCES `ubicaciones` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `paquetes_planilla_id_foreign` FOREIGN KEY (`planilla_id`) REFERENCES `planillas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_pedidos_compras_pedido_global` FOREIGN KEY (`pedido_global_id`) REFERENCES `pedidos_globales` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pedidos_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pedidos_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `pedidos_distribuidor_id_foreign` FOREIGN KEY (`distribuidor_id`) REFERENCES `distribuidores` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pedidos_fabricante_id_foreign` FOREIGN KEY (`fabricante_id`) REFERENCES `fabricantes` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pedidos_almacen_venta`
--
ALTER TABLE `pedidos_almacen_venta`
  ADD CONSTRAINT `pedidos_almacen_venta_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clientes_almacen` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pedidos_almacen_venta_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pedidos_almacen_venta_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `pedidos_almacen_venta_lineas`
--
ALTER TABLE `pedidos_almacen_venta_lineas`
  ADD CONSTRAINT `pedidos_almacen_venta_lineas_ibfk_1` FOREIGN KEY (`pedido_almacen_venta_id`) REFERENCES `pedidos_almacen_venta` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pedidos_almacen_venta_lineas_ibfk_2` FOREIGN KEY (`producto_base_id`) REFERENCES `productos_base` (`id`);

--
-- Filtros para la tabla `pedidos_globales`
--
ALTER TABLE `pedidos_globales`
  ADD CONSTRAINT `fk_pedidos_globales_distribuidor` FOREIGN KEY (`distribuidor_id`) REFERENCES `distribuidores` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pedidos_globales_fabricante_id_foreign` FOREIGN KEY (`fabricante_id`) REFERENCES `fabricantes` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `pedido_productos`
--
ALTER TABLE `pedido_productos`
  ADD CONSTRAINT `fk_pedido_producto_obra` FOREIGN KEY (`obra_id`) REFERENCES `obras` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pedido_productos_pedido_global` FOREIGN KEY (`pedido_global_id`) REFERENCES `pedidos_globales` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `pedido_productos_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pedido_productos_ibfk_2` FOREIGN KEY (`producto_base_id`) REFERENCES `productos_base` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `permisos_acceso`
--
ALTER TABLE `permisos_acceso`
  ADD CONSTRAINT `permisos_acceso_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `permisos_acceso_ibfk_2` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `permisos_acceso_ibfk_3` FOREIGN KEY (`seccion_id`) REFERENCES `secciones` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `planillas`
--
ALTER TABLE `planillas`
  ADD CONSTRAINT `fk_cliente_id` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `fk_planillas_obra_id` FOREIGN KEY (`obra_id`) REFERENCES `obras` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_planillas_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `planillas_revisada_por_id_fk` FOREIGN KEY (`revisada_por_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `fk_productos_entrada_id` FOREIGN KEY (`entrada_id`) REFERENCES `entradas` (`id`),
  ADD CONSTRAINT `fk_productos_obra` FOREIGN KEY (`obra_id`) REFERENCES `obras` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_productos_productos_base` FOREIGN KEY (`producto_base_id`) REFERENCES `productos_base` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_updated_by_productos` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `productos_distribuidor_id_foreign` FOREIGN KEY (`distribuidor_id`) REFERENCES `distribuidores` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `productos_fabricante_id_foreign` FOREIGN KEY (`fabricante_id`) REFERENCES `fabricantes` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`ubicacion_id`) REFERENCES `ubicaciones` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `productos_ibfk_2` FOREIGN KEY (`maquina_id`) REFERENCES `maquinas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `productos_ibfk_3` FOREIGN KEY (`consumido_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `salidas`
--
ALTER TABLE `salidas`
  ADD CONSTRAINT `fk_salidas_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `salidas_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas_transporte` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `salidas_ibfk_2` FOREIGN KEY (`camion_id`) REFERENCES `camiones` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `salidas_almacen`
--
ALTER TABLE `salidas_almacen`
  ADD CONSTRAINT `salidas_almacen_camionero_id_foreign` FOREIGN KEY (`camionero_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `salidas_almacen_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `salidas_almacen_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `salidas_paquetes`
--
ALTER TABLE `salidas_paquetes`
  ADD CONSTRAINT `fk_salidas_paquetes_salida` FOREIGN KEY (`salida_id`) REFERENCES `salidas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `salidas_paquetes_ibfk_2` FOREIGN KEY (`paquete_id`) REFERENCES `paquetes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `salida_cliente`
--
ALTER TABLE `salida_cliente`
  ADD CONSTRAINT `fk_salida_cliente_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_salida_cliente_obra` FOREIGN KEY (`obra_id`) REFERENCES `obras` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_salida_cliente_salida` FOREIGN KEY (`salida_id`) REFERENCES `salidas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `solicitudes_vacaciones`
--
ALTER TABLE `solicitudes_vacaciones`
  ADD CONSTRAINT `solicitudes_vacaciones_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `subpaquetes`
--
ALTER TABLE `subpaquetes`
  ADD CONSTRAINT `subpaquetes_ibfk_1` FOREIGN KEY (`elemento_id`) REFERENCES `elementos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subpaquetes_ibfk_2` FOREIGN KEY (`planilla_id`) REFERENCES `planillas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subpaquetes_paquete_id_foreign` FOREIGN KEY (`paquete_id`) REFERENCES `paquetes` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_updated_by_users` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_users_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_users_especialidad` FOREIGN KEY (`maquina_id`) REFERENCES `maquinas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
