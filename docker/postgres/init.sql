-- Script d'initialisation PostgreSQL pour MMM

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

DO $$
BEGIN
    RAISE NOTICE 'Base de données MMM initialisée avec succès !';
END $$;