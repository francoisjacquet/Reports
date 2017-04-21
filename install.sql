/**
 * Install SQL
 * Required if the module has menu entries
 * - Add profile exceptions for the module to appear in the menu
 * - Add program config options if any (to every schools)
 * - Add module specific tables (and their eventual sequences & indexes)
 *   if any: see rosariosis.sql file for examples
 *
 * @package Reports module
 */

-- Fix #102 error language "plpgsql" does not exist
-- http://timmurphy.org/2011/08/27/create-language-if-it-doesnt-exist-in-postgresql/
--
-- Name: create_language_plpgsql(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION create_language_plpgsql()
RETURNS BOOLEAN AS $$
    CREATE LANGUAGE plpgsql;
    SELECT TRUE;
$$ LANGUAGE SQL;

SELECT CASE WHEN NOT (
    SELECT TRUE AS exists FROM pg_language
    WHERE lanname='plpgsql'
    UNION
    SELECT FALSE AS exists
    ORDER BY exists DESC
    LIMIT 1
) THEN
    create_language_plpgsql()
ELSE
    FALSE
END AS plpgsql_created;

DROP FUNCTION create_language_plpgsql();


--
-- Data for Name: profile_exceptions; Type: TABLE DATA;
--

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Reports/SavedReports.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Reports/SavedReports.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Reports/Calculations.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Reports/Calculations.php'
    AND profile_id=1);

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit)
SELECT 1, 'Reports/CalculationsReports.php', 'Y', 'Y'
WHERE NOT EXISTS (SELECT profile_id
    FROM profile_exceptions
    WHERE modname='Reports/CalculationsReports.php'
    AND profile_id=1);


--
-- Name: saved_calculations; Type: TABLE; ; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_saved_calculations() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname=CURRENT_SCHEMA
        AND tablename='saved_calculations') THEN
    RAISE NOTICE 'Table "saved_calculations" already exists.';
    ELSE
        CREATE TABLE saved_calculations (
            id numeric NOT NULL,
            title character varying(100),
            url character varying(5000)
        );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_saved_calculations();
DROP FUNCTION create_table_saved_calculations();


--
-- Name: saved_calculations_seq; Type: SEQUENCE;
--

CREATE OR REPLACE FUNCTION create_sequence_saved_calculations_seq() RETURNS void AS
$func$
BEGIN
    CREATE SEQUENCE saved_calculations_seq
        START WITH 1
        INCREMENT BY 1
        NO MAXVALUE
        NO MINVALUE
        CACHE 1;
EXCEPTION WHEN duplicate_table THEN
    RAISE NOTICE 'Sequence "saved_calculations_seq" already exists.';
END
$func$ LANGUAGE plpgsql;

SELECT create_sequence_saved_calculations_seq();
DROP FUNCTION create_sequence_saved_calculations_seq();


--
-- Name: saved_reports; Type: TABLE; ; Tablespace:
--

CREATE OR REPLACE FUNCTION create_table_saved_reports() RETURNS void AS
$func$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_catalog.pg_tables
        WHERE schemaname=CURRENT_SCHEMA
        AND tablename='saved_reports') THEN
    RAISE NOTICE 'Table "saved_reports" already exists.';
    ELSE
        CREATE TABLE saved_reports (
            id numeric NOT NULL,
            title character varying(100),
            staff_id numeric,
            php_self character varying(5000),
            search_php_self character varying(5000),
            search_vars character varying(5000)
        );
    END IF;
END
$func$ LANGUAGE plpgsql;

SELECT create_table_saved_reports();
DROP FUNCTION create_table_saved_reports();


--
-- Name: saved_reports_seq; Type: SEQUENCE;
--

CREATE OR REPLACE FUNCTION create_sequence_saved_reports_seq() RETURNS void AS
$func$
BEGIN
    CREATE SEQUENCE saved_reports_seq
        START WITH 1
        INCREMENT BY 1
        NO MAXVALUE
        NO MINVALUE
        CACHE 1;
EXCEPTION WHEN duplicate_table THEN
    RAISE NOTICE 'Sequence "saved_reports_seq" already exists.';
END
$func$ LANGUAGE plpgsql;

SELECT create_sequence_saved_reports_seq();
DROP FUNCTION create_sequence_saved_reports_seq();
