-- Esquema Transportes y Mudanzas Pantera (PostgreSQL)
-- Basado en la estructura de Control El Lince, migrado de MySQL a Postgres

DROP TABLE IF EXISTS facturaciones CASCADE;
CREATE TABLE facturaciones (
  id SERIAL PRIMARY KEY,
  asesor VARCHAR(255),
  fecha DATE NOT NULL,
  cliente VARCHAR(150) NOT NULL,
  fecha_servicio DATE,
  hora_servicio TIME,
  servicio_id INTEGER,
  producto VARCHAR(500),
  cantidad INTEGER NOT NULL DEFAULT 1,
  anticipo NUMERIC(10,2) NOT NULL DEFAULT 0.00,
  subtotal NUMERIC(10,2),
  iva NUMERIC(10,2),
  total NUMERIC(10,2) NOT NULL
);

DROP TABLE IF EXISTS folios CASCADE;
CREATE TABLE folios (
  id SERIAL PRIMARY KEY,
  ultimo_folio INTEGER NOT NULL DEFAULT 0
);
INSERT INTO folios (ultimo_folio) VALUES (0);

DROP TABLE IF EXISTS pagos CASCADE;
CREATE TABLE pagos (
  id SERIAL PRIMARY KEY,
  cotizacion_id INTEGER NOT NULL,
  nombre_cliente VARCHAR(255),
  asesor VARCHAR(100),
  monto NUMERIC(10,2),
  concepto VARCHAR(255),
  clabe VARCHAR(20),
  banco VARCHAR(100),
  convenio_cie VARCHAR(20),
  referencia_spei VARCHAR(30),
  openpay_customer_id VARCHAR(50),
  status VARCHAR(20) DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_limite_pago DATE,
  paynet_reference VARCHAR(50),
  openpay_id VARCHAR(50),
  deleted_at TIMESTAMP,
  payment_url VARCHAR(255)
);
CREATE INDEX idx_pagos_asesor ON pagos(asesor);

DROP TABLE IF EXISTS rastreo_links CASCADE;
CREATE TABLE rastreo_links (
  id SERIAL PRIMARY KEY,
  token CHAR(32) NOT NULL UNIQUE,
  servicio_id INTEGER,
  shipday_order_id BIGINT NOT NULL,
  shipday_carrier_id BIGINT,
  cliente_nombre VARCHAR(150),
  creado_por VARCHAR(100),
  estado VARCHAR(20) NOT NULL DEFAULT 'activo'
    CHECK (estado IN ('activo','completado','fallido','expirado')),
  creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expira_en TIMESTAMP NOT NULL,
  completado_en TIMESTAMP,
  datos_finales TEXT
);
CREATE INDEX idx_rastreo_token ON rastreo_links(token);
CREATE INDEX idx_rastreo_shipday_order ON rastreo_links(shipday_order_id);

DROP TABLE IF EXISTS reviews CASCADE;
CREATE TABLE reviews (
  id SERIAL PRIMARY KEY,
  rating INTEGER NOT NULL,
  phone VARCHAR(20),
  comments TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

DROP TABLE IF EXISTS servicios CASCADE;
CREATE TABLE servicios (
  id SERIAL PRIMARY KEY,
  nombre_cliente VARCHAR(100) NOT NULL,
  telefono VARCHAR(20),
  correo VARCHAR(150),
  tipo_servicio VARCHAR(50) NOT NULL,
  inmueble VARCHAR(100),
  destino VARCHAR(100),
  direccion_origen TEXT NOT NULL,
  direccion_destino TEXT NOT NULL,
  tipo_camioneta VARCHAR(50) NOT NULL,
  inventario TEXT,
  cargadores INTEGER DEFAULT 0,
  maniobra NUMERIC(10,2) DEFAULT 0.00,
  total NUMERIC(10,2) NOT NULL,
  fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  idempotency_key VARCHAR(64) UNIQUE
);

DROP TABLE IF EXISTS usuarios CASCADE;
CREATE TABLE usuarios (
  id SERIAL PRIMARY KEY,
  correo VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(150) NOT NULL,
  nombre_usuario VARCHAR(100),
  webauthn_credential_id TEXT NOT NULL DEFAULT '',
  webauthn_public_key TEXT NOT NULL DEFAULT '',
  rol VARCHAR(20),
  huella_token_android VARCHAR(255),
  clave_publica TEXT
);

DROP TABLE IF EXISTS usuarios_huella CASCADE;
CREATE TABLE usuarios_huella (
  id SERIAL PRIMARY KEY,
  correo VARCHAR(255) NOT NULL UNIQUE,
  credentialId TEXT NOT NULL,
  publicKey TEXT
);
